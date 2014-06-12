<?php
namespace Aqua\Content\Filter;

use Aqua\Content\AbstractFilter;
use Aqua\Content\ContentData;
use Aqua\Content\ContentType;
use Aqua\Content\Filter\CommentFilter\Comment;
use Aqua\Core\App;
use Aqua\SQL\Query;
use Aqua\SQL\Search;
use Aqua\User\Account;
use Aqua\User\Role;
use Aqua\Util\Email;

class SubscriptionFilter
extends AbstractFilter
{
	const COMMENT_SUBSCRIPTION = 1;
	const REPLY_SUBSCRIPTION   = 2;

	public function init()
	{
		if($this->getOption('comments', true) && $this->contentType->hasFilter('CommentFilter')) {
			$this->contentType->filter('CommentFilter')->attach('after-create', array( $this, 'onComment' ));
		}
	}

	public function onComment($event, ContentType $cType, Comment $comment)
	{
		$content        = $comment->content();
		$datetimeFormat = App::settings()->get('datetime_format', '');
		$replacements   = array(
			'site-title'                => htmlspecialchars(App::settings()->get('title', '')),
			'site-url'                  => \Aqua\URL,
			'content-title'             => htmlspecialchars($content->title),
			'content-url'               => $content->contentType->url(array( 'path' => array( $content->slug ) ), false),
			'comment-url'               => $content->contentType->url(array(
					'path' => array( $content->slug ),
					'query' => array( 'root' => $comment->id )
				), false),
			'content-date'              => $content->publishDate($datetimeFormat),
			'comment-html'              => $comment->html,
			'comment-bbcode'            => $comment->bbCode,
			'comment-display-name'      => $comment->author()->displayName,
			'comment-display-name-full' => $comment->authorDisplay(),
			'comment-avatar'            => $comment->authorAvatar()
		);
		foreach($content->getSubscriptions($comment) as $sub) {
			if(empty($sub['comment_id'])) {
				$email = Email::fromTemplate('sub-comment');
			} else {
				$email = Email::fromTemplate('sub-comment-reply');
				$email->replace(array(
					'user-comment-url' => $content->contentType->url(array(
							'path' => array( $content->slug ),
					        'query' => array( 'root' => $sub['comment_id'] )
						), false)
				));
			}
			if(!$role = Role::get($sub['role_id'])) {
				$role = Role::get(Role::ROLE_USER);
			}
			$email->replace($replacements + array(
					'user-display-name'      => htmlspecialchars($sub['display_name']),
					'user-display-name-full' => $role->display($sub['display_name'], 'ac-username'),
			        'user-email'             => htmlspecialchars($sub['email']),
			        'username'               => htmlspecialchars($sub['username']),
				))
				->addAddress($sub['email'], $sub['display_name'])
				->queue();
		}
	}

	public function afterCreate(ContentData $content, array &$data)
	{
		if(!$this->getOption('content', true) ||
		   (array_key_exists('notify_subscribers', $data) && $data['notify_subscribers'] === false) ||
		   (!array_key_exists('notify_subscribers', $data) && (!$this->contentType->hasFilter('featuredFilter') ||
		                                                       !$content->isFeatured()))) {
			return;
		}
		$replacements = array(
			'site-title'               => htmlspecialchars(App::settings()->get('title', '')),
			'site-url'                 => \Aqua\URL,
			'content-type-name'        => strtolower(htmlspecialchars($content->contentType->itemName)),
			'content-title'            => htmlspecialchars($content->title),
			'content-url'              => $content->contentType->url(array( 'path' => array( $content->slug ) ), false),
		    'content-date'             => $content->publishDate(App::settings()->get('datetime_format', '')),
		    'short-content'            => $content->shortContent ?: $content->truncate(600, '...'),
		    'author-display-name'      => $content->author()->displayName,
		    'author-display-name-full' => $content->author()->display(),
		    'author-avatar'            => $content->author()->avatar(),
		);
		foreach($this->contentType->getSubscriptions() as $sub) {
			if(!($role = Role::get($sub['role_id']))) {
				$role = Role::get(Role::ROLE_USER);
			}
			Email::fromTemplate('sub-content')
				->replace($replacements + array(
					'username'               => $sub['username'],
				    'user-display-name'      => $sub['display_name'],
				    'user-display-name-full' => $role->display($sub['username'], 'ac-username'),
				    'user-avatar'            => $sub['avatar'],
				    'user-email'             => $sub['email']
				))
				->addAddress($sub['email'], $sub['display_name'])
				->queue();
		}
	}

	public function afterDelete(ContentData $content)
	{
		$sth = App::connection()->prepare('
		DELETE FROM `%s`
		WHERE _content_id = ?
		', ac_table('content_subscriptions'));
		$sth->bindValue(1, $content->uid, \PDO::PARAM_STR);
		$sth->execute();
		$sth->closeCursor();
	}

	public function contentType_addSubscription(Account $user)
	{
		$sth = App::connection()->prepare(sprintf('
		INSERT INTO `%s` (_type, _user_id)
		VALUES (:type, :user)
		ON DUPLICATE KEY UPDATE _type = VALUES(_type)
		', ac_table('content_type_subscriptions')));
		$sth->bindValue(':type', $this->contentType->id, \PDO::PARAM_INT);
		$sth->bindValue(':user', $user->id, \PDO::PARAM_INT);
		$sth->execute();
		if($count = $sth->rowCount()) {
			$feedback = array( $user );
			$this->notify('content-type-subscribe', $feedback);
		}
		$sth->closeCursor();
		return (bool)$count;
	}

	public function contentType_removeSubscription(Account $user)
	{
		$sth = App::connection()->prepare(sprintf('
		DELETE FROM `%s`
		WHERE _type = :type
		AND _user_id = :user
		', ac_table('content_type_subscriptions')));
		$sth->bindValue(':type', $this->contentType->id, \PDO::PARAM_INT);
		$sth->bindValue(':user', $user->id, \PDO::PARAM_INT);
		$sth->execute();
		if($count = $sth->rowCount()) {
			$feedback = array( $user );
			$this->notify('content-type-unsubscribe', $feedback);
		}
		$sth->closeCursor();
		return (bool)$count;
	}

	public function contentType_isSubscribed(Account $account)
	{
		$sth = App::connection()->prepare(sprintf('
		SELECT COUNT(1)
		FROM `%s`
		WHERE _type = :type
		AND _user_id = :user
		LIMIT 1
		', ac_table('content_type_subscriptions')));
		$sth->bindValue(':type', $this->contentType->id, \PDO::PARAM_INT);
		$sth->bindValue(':user', $account->id, \PDO::PARAM_INT);
		$sth->execute();
		return (bool)$sth->fetchColumn(0);
	}

	public function contentType_getSubscriptions()
	{
		return Query::select(App::connection())
			->columns(array(
				'id'           => 'u.id',
				'username'     => 'u._username',
				'display_name' => 'u._display_name',
				'email'        => 'u._email',
				'avatar'       => 'u._avatar',
				'status'       => 'u._status',
				'role_id'      => 'u._role_id'
			))
			->setColumnType(array( 'role_id' => 'integer' ))
			->from(ac_table('content_type_subscriptions'), 's')
			->innerJoin(ac_table('users'), 'u.id = s._user_id', 'u')
			->where(array( 's._type' => $this->contentType->id ))
			->setKey('id')
			->query()
			->results;
	}

	public function contentData_addSubscription(ContentData $content, Account $user, $type)
	{
		$sth = App::connection()->prepare(sprintf('
		INSERT INTO `%s` (_content_id, _user_id, _type)
		VALUES (:content, :user, :type)
		ON DUPLICATE KEY UPDATE _type = VALUES(_type)
		', ac_table('content_subscriptions')));
		$sth->bindValue(':content', $content->uid, \PDO::PARAM_INT);
		$sth->bindValue(':user', $user->id, \PDO::PARAM_INT);
		$sth->bindValue(':type', $type, \PDO::PARAM_INT);
		$sth->execute();
		if($count = $sth->rowCount()) {
			$feedback = array( $content, $user, $type );
			$this->notify('content-subscribe', $feedback);
		}
		$sth->closeCursor();
		return (bool)$count;
	}

	public function contentData_removeSubscription(ContentData $content, Account $user)
	{
		$sth = App::connection()->prepare(sprintf('
		DELETE FROM `%s`
		WHERE _content_id = :content
		AND _user_id = :user
		', ac_table('content_subscriptions')));
		$sth->bindValue(':content', $content->uid, \PDO::PARAM_INT);
		$sth->bindValue(':user', $user->id, \PDO::PARAM_INT);
		$sth->execute();
		if($count = $sth->rowCount()) {
			$feedback = array( $content, $user );
			$this->notify('content-unsubscribe', $feedback);
		}
		$sth->closeCursor();
		return (bool)$count;
	}

	public function contentData_isSubscribed(ContentData $content, Account $account)
	{
		$sth = App::connection()->prepare(sprintf('
		SELECT (_type + 0)
		FROM `%s`
		WHERE _content_id = :content
		AND _user_id = :user
		LIMIT 1
		', ac_table('content_subscriptions')));
		$sth->bindValue(':content', $content->uid, \PDO::PARAM_INT);
		$sth->bindValue(':user', $account->id, \PDO::PARAM_INT);
		$sth->execute();
		if($sth->rowCount()) {
			return (int)$sth->fetchColumn(0);
		} else {
			return null;
		}
	}

	public function contentData_getSubscriptions(ContentData $content, Comment $comment)
	{
		$nestingLevel = $this->getOption('replyNestingLevel', 1);
		$columns = array(
			'id'           => 'u.id',
			'username'     => 'u._username',
			'display_name' => 'u._display_name',
		    'email'        => 'u._email',
		    'avatar'       => 'u._avatar',
		    'status'       => 'u._status',
		    'role_id'      => 'u._role_id',
		    'comment_id'   => 'NULL',
		    'parent_id'    => 'NULL'
		);
		$subs = Query::select(App::connection())
			->columns($columns)
			->setColumnType(array( 'role_id' => 'integer' ))
			->from(ac_table('content_subscriptions'), 's')
			->innerJoin(ac_table('users'), 'u.id = s._user_id', 'u')
			->where(array(
				's._content_id' => $content->uid,
				's._user_id'    => array( Search::SEARCH_DIFFERENT, $comment->authorId ),
				's._type'       => 'comments'
			))
			->setKey('id')
			->query()
			->results;
		if($comment->parentId) {
			$columns['comment_id'] = 'c.id';
			$columns['parent_id']  = 'c._parent_id';
			$columns['sub_type']   = '(s._type + 0)';
			$select = Query::select(App::connection())
				->columns($columns)
				->setColumnType(array(
					'role_id'    => 'integer',
					'comment_id' => 'integer',
					'parent_id'  => 'integer'
				))
				->from(ac_table('comments'), 'c')
				->innerJoin(ac_table('content_subscriptions'), 's._user_id = c._author_id', 's')
				->innerJoin(ac_table('users'), 'u.id = c._author_id', 'u')
				->having(array(
		            'id'        => array( Search::SEARCH_DIFFERENT, $comment->authorId ),
					'sub_type'  => self::REPLY_SUBSCRIPTION
				))
				->setKey('id');
			$parentId = $comment->parentId;
			for($i = 0; $i < $nestingLevel; ++$i) {
				$select->where(array( 'c.id' => $parentId ), false)->query();
				if($select->valid()) {
					$subs[$select->key()] = $select->current();
					$parentId = $select->get('parent_id');
				} else {
					break;
				}
			}
		}
		return $subs;
	}
}
