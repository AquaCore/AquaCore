<?php
namespace Aqua\Core\Exception;

class Assertion
extends \Exception
{
	public static function assertCallback($file, $line, $script, $message)
	{
		$message = __('exception', 'assertion', $script, $message);
		$e = new self($message);
		$e->file = $file;
		$e->line = $line;
		throw $e;
	}

	public static function enable()
	{
		assert_options(ASSERT_ACTIVE, true);
		assert_options(ASSERT_WARNING, false);
		assert_options(ASSERT_BAIL, true);
		assert_options(ASSERT_QUIET_EVAL, false);
		assert_options(ASSERT_CALLBACK, array(__CLASS__, 'assertCallback'));
	}

	public static function disable()
	{
		assert_options(ASSERT_ACTIVE, false);
	}
}
