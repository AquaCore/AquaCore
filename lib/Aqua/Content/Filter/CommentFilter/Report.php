<?php
namespace Aqua\Content\Filter\CommentFilter;

use Aqua\User\Account;

class Report
{
	/**
	 * @var int
	 */
	public $id;
	/**
	 * @var int
	 */
	public $commentId;
	/**
	 * @var int
	 */
	public $userId;
	/**
	 * @var int
	 */
	public $date;
	/**
	 * @var string
	 */
	public $ipAddress;
	/**
	 * @var string
	 */
	public $report;

	public function user()
	{
		return Account::get($this->userId, 'id');
	}

	public function date($format)
	{
		return strftime($format, $this->date);
	}
}
 