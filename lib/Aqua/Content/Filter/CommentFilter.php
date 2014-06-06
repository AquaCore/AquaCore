<?php
namespace Aqua\Content\Filter;

use Aqua\BBCode\BBCode;
use Aqua\Content\AbstractFilter;
use Aqua\Content\ContentData;
use Aqua\Content\ContentType;
use Aqua\Content\Filter\CommentFilter\Comment;
use Aqua\Content\Filter\CommentFilter\Report;
use Aqua\Core\App;
use Aqua\Event\Event;
use Aqua\SQL\Query;
use Aqua\SQL\Search;
use Aqua\UI\Tag\Meta;
use Aqua\User\Account;

class CommentFilter
extends AbstractFilter
{
	public static $cache;

	const CACHE_KEY              = 'general_cache.comments';
	const CACHE_TTL              = 86400;
	const CACHE_RECENT_COMMENTS  = 4;
	const CACHE_RECENT_REPORTS   = 5;

	public function afterUpdate(ContentData $content, array $data, array &$values)
	{
		$updated = 0;
		if(array_key_exists('comments_disabled', $data)) {
			if($data['comments_disabled']) {
				$content->meta->set('comments-disabled', true);
			} else {
				$content->meta->delete('comments-disabled');
			}
			$values['comments_disabled'] = (bool)$data['comments_disabled'];
			++$updated;
		}
		if(array_key_exists('comment_anonymously', $data)) {
			if($data['comment_anonymously']) {
				$content->meta->set('comment-anonymously', true);
			} else {
				$content->meta->delete('comment-anonymously');
			}
			$values['comment_anonymously'] = (bool)$data['comment_anonymously'];
			++$updated;
		}

		return (bool)$updated;
	}

	public function afterCreate(ContentData $content, array &$data)
	{
		if(array_key_exists('comments_disabled', $data) && $data['comments_disabled'] === true) {
			$content->meta->set('comments-disabled', true);
		}
		if(array_key_exists('comment_anonymously', $data) && $data['comment_anonymously'] === true) {
			$content->meta->set('comment-anonymously', true);
		}
	}

	public function forge(ContentData $content, array $data)
	{
		$content->meta['comments-disabled']   = (array_key_exists('comments_disabled', $data) &&
		                                         $data['comments_disabled'] === true);
		$content->meta['comment-anonymously'] = (array_key_exists('comment_anonymously', $data) &&
		                                         $data['comment_anonymously'] === true);
	}

	/**
	 * @param \Aqua\Content\ContentData $content
	 * @param \Aqua\User\Account        $author
	 * @param string                    $bbcode
	 * @param bool                      $anon
	 * @param int                       $options
	 * @param int                       $status
	 * @param \Aqua\Content\Filter\COmmentFilter\Comment|null $parent
	 * @return \Aqua\Content\Filter\CommentFilter\Comment|null
	 */
	public function contentData_addComment(
		ContentData $content,
		Account $author,
		$bbcode,
		$anon = false,
		$options = 0,
		$status = Comment::STATUS_PUBLISHED,
		Comment $parent = null
	) {
		$feedback = array(
			$content,
		    $author,
		    &$bbcode,
		    &$anon,
		    &$options,
		    &$status,
		    $parent
		);
		if($content->forged ||
		   $content->meta->get('comments-disabled', false) ||
		   $this->notify('before-create', $feedback) === false) {
			return null;
		}
		if(!$content->meta->get('comment-anonymously', false)) {
			$anon = false;
		}
		$html = $this->parseCommentContent($bbcode);
		$tbl  = ac_table('comments');
		$sth  = App::connection()->prepare("
		INSERT INTO `$tbl` (
		_content_type,
		_content_id,
		_parent_id,
		_root_id,
		_nesting_level,
		_author_id,
		_status,
		_ip_address,
		_anonymous,
		_html_content,
		_bbc_content,
		_publish_date,
		_options
		) VALUES (
		:ctype,
		:id,
		:parent,
		:root,
		:level,
		:author,
		:status,
		:ip,
		:anon,
		:html,
		:bbcode,
		NOW(),
		:opt
		)
		");
		$sth->bindValue(':ctype', $content->contentType->id, \PDO::PARAM_INT);
		$sth->bindValue(':id', $content->uid, \PDO::PARAM_INT);
		$sth->bindValue(':author', $author->id, \PDO::PARAM_INT);
		$sth->bindValue(':status', $status, \PDO::PARAM_INT);
		$sth->bindValue(':ip', App::request()->ipString, \PDO::PARAM_STR);
		$sth->bindValue(':anon', $anon ? 'y' : 'n', \PDO::PARAM_STR);
		$sth->bindValue(':html', $html, \PDO::PARAM_LOB);
		$sth->bindValue(':bbcode', $bbcode, \PDO::PARAM_LOB);
		$sth->bindValue(':opt', $options, \PDO::PARAM_INT);
		if($parent) {
			$sth->bindValue(':parent', $parent->id, \PDO::PARAM_INT);
			$sth->bindValue(':root', $parent->rootId ?: $parent->id, \PDO::PARAM_INT);
			$sth->bindValue(':level', $parent->nestingLevel + 1, \PDO::PARAM_INT);
		} else {
			$sth->bindValue(':parent', 0, \PDO::PARAM_INT);
			$sth->bindValue(':root', 0, \PDO::PARAM_INT);
			$sth->bindValue(':level', 0, \PDO::PARAM_INT);
		}
		$sth->execute();
		$comment  = $this->contentType_getComment((int)App::connection()->lastInsertId());
		$feedback = array( $comment );
		if($parent) {
			$sth = App::connection()->prepare("
			UPDATE `$tbl`
			SET _children = _children + 1
			WHERE id = ?
			");
			$sth->bindValue(1, $parent->id, \PDO::PARAM_INT);
			$sth->execute();
		}
		$this->notify('after-create', $feedback);
		$this->contentData_commentSpamFilter($content, $comment);
		self::$cache !== null or self::fetchCache(null, true);
		if(!empty(self::$cache)) {
			array_unshift(self::$cache['last_comments'], array(
				'id'           => $comment->id,
				'content_id'   => $comment->contentId,
				'status'       => $comment->status,
				'anonymous'    => $comment->anonymous,
				'content'      => $comment->html,
				'publish_date' => $comment->publishDate,
				'user_id'      => $comment->authorId,
				'display_name' => $author->displayName,
				'avatar'       => $author->avatar(),
				'role_id'      => $author->roleId,
				'title'        => $content->title,
				'slug'         => $content->slug,
				'content_type' => $content->contentType->id
			));
			if(count(self::$cache['last_comments']) > self::CACHE_RECENT_COMMENTS) {
				self::$cache['last_comments'] = array_slice(
					self::$cache['last_comments'],
					0,
					self::CACHE_RECENT_COMMENTS,
					false
				);
			}
			App::cache()->store(self::CACHE_KEY, self::$cache, self::CACHE_TTL);
		}
		return $comment;
	}

	/**
	 * @param \Aqua\Content\ContentData                  $content
	 * @param \Aqua\Content\Filter\CommentFilter\Comment $comment
	 * @param array                                      $edit
	 * @return bool|int
	 */
	public function contentData_updateComment(ContentData $content, Comment $comment, array $edit)
	{
		$values = array();
		$update = '';
		$data   = array();
		$edit   = array_intersect_key($edit, array_flip(array(
				'author', 'last_editor', 'status', 'ip_address',
				'anonymous', 'publish_date',
				'bbcode_content', 'html_content', 'options'
			)));
		$feedback = array( $comment, &$edit );
		if(empty($edit) || $this->notify('before-edit', $feedback) === false) {
			return false;
		}
		$edit = array_map(function ($val) { return (is_string($val) ? trim($val) : $val); }, $edit);
		if(array_key_exists('author', $edit) && $edit['author'] !== $comment->authorId) {
			$values['author'] = $data['authorId'] = $edit['author'];
			$update .= '_author_id = ?, ';
		}
		if(array_key_exists('status', $edit) && $edit['status'] !== $comment->status) {
			$values['status'] = $data['status'] = $edit['status'];
			$update .= '_status = ?, ';
		}
		if(array_key_exists('ip_address', $edit) && $edit['ip_address'] !== $comment->ipAddress) {
			$values['ip_address'] = $data['ipAddress'] = $edit['ip_address'];
			$update .= '_ip_address = ?, ';
		}
		if(array_key_exists('anonymous', $edit) && (bool)$edit['anonymous'] !== $comment->anonymous) {
			$data['anonymous']   = (bool)$edit['anonymous'];
			$values['anonymous'] = ($data['anonymous'] ? 'y' : 'n');
			$update .= '_anonymous = ?, ';
		}
		if(array_key_exists('publish_date', $edit) && $edit['publish_date'] !== $comment->publishDate) {
			$data['publishDate']    = $edit['publish_date'];
			$values['publish_date'] = date('Y-m-d H:i:s', $data['publishDate']);
			$update .= '_publish_date = ?, ';
		}
		if(array_key_exists('bbcode_content', $edit)) {
			$values['bbcode_content'] = $data['bbCode'] = $edit['bbcode_content'];
			$update .= '_bbc_content = ?, ';
			if(!isset($edit['html_content'])) {
				$edit['html_content'] = $this->parseCommentContent($edit['bbcode_content']);
			}
		}
		if(array_key_exists('html_content', $edit)) {
			$values['html_content'] = $data['html'] = $edit['html_content'];
			$update .= '_html_content = ?, ';
		}
		if(array_key_exists('options', $edit)) {
			$values['html_content'] = $data['html'] = $edit['html_content'];
			$update .= '_html_content = ?, ';
		}
		if(array_key_exists('rating', $edit)) {
			$values['rating'] = $data['rating'] = $edit['rating'];
			$update .= '_rating = ?, ';
		}
		if(empty($values)) {
			return false;
		}
		if(array_key_exists('last_editor', $edit) && $edit['last_editor'] !== $comment->lastEditorId) {
			$values['last_editor'] = $data['lastEditorId'] = $edit['last_editor'];
			$update .= '_editor_id = ?, ';
		}
		$update   = substr($update, 0, -2);
		$values[] = $comment->id;
		$tbl      = ac_table('comments');
		$sth      = App::connection()->prepare("
		UPDATE `$tbl`
		SET {$update}
		WHERE id = ?
		LIMIT 1
		");
		if(!$sth->execute(array_values($values)) || !$sth->rowCount()) {
			return false;
		}
		array_pop($values);
		if(isset($values['publish_date'])) $values['publish_date'] = strtotime($values['publish_date']);
		if(isset($values['anonymous'])) $values['anonymous'] = ($values['anonymous'] === 'y');
		$comment->editDate = time();
		$feedback = array( $comment, $values );
		$this->notify('after-edit', $feedback);
		foreach($data as $key => $val) {
			$comment->$key = $val;
		}

		return true;
	}

	/**
	 * @param \Aqua\Content\ContentData $content
	 * @return int
	 */
	public function contentData_commentCount(ContentData $content)
	{
		if($content->forged) return 0;
		if(!array_key_exists('comment_count', $content->data)) {
			$tbl = ac_table('comments');
			$sth = App::connection()->prepare("SELECT COUNT(1) FROM `$tbl` WHERE _content_id = ?");
			$sth->bindValue(1, $content->uid, \PDO::PARAM_INT);
			$sth->execute();
			$content->data['comment_count'] = (int)$sth->fetchColumn(0);
		}

		return $content->data['comment_count'];
	}

	/**
	 * @param \Aqua\Content\ContentData $content
	 * @param \Aqua\User\Account        $user
	 * @return array|null
	 */
	public function contentData_commentRatings(ContentData $content, Account $user = null)
	{
		if(!$user || $content->forged) {
			return ($content ? null : array());
		}
		$tbl     = ac_table('comment_ratings');
		$query   = "
		SELECT _comment_id, _weight
		FROM `$tbl`
		WHERE _user_id = ?
		AND _content_id = ?";
		$ratings = array();
		$sth     = App::connection()->prepare($query);
		$sth->bindValue(1, $user->id, \PDO::PARAM_INT);
		$sth->bindValue(2, $content->uid, \PDO::PARAM_INT);
		$sth->execute();
		while($data = $sth->fetch(\PDO::FETCH_NUM)) {
			$ratings[$data[0]] = (int)$data[1];
		}

		return $ratings;
	}

	/**
	 * @param \Aqua\Content\ContentData $content
	 * @return \Aqua\SQL\Search
	 */
	public function contentData_commentSearch(ContentData $content)
	{
		return self::commentSearch()
			->where(array( 'content_id' => $content->uid ));
	}

	public function contentData_getComments(ContentData $content, $root = null, $start = 0, $limit = 0)
	{
		$search = $this->contentData_commentSearch($content)
			->calcRows(true)
			->where(array( 'status' => Comment::STATUS_PUBLISHED ))
			->setKey('id')
			->limit($start, $limit)
			->order(array( 'publish_date' => 'DESC' ));
		if($root) {
			$search->where(array( 'id' => $root ));
		} else {
			$search->where(array( 'root_id' => 0 ));
		}
		$search->query();
		$rowsFound = $search->rowsFound;
		$comments  = $search->results;
		if(!($nestingLevel = (int)$this->getOption('nasting', 5)) || empty($comments)) {
			return array( $comments, $rowsFound );
		}
		$in = array();
		$minNestingLevel = 0;
		foreach($comments as $comment) {
			$comment->children = array();
			$in[] = $comment->rootId ?: $comment->id;
			$minNestingLevel = min($nestingLevel, $comment->nestingLevel);
		}
		$in = array_unique($in);
		array_unshift($in, Search::SEARCH_IN);
		$search->where(array(
				'root_id' => $in,
				'nesting_level' => array( Search::SEARCH_BETWEEN, $minNestingLevel, $nestingLevel + $minNestingLevel ),
			), false)
			->limit(null, null);
		$search->query();
		foreach($search as $comment) {
			if(array_key_exists($comment->parentId, $comments)) {
				$comments[$comment->parentId]->children[] = $comment;
			} else if(array_key_exists($comment->parentId, $search->results)) {
				if(!$search->results[$comment->parentId]) {
					$search->results[$comment->parentId] = array();
				}
				$search->results[$comment->parentId]->children[] = $comment;
			}
		}
		return array( $comments, $rowsFound );
	}

	/**
	 * @return \Aqua\SQL\Search
	 */
	public function contentType_commentSearch()
	{
		return self::commentSearch()
			->where(array( 'content_type' => $this->contentType->id ));
	}

	/**
	 * @param  int $id
	 * @return \Aqua\Content\Filter\CommentFilter\Comment|null
	 */
	public function contentType_getComment($id)
	{
		$select = self::commentSearch()
			->where(array(
				'id' => $id,
			    'content_type' => $this->contentType->id
			))
			->query();

		return ($select->valid() ? $select->current() : null);
	}

	/**
	 * @param \Aqua\Content\Filter\CommentFilter\Comment $comment
	 * @param \Aqua\User\Account                         $user
	 * @param int                                        $weight
	 * @return bool|mixed
	 */
	public function contentType_rateComment(Comment $comment, Account $user, $weight)
	{
		if(!$this->getOption('rating', false) ||
		   ($comment->authorId === $user->id &&
		    !$this->getOption('rateself', false))) {
			return false;
		}
		$weight = max(-1, min(1, $weight));
		$tbl    = ac_table('comment_ratings');
		$sth    = App::connection()->prepare("
		SELECT _weight
		FROM `$tbl`
		WHERE _user_id = :user
		AND _comment_id = :comment
		LIMIT 1
		");
		$sth->bindValue(':user', $user->id, \PDO::PARAM_INT);
		$sth->bindValue(':comment', $comment->id, \PDO::PARAM_INT);
		$sth->execute();
		$old_weight = (int)$sth->fetchColumn(0);
		$sth        = App::connection()->prepare("
		INSERT INTO `$tbl` (_user_id, _content_id, _comment_id, _ip_address, _weight)
		VALUES (:user, :content, :comment, :ip, :weight)
		ON DUPLICATE KEY UPDATE _weight = :weight
		");
		$sth->bindValue(':user', $user->id, \PDO::PARAM_INT);
		$sth->bindValue(':content', $comment->contentId, \PDO::PARAM_INT);
		$sth->bindValue(':comment', $comment->id, \PDO::PARAM_INT);
		$sth->bindValue(':ip', App::request()->ipString, \PDO::PARAM_STR);
		$sth->bindValue(':weight', $weight, \PDO::PARAM_INT);
		$sth->execute();
		$weight_total = -$old_weight + $weight;
		if($weight_total === 0) {
			return $weight;
		}
		else if($weight_total < 0) {
			$operator = '-';
		}
		else $operator = '+';
		$tbl = ac_table('comments');
		$sth = App::connection()->prepare("
		UPDATE `$tbl`
		SET _rating = _rating $operator :weight
		WHERE id = :comment
		LIMIT 1
		");
		$sth->bindValue(':weight', abs($weight_total), \PDO::PARAM_INT);
		$sth->bindValue(':comment', $comment->id, \PDO::PARAM_INT);
		$sth->execute();
		$comment->rating += $weight_total;
		$feedback = array( $comment, $user, &$weight );
		$this->notify('rate', $feedback);

		return $weight;
	}

	public function contentType_reportComment(Comment $comment, Account $user, $report)
	{
		$tbl = ac_table('comment_reports');
		$sth = App::connection()->prepare("
		INSERT INTO `$tbl` (_comment_id, _user_id, _ip_address, _date, _content)
		VALUES (:comment, :user, :ip, NOW(), :content)
		");
		$sth->bindValue(':comment', $comment->id, \PDO::PARAM_INT);
		$sth->bindValue(':user', $user->id, \PDO::PARAM_INT);
		$sth->bindValue(':ip', App::request()->ipString, \PDO::PARAM_STR);
		$sth->bindValue(':content', $report, \PDO::PARAM_STR);
		if(!$sth->execute() || !$sth->rowCount()) {
			return false;
		}
		$tbl = ac_table('comments');
		$reportId = App::connection()->lastInsertId();
		$sth = App::connection()->prepare("
		UPDATE `$tbl`
		SET _reports = _reports + 1
		WHERE id = ?
		LIMIT 1
		");
		$sth->bindValue(1, $comment->id, \PDO::PARAM_INT);
		$sth->execute();
		$comment->reportCount++;
		$report = $this->reportSearch()
			->where(array( 'id' => $reportId ))
			->query()
			->current();
		$feedback = array( $report, $comment, $user );
		Event::fire('comment.report', $feedback);
		self::$cache !== null or self::fetchCache(null, true);
		if(!empty(self::$cache)) {
			array_unshift(self::$cache['last_reports'], array(
				'id'           => $report->id,
				'comment_id'   => $report->commentId,
				'date'         => $report->date,
				'content'      => $report->report,
				'user_id'      => $report->userId,
				'display_name' => $user->displayName,
				'role_id'      => $user->roleId,
			));
			if(count(self::$cache['last_reports']) > self::CACHE_RECENT_REPORTS) {
				self::$cache['last_reports'] = array_slice(
					self::$cache['last_reports'],
					0,
					self::CACHE_RECENT_REPORTS,
					false
				);
			}
			App::cache()->store(self::CACHE_KEY, self::$cache, self::CACHE_TTL);
		}
		return $report;
	}

	public function contentData_commentSpamFilter(ContentData $content, Comment $comment)
	{
		$isSpam = false;
		$feedback = array( $content, $comment, &$isSpam );
		if(Event::fire('content.filter-spam', $feedback) === false || $isSpam) {
			$comment->update(array( 'status' => Comment::STATUS_FLAGGED ));
			return true;
		}
		return false;
	}

	/**
	 * @param string $content
	 * @return string
	 */
	public static function parseCommentContent($content)
	{
		$bbcode = new BBCode;
		$bbcode->defaults();

		return $bbcode->parse($content);
	}

	/**
	 * @param array $data
	 * @return \Aqua\Content\Filter\CommentFilter\Comment
	 */
	public static function parseCommentSql(array $data)
	{
		$comment               = new Comment;
		$comment->meta         = new Meta(ac_table('comment_meta'), $data['id']);
		$comment->contentType  = ContentType::getContentType($data['content_type']);
		$comment->contentId    = (int)$data['content_id'];
		$comment->parentId     = (int)$data['parent_id'];
		$comment->rootId       = (int)$data['root_id'];
		$comment->nestingLevel = (int)$data['nesting_level'];
		$comment->childCount   = (int)$data['children'];
		$comment->id           = (int)$data['id'];
		$comment->status       = (int)$data['status'];
		$comment->authorId     = (int)$data['author'];
		if($data['last_editor'] !== null) $comment->lastEditorId = (int)$data['last_editor'];
		$comment->publishDate = (int)$data['publish_date'];
		if($data['edit_date'] !== null) $comment->editDate = (int)$data['edit_date'];
		$comment->options     = (int)$data['options'];
		$comment->rating      = (int)$data['rating'];
		$comment->reportCount = (int)$data['reports'];
		$comment->anonymous   = ($data['anonymous'] === 'y');
		$comment->ipAddress   = $data['ip_address'];
		$comment->html        = $data['html'];
		$comment->bbCode      = $data['bbcode'];

		return $comment;
	}

	/**
	 * @param array $data
	 * @return \Aqua\Content\Filter\CommentFilter\Report
	 */
	public static function parseReportSql(array $data)
	{
		$report            = new Report;
		$report->id        = (int)$data['id'];
		$report->commentId = (int)$data['comment_id'];
		$report->userId    = (int)$data['user_id'];
		$report->date      = (int)$data['date'];
		$report->ipAddress = $data['ip_address'];
		$report->report    = $data['report'];

		return $report;
	}

	public static function commentSearch()
	{
		return Query::search(App::connection())
			->parser(array( __CLASS__, 'parseCommentSql' ))
			->from(ac_table('comments'), 'co')
			->groupBy('co.id')
			->columns(array(
				'id'            => 'co.id',
				'content_id'    => 'co._content_id',
				'content_type'  => 'co._content_type',
				'parent_id'     => 'co._parent_id',
				'root_id'       => 'co._root_id',
				'nesting_level' => 'co._nesting_level',
				'children'      => 'co._children',
				'author'        => 'co._author_id',
				'last_editor'   => 'co._editor_id',
				'ip_address'    => 'co._ip_address',
				'status'        => 'co._status',
				'anonymous'     => 'co._anonymous',
				'publish_date'  => 'UNIX_TIMESTAMP(co._publish_date)',
				'edit_date'     => 'UNIX_TIMESTAMP(co._edit_date)',
				'rating'        => 'co._rating',
				'reports'       => 'co._reports',
				'options'       => 'co._options',
				'html'          => 'co._html_content',
				'bbcode'        => 'co._bbc_content'
			))->whereOptions(array(
				'id'            => 'co.id',
				'content_id'    => 'co._content_id',
				'content_type'  => 'co._content_type',
				'parent_id'     => 'co._parent_id',
				'root_id'       => 'co._root_id',
				'nesting_level' => 'co._nesting_level',
				'children'      => 'co._children',
				'author'        => 'co._author',
				'last_editor'   => 'co._editor_id',
				'ip_address'    => 'co._ip_address',
				'status'        => 'co._status',
				'anonymous'     => 'co._anonymous',
				'publish_date'  => 'co._publish_date',
				'edit_date'     => 'co._edit_date',
				'rating'        => 'co._rating',
				'reports'       => 'co._reports',
				'options'       => 'co._options',
			));
	}

	/**
	 * @return \Aqua\SQL\Search
	 */
	public static function reportSearch()
	{
		return Query::search(App::connection())
			->parser(array( __CLASS__, 'parseReportSql' ))
			->from(ac_table('comment_reports'), 'cr')
			->groupBy('cr.id')
			->columns(array(
				'id'         => 'cr.id',
				'comment_id' => 'cr._comment_id',
				'user_id'    => 'cr._user_id',
				'ip_address' => 'cr._ip_address',
				'date'       => 'UNIX_TIMESTAMP(cr._date)',
				'report'     => 'cr._content',
			))->whereOptions(array(
				'id'         => 'cr.id',
				'comment_id' => 'cr._comment_id',
				'user_id'    => 'cr._user_id',
				'ip_address' => 'cr._ip_address',
				'date'       => 'cr._date',
			));
	}

	/**
	 * @param int $id
	 * @return \Aqua\Content\Filter\CommentFilter\Comment|null
	 */
	public static function getComment($id)
	{
		$search = self::commentSearch()
			->where(array( 'id' => $id ))
			->query();

		return ($search->valid() ? $search->current() : null);
	}

	/**
	 * @param int $id
	 * @return \Aqua\Content\Filter\CommentFilter\Report|null
	 */
	public static function getReport($id)
	{
		$search = self::reportSearch()->where(array( 'id' => $id ));

		return ($search->valid() ? $search->current() : null);
	}

	/**
	 * @param string|null $name
	 * @param bool        $internal
	 * @return mixed
	 */
	public static function fetchCache($name = null, $internal = false)
	{
		self::$cache !== null or (self::$cache = App::cache()->fetch(self::CACHE_KEY, array()));
		if($internal) {
			return null;
		}
		if(empty(self::$cache)) {
			self::rebuildCache();
		}
		if($name === null) {
			return self::$cache;
		} else if(isset(self::$cache[$name])) {
			return self::$cache[$name];
		} else {
			return null;
		}
	}

	public static function rebuildCache($name = null)
	{
		if($name === null || $name === 'last_comments') {
			self::$cache['last_comments'] = Query::select(App::connection())
				->columns(array(
					'id'           => 'c.id',
				    'content_id'   => 'c._content_id',
				    'status'       => 'c._status',
				    'anonymous'    => 'c._anonymous',
				    'content'      => 'c._html_content',
				    'publish_date' => 'UNIX_TIMESTAMP(c._publish_date)',
				    'user_id'      => 'c._author_id',
				    'display_name' => 'u._display_name',
				    'avatar'       => 'u._avatar',
				    'role_id'      => 'u._role_id',
				    'title'        => 'cd._title',
				    'slug'         => 'cd._slug',
				    'content_type' => 'cd._type'
				))
				->setColumnType(array(
					'id'           => 'integer',
				    'content_id'   => 'integer',
				    'content_type' => 'integer',
				    'status'       => 'integer',
				    'role_id'      => 'integer',
				    'publish_date' => 'timestamp'
				))
				->from(ac_table('comments'), 'c')
				->innerJoin(ac_table('users'), 'u.id = c._author_id', 'u')
				->innerJoin(ac_table('content'), 'cd._uid = c._content_id', 'cd')
				->order(array( 'c._publish_date' => 'DESC' ))
				->groupBy('c.id')
				->limit(self::CACHE_RECENT_COMMENTS)
				->query()
				->results;
			foreach(self::$cache['last_comments'] as &$cache) {
				if(substr($cache['avatar'], 0, 16) === '/uploads/avatar/') {
					$cache['avatar'] = \Aqua\URL . $cache['avatar'];
				} else if(empty($cache['avatar'])) {
					$path = App::settings()->get('account')->get('default_avatar', '');
					if($path) {
						$cache['avatar'] = \Aqua\URL . $path;
					} else {
						$cache['avatar'] = \Aqua\BLANK;
					}
				}
			}
		}
		if($name === null || $name === 'last_reports') {
			self::$cache['last_reports'] = Query::select(App::connection())
				->columns(array(
					'id'           => 'r.id',
				    'comment_id'   => 'r._comment_id',
				    'date'         => 'r._date',
				    'content'      => 'r._content',
				    'user_id'      => 'r._user_id',
				    'display_name' => 'u._display_name',
				    'role_id'      => 'u._role_id',
				))
				->setColumnType(array(
					'id'           => 'integer',
				    'comment_id'   => 'integer',
				    'user_id'      => 'integer',
				    'role_id'      => 'integer',
				    'date'         => 'timestamp'
				))
				->from(ac_table('comment_reports'), 'r')
				->innerJoin(ac_table('users'), 'u.id = r._user_id', 'u')
				->order(array( 'r._date' => 'DESC' ))
				->groupBy('r.id')
				->limit(self::CACHE_RECENT_REPORTS)
				->query()
				->results;
		}
		App::cache()->store(self::CACHE_KEY, self::$cache, self::CACHE_TTL);
	}
}
