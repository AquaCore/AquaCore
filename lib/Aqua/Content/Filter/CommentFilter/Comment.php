<?php
namespace Aqua\Content\Filter\CommentFilter;

use Aqua\Core\App;
use Aqua\User\Account;

class Comment
{
	/**
	 * @var \Aqua\Content\ContentType
	 */
	public $contentType;
	/**
	 * @var int
	 */
	public $contentId;
	/**
	 * @var int
	 */
	public $rootId;
	/**
	 * @var int
	 */
	public $parentId;
	/**
	 * @var int
	 */
	public $nestingLevel;
	/**
	 * @var int
	 */
	public $id;
	/**
	 * @var string
	 */
	public $ipAddress;
	/**
	 * @var int
	 */
	public $status;
	/**
	 * @var bool
	 */
	public $anonymous;
	/**
	 * @var int
	 */
	public $authorId;
	/**
	 * @var int
	 */
	public $lastEditorId;
	/**
	 * @var int
	 */
	public $publishDate;
	/**
	 * @var int
	 */
	public $editDate;
	/**
	 * @var string
	 */
	public $bbCode;
	/**
	 * @var string
	 */
	public $html;
	/**
	 * @var int
	 */
	public $rating;
	/**
	 * @var int
	 */
	public $options;
	/**
	 * @var \Aqua\Content\Filter\CommentFilter\Comment|null
	 */
	public $parent;
	/**
	 * @var \Aqua\Content\Filter\CommentFilter\Comment[]|null
	 */
	public $children;
	/**
	 * @var int
	 */
	public $childCount;
	/**
	 * @var array
	 */
	public $meta;
	/**
	 * @var bool
	 */
	protected $_metaLoaded = false;

	const STATUS_PUBLISHED = 0;
	const STATUS_HIDDEN    = 1;
	const STATUS_FLAGGED   = 3;

	/**
	 * @return \Aqua\Content\ContentData
	 */
	public function content()
	{
		return $this->contentType->get($this->contentId);
	}

	/**
	 * @return \Aqua\User\Account
	 */
	public function author()
	{
		return Account::get($this->authorId);
	}

	/**
	 * @return \Aqua\UI\Tag|string
	 */
	public function authorDisplay()
	{
		if($this->anonymous) {
			return __('comment', 'anonymous');
		} else {
			return $this->author()->display();
		}
	}

	/**
	 * @param int $size
	 * @return string
	 */
	public function authorAvatar($size = null)
	{
		if($this->anonymous) {
			$path = App::settings()->get('account')->get('default_avatar', '');
			if($path) {
				return \Aqua\URL . $path;
			} else {
				return \Aqua\BLANK;
			}
		} else {
			return $this->author()->avatar($size);
		}
	}

	/**
	 * @return \Aqua\User\Account
	 */
	public function lastEditor()
	{
		return Account::get($this->lastEditorId);
	}

	/**
	 * @return \Aqua\UI\Tag|string
	 */
	public function lastEditorDisplay()
	{
		if($this->anonymous && $this->lastEditorId === $this->authorId) {
			return __('comment', 'anonymous');
		} else {
			return $this->lastEditor()->display();
		}
	}

	/**
	 * @param int $size
	 * @return string
	 */
	public function lastEditorAvatar($size = null)
	{
		if($this->anonymous && $this->lastEditorId === $this->authorId) {
			$path = App::settings()->get('account')->get('default_avatar', '');
			if($path) {
				return \Aqua\URL . $path;
			} else {
				return \Aqua\BLANK;
			}
		} else {
			return $this->lastEditor()->avatar($size);
		}
	}

	/**
	 * @param string $format
	 * @return string
	 */
	public function publishDate($format)
	{
		return strftime($format, $this->publishDate);
	}

	/**
	 * @param string $format
	 * @return string
	 */
	public function editDate($format)
	{
		return strftime($format, $this->editDate);
	}

	/**
	 * @return string
	 */
	public function status()
	{
		return __('comment-status', $this->status);
	}

	/**
	 * @param string $key
	 * @param mixed  $default
	 * @return mixed
	 */
	public function getMeta($key, $default = null)
	{
		$this->_metaLoaded or $this->loadMeta();
		if(array_key_exists($key, $this->meta)) {
			return $this->meta[$key];
		} else {
			return $default;
		}
	}

	/**
	 * @param array|string $key
	 * @param string       $value
	 * @return \Aqua\Content\Filter\CommentFilter\Comment
	 */
	public function setMeta($key, $value = null)
	{
		if(!is_array($key)) $key = array( $key => $value );
		$tbl = ac_table('comment_meta');
		$sth = App::connection()->prepare("
		INSERT INTO `$tbl` (_comment_id, _key, _val)
		VALUES (:id, :key, :val)
		ON DUPLICATE KEY UPDATE _val = :val
		");
		foreach($key as $k => $val) {
			$sth->bindValue(':id', $this->id, \PDO::PARAM_INT);
			$sth->bindValue(':key', $k, \PDO::PARAM_STR);
			$sth->bindValue(':val', $val, \PDO::PARAM_LOB);
			$sth->execute();
		}
		if($this->_metaLoaded) $this->meta = array_merge($this->meta, $key);
		return $this;
	}

	/**
	 * @param string|array $key
	 * @return \Aqua\Content\Filter\CommentFilter\Comment
	 */
	public function deleteMeta($key)
	{
		if(!is_array($key)) $key = array( $key );
		$tbl = ac_table('comment_meta');
		$sth = App::connection()->prepare("
		DELETE FROM `$tbl`
		WHERE _comment_id = ? AND _key = ?
		");
		foreach($key as $k) {
			$sth->bindValue(1, $this->id, \PDO::PARAM_INT);
			$sth->bindValue(2, $k, \PDO::PARAM_STR);
			$sth->execute();
			if($this->_metaLoaded) unset($this->meta[$k]);
		}
		return $this;
	}

	/**
	 * @return \Aqua\Content\Filter\CommentFilter\Comment
	 */
	public function loadMeta()
	{
		$tbl = ac_table('comment_meta');
		$sth = App::connection()->prepare("
		SELECT _key, _val
		FROM `$tbl`
		WHERE _comment_id = ?
		");
		$sth->bindValue(1, $this->id, \PDO::PARAM_INT);
		$sth->execute();
		$this->meta = array();
		while($data = $sth->fetch(\PDO::FETCH_NUM)) {
			$this->meta[$data[0]] = $data[1];
		}
		$this->_metaLoaded = true;
		return $this;
	}

	/**
	 * @param array $edit
	 * @return bool
	 */
	public function update(array $edit)
	{
		return $this->content()->updateComment($this, $edit);
	}

	public function rate(Account $user, $weight)
	{
		return $this->contentType->rateComment($this, $user, $weight);
	}
}
