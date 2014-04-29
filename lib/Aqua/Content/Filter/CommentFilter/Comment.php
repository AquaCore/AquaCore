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
	public $reportCount;
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
	 * @var \Aqua\Core\Meta
	 */
	public $meta;

	const STATUS_PUBLISHED = 0;
	const STATUS_HIDDEN    = 1;
	const STATUS_FLAGGED   = 2;

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

	public function timeElapsedPublishDate()
	{
		return __('time-elapsed', 'ago', ac_time_elapsed($this->editDate));
	}

	/**
	 * @param string $format
	 * @return string
	 */
	public function editDate($format)
	{
		return strftime($format, $this->editDate);
	}

	public function timeElapsedEditDate()
	{
		return __('time-elapsed', 'ago', ac_time_elapsed($this->editDate));
	}

	/**
	 * @return string
	 */
	public function status()
	{
		return __('comment-status', $this->status);
	}

	/**
	 * @return \Aqua\SQL\Search
	 */
	public function reportSearch()
	{
		return $this->contentType
			->filter('CommentFilter')
			->reportSearch()
			->where(array( 'comment_id' => $this->id ));
	}

	/**
	 * @param array $edit
	 * @return bool
	 */
	public function update(array $edit)
	{
		return $this->content()->updateComment($this, $edit);
	}

	public function delete()
	{
		return $this->content()->deleteComment($this);
	}

	public function rate(Account $user, $weight)
	{
		return $this->contentType->rateComment($this, $user, $weight);
	}
}
