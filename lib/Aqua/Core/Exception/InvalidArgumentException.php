<?php
namespace Aqua\Core\Exception;

class InvalidArgumentException
extends \InvalidArgumentException
{
	public function __construct($number, $expected, $given, \Exception $previous = null)
	{
		$trace = debug_backtrace();
		$trace = next($trace) + array(
				'function' => null,
		        'class'    => null,
		        'type'     => '::',
			);
		if(isset($trace['class'])) {
			$function = $trace['class'];
			$function.= $trace['type'];
			$function.= $trace['function'];
		} else {
			$function = $trace['function'];
		}
		if(is_object($given)) {
			$type = get_class($given);
		} else {
			$type = gettype($given);
		}
		if(is_array($expected)) {
			if(count($expected) > 1) {
				$last = array_pop($expected);
				$expected = '"' . implode('", "', $expected) . '"';
				$expected.= ' ' . __('application', 'or') . ' "' . $last . '"';
			} else {
				$expected = '"' . current($expected) . '"';
			}
		} else {
			$expected = "\"$expected\"";
		}
		parent::__construct(__('exception', 'invalid-argument', $function, $number, $expected, $type), 0, $previous);
	}
}
 