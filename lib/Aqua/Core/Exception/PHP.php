<?php
namespace Aqua\Core\Exception;

class PHP
extends \Exception
{
	public static function errorHandler($code, $message, $file, $line)
	{
		$e = new self($message, $code);
		$e->file = $file;
		$e->line = $line;
		if(error_reporting() & $code) {
			throw $e;
		}
	}

	public static function report($level = E_ALL)
	{
		error_reporting(E_ALL & $level);
	}

	public static function suppress($level = E_ALL)
	{
		self::report(~$level);
	}

	public static function handleErrors()
	{
		set_error_handler(array(__CLASS__, 'errorHandler'));
	}

	public static function restoreErrorHandler()
	{
		restore_error_handler();
	}
}
