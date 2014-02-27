<?php
namespace Aqua\Content\Filter;

use Aqua\BBCode\BBCode;
use Aqua\Content\AbstractFilter;
use Aqua\Content\ContentData;
use Aqua\Content\Filter\CommentFilter\Comment;
use Aqua\Core\App;
use Aqua\Event\Event;
use Aqua\SQL\Query;
use Aqua\User\Account;

class CommentFilter
extends AbstractFilter
{
	public function afterUpdate(ContentData $content, array $data, array &$values)
	{
		$updated = 0;
		if(array_key_exists('comments_disabled', $data)) {
			if($data['comments_disabled']) {
				$content->setMeta('comments-disabled', true);
			} else {
				$content->deleteMeta('comments-disabled');
			}
			$values['comments_disabled'] = (bool)$data['comments_disabled'];
			++$updated;
		}
		if(array_key_exists('comment_anonymously', $data)) {
			if($data['comment_anonymously']) {
				$content->setMeta('comment-anonymously', true);
			} else {
				$content->deleteMeta('comment-anonymously');
			}
			$values['comment_anonymously'] = (bool)$data['comment_anonymously'];
			++$updated;
		}

		return (bool)$updated;
	}

	public function afterCreate(ContentData $content, array &$data)
	{
		if(array_key_exists('comments_disabled', $data) && $data['comments_disabled'] === true) {
			$content->setMeta('comments-disabled', true);
		}
		if(array_key_exists('comment_anonymously', $data) && $data['comment_anonymously'] === true) {
			$content->setMeta('comment-anonymously', true);
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
	 * @return \Aqua\Content\Filter\CommentFilter\Comment|null
	 */
	public function contentData_addComment(
		ContentData $content,
		Account $author,
		$bbcode,
		$anon = false,
		$options = 0,
		$status = Comment::STATUS_PUBLISHED
	) {
		if($content->forged) return null;
		if($content->getMeta('comments-disabled', false)) return null;
		if(!$content->getMeta('comment-anonymously', false)) $anon = false;
		$html = $this->parseCommentContent($bbcode);
		$tbl  = ac_table('comments');
		$sth  = App::connection()->prepare("
		INSERT INTO `$tbl` (
		_content_id,
		_author_id,
		_status,
		_ip_address,
		_anonymous,
		_html_content,
		_bbc_content,
		_publish_date,
		_options
		) VALUES (
		:id,
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
		$sth->bindValue(':id', $content->uid, \PDO::PARAM_INT);
		$sth->bindValue(':author', $author->id, \PDO::PARAM_INT);
		$sth->bindValue(':status', $status, \PDO::PARAM_INT);
		$sth->bindValue(':ip', App::request()->ipString, \PDO::PARAM_STR);
		$sth->bindValue(':anon', $anon ? 'y' : 'n', \PDO::PARAM_STR);
		$sth->bindValue(':html', $html, \PDO::PARAM_LOB);
		$sth->bindValue(':bbcode', $bbcode, \PDO::PARAM_LOB);
		$sth->bindValue(':opt', $options, \PDO::PARAM_INT);
		$sth->execute();
		$comment  = $this->contentType_getComment((int)App::connection()->lastInsertId());
		$feedback = array( $comment );
		Event::fire('comment.create', $feedback);

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
				'anonymous', 'publish_date', 'edit_date',
				'bbcode_content', 'html_content', 'options'
			)));
		if(empty($edit)) {
			return false;
		}
		$edit = array_map(function ($val) { return (is_string($val) ? trim($val) : $val); }, $edit);
		if(isset($edit['author']) && $edit['author'] !== $comment->authorId) {
			$values['author'] = $data['authorId'] = $edit['author'];
			$update .= '_author_id = ?, ';
		}
		if(isset($edit['status']) && $edit['status'] !== $comment->status) {
			$values['status'] = $data['status'] = $edit['status'];
			$update .= '_status = ?, ';
		}
		if(isset($edit['ip_address']) && $edit['ip_address'] !== $comment->ipAddress) {
			$values['ip_address'] = $data['ipAddress'] = $edit['ip_address'];
			$update .= '_ip_address = ?, ';
		}
		if(isset($edit['anonymous']) && (bool)$edit['anonymous'] !== $comment->anonymous) {
			$data['anonymous']   = (bool)$edit['anonymous'];
			$values['anonymous'] = ($data['anonymous'] ? 'y' : 'n');
			$update .= '_anonymous = ?, ';
		}
		if(isset($edit['publish_date']) && $edit['publish_date'] !== $comment->publishDate) {
			$data['publishDate']    = $edit['publish_date'];
			$values['publish_date'] = date('Y-m-d H:i:s', $data['publishDate']);
			$update .= '_publish_date = ?, ';
		}
		if(isset($edit['edit_date']) && $edit['edit_date'] !== $comment->editDate) {
			$data['editDate']    = $edit['edit_date'];
			$values['edit_date'] = date('Y-m-d H:i:s', $data['edit_date']);
			$update .= '_edit_date = ?, ';
		}
		if(isset($edit['bbcode_content'])) {
			$values['bbcode_content'] = $data['bbCode'] = $edit['bbcode_content'];
			$update .= '_bbcode_content = ?, ';
			if(!isset($edit['html_content'])) {
				$edit['html_content'] = $this->parseCommentContent($edit['bbcode_content']);
			}
		}
		if(isset($edit['html_content'])) {
			$values['html_content'] = $data['html'] = $edit['html_content'];
			$update .= '_html_content = ?, ';
		}
		if(isset($edit['options'])) {
			$values['html_content'] = $data['html'] = $edit['html_content'];
			$update .= '_html_content = ?, ';
		}
		if(isset($edit['rating'])) {
			$values['rating'] = $data['rating'] = $edit['rating'];
			$update .= '_rating = ?, ';
		}
		if(empty($values)) {
			return false;
		}
		if(isset($edit['last_editor']) && $edit['last_editor'] !== $comment->lastEditorId) {
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
		if(isset($values['edit_date'])) $values['edit_date'] = strtotime($values['publish_date']);
		if(isset($values['anonymous'])) $values['anonymous'] = ($values['anonymous'] === 'y');
		$feedback = array( $comment, $values );
		Event::fire('comment.update', $feedback);
		if(!$values['edit_date']) $comment->editDate = time();
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
		return $this->commentSearch()
			->where(array( 'content_id' => $content->uid ));
	}

	/**
	 * @return \Aqua\SQL\Search
	 */
	public function contentType_commentSearch()
	{
		return $this->commentSearch()
			->innerJoin(ac_table('content'), 'co._content_id = c._uid', 'c')
			->whereOptions(array( 'content_type' => 'c._type' ))
			->where(array( 'content_type' => $this->contentType->id ));
	}

	/**
	 * @param  int                      $id
	 * @return \Aqua\Content\Filter\CommentFilter\Comment|null
	 */
	public function contentType_getComment($id)
	{
		$select = Query::select(App::connection())
			->columns(array(
				'id'           => 'c.id',
				'content_id'   => 'c._content_id',
				'author'       => 'c._author_id',
				'last_editor'  => 'c._editor_id',
				'ip_address'   => 'c._ip_address',
				'status'       => 'c._status',
				'anonymous'    => 'c._anonymous',
				'publish_date' => 'UNIX_TIMESTAMP(c._publish_date)',
				'edit_date'    => 'UNIX_TIMESTAMP(c._edit_date)',
				'rating'       => 'c._rating',
				'options'      => 'c._options',
				'html'         => 'c._html_content',
				'bbcode'       => 'c._bbc_content'
			))
			->from(ac_table('comments'), 'c')
			->where(array( 'c.id' => $id ))
			->limit(1)
			->parser(array( $this, 'parseCommentSql' ))
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
		   ($comment->authorId === $user->id && !$this->getOption('rateself', false))) {
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

		return $weight;
	}

	/**
	 * @return \Aqua\SQL\Search
	 */
	public function commentSearch()
	{
		return Query::search(App::connection())
			->parser(array( $this, 'parseCommentSql' ))
			->from(ac_table('comments'), 'co')
			->groupBy('co.id')
			->columns(array(
				'id'           => 'co.id',
				'content_id'   => 'co._content_id',
				'author'       => 'co._author_id',
				'last_editor'  => 'co._editor_id',
				'ip_address'   => 'co._ip_address',
				'status'       => 'co._status',
				'anonymous'    => 'co._anonymous',
				'publish_date' => 'UNIX_TIMESTAMP(co._publish_date)',
				'edit_date'    => 'UNIX_TIMESTAMP(co._edit_date)',
				'rating'       => 'co._rating',
				'options'      => 'co._options',
				'html'         => 'co._html_content',
				'bbcode'       => 'co._bbc_content'
			))->whereOptions(array(
				'id'           => 'co._id',
				'content_id'   => 'co._content_id',
				'author'       => 'co._author',
				'last_editor'  => 'co._editor_id',
				'ip_address'   => 'co._ip_address',
				'status'       => 'co._status',
				'anonymous'    => 'co._anonymous',
				'publish_date' => 'co._publish_date',
				'edit_date'    => 'co._edit_date',
				'rating'       => 'co._rating',
				'options'      => 'co._options',
			));
	}

	/**
	 * @param array $data
	 * @return \Aqua\Content\Filter\CommentFilter\Comment
	 */
	public function parseCommentSql(array $data)
	{
		$comment              = new Comment;
		$comment->contentType = & $this->contentType;
		$comment->contentId   = (int)$data['content_id'];
		$comment->id          = (int)$data['id'];
		$comment->status      = (int)$data['status'];
		$comment->authorId    = (int)$data['author'];
		if($data['last_editor'] !== null) $comment->lastEditorId = (int)$data['last_editor'];
		$comment->publishDate = (int)$data['publish_date'];
		if($data['edit_date'] !== null) $comment->editDate = (int)$data['edit_date'];
		$comment->options   = (int)$data['options'];
		$comment->rating    = (int)$data['rating'];
		$comment->anonymous = ($data['anonymous'] === 'y');
		$comment->ipAddress = $data['ip_address'];
		$comment->html      = $data['html'];
		$comment->bbCode    = $data['bbcode'];

		return $comment;
	}

	/**
	 * @param string $content
	 * @return string
	 */
	public function parseCommentContent($content)
	{
		$bbcode = new BBCode;
		$bbcode->defaults();

		return $bbcode->parse($content);
	}
}
