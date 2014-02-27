<?php
namespace Aqua\Ragnarok\Server\Logs;

use Aqua\Ragnarok\Server\Login;

class PasswordResetLog
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
	 * @var string
	 */
	public $ipAddress;

	/**
	 * @var string
	 */
	public $key;

	/**
	 * @var int
	 */
	public $requestDate;

	/**
	 * @var int
	 */
	public $resetDate;

	public function account()
	{
		return $this->login->get($this->accountId);
	}

	public function requestDate($format)
	{
		return strftime($format, $this->requestDate);
	}

	public function resetDate($format)
	{
		return strftime($format, $this->resetDate);
	}
}
