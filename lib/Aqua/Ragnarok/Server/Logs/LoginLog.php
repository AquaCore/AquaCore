<?php
namespace Aqua\Ragnarok\Server\Logs;

class LoginLog
{
	/**
	 * @var \Aqua\Ragnarok\Server\Login
	 */
	public $login;
	/**
	 * Input username
	 *
	 * @var int
	 */
	public $username;
	/**
	 * Login timestamp
	 *
	 * @var int
	 */
	public $date;
	/**
	 * Request IP address
	 *
	 * @var string
	 */
	public $ipAddress;
	/**
	 * Response code
	 *
	 * @var int
	 */
	public $code;
	/**
	 * Full response message
	 *
	 * @var string
	 */
	public $message;

	/**
	 * Format the login date
	 *
	 * @param string $format
	 * @return string
	 */
	public function date($format)
	{
		return strftime($format, $this->date);
	}

	/**
	 * Returns the response type according to the response code. ("Successful", "Banned", ...)
	 *
	 * @return string
	 */
	public function response()
	{
		return __('ragnarok-login-response', $this->code);
	}
}
