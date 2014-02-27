<?php
namespace Aqua\Ragnarok\Server\Logs;

use Aqua\Ragnarok\Server\Login;

class LoginLog
{
	/**
	 * @var \Aqua\Ragnarok\Server\Login
	 */
	public $login;

	/**
	 * @var int
	 */
	public $username;

	/**
	 * @var int
	 */
	public $date;

	/**
	 * @var string
	 */
	public $ipAddress;

	/**
	 * @var int
	 */
	public $code;

	/**
	 * @var string
	 */
	public $message;

	public function date($format)
	{
		return strftime($format, $this->date);
	}

	public function response()
	{
		return __('ragnarok-login-response', $this->code);
	}
}
