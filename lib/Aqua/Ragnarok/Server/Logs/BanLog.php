<?php
namespace Aqua\Ragnarok\Server\Logs;

use Aqua\User\Account;

class BanLog
{
	/**
	 * @var \Aqua\Ragnarok\Server\Login
	 */
	public $login;

	/**
	 * @var int
	 */
	public $id;

	/**
	 * @var int
	 */
	public $accountId;

	/**
	 * @var int
	 */
	public $bannedId;

	/**
	 * @var int
	 */
	public $type;

	/**
	 * @var int
	 */
	public $date;

	/**
	 * @var int
	 */
	public $unbanDate;

	/**
	 * @var string
	 */
	public $reason;

	const TYPE_PERMANENT = 1;
	const TYPE_TEMPORARY = 2;
	const TYPE_UNBAN = 3;

	public function account()
	{
		return Account::get($this->accountId);
	}

	public function banned()
	{
		return $this->login->get($this->bannedId);
	}

	public function banDate($format)
	{
		return strftime($format, $this->date);
	}

	public function unbanDate($format)
	{
		return strftime($format, $this->unbanDate);
	}

	public function type()
	{
		return __('ragnarok-ban-type', $this->type);
	}
}
