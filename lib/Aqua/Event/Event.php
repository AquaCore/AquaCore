<?php
namespace Aqua\Event;

class Event
{
	/**
	 * @var \Aqua\Event\EventDispatcher|null
	 */
	public static $dispatcher;

	/**
	 * @param string   $event
	 * @param callable $listener
	 */
	public static function bind($event, \Closure $listener)
	{
		self::dispatcher()->attach($event, $listener);
	}

	/**
	 * @param string   $event
	 * @param callable $listener
	 */
	public static function unbind($event, \Closure $listener)
	{
		self::dispatcher()->detach($event, $listener);
	}

	/**
	 * @param string $event
	 * @param array  $feedback
	 * @return mixed
	 */
	public static function fire($event, &$feedback = null)
	{
		return self::dispatcher()->notify($event, $feedback);
	}

	/**
	 * @return \Aqua\Event\EventDispatcher
	 */
	public static function dispatcher()
	{
		if(!self::$dispatcher) {
			self::$dispatcher = new EventDispatcher;
		}

		return self::$dispatcher;
	}
}
