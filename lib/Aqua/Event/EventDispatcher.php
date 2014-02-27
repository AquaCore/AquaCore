<?php
namespace Aqua\Event;

class EventDispatcher
implements \Countable
{
	/**
	 * @var array
	 */
	public $events = array();
	/**
	 * Array of attached listeners
	 *
	 * @var \Closure[]
	 */
	public $listeners = array();
	/**
	 * @var int
	 */
	public $size = 0;

	/**
	 * @return int
	 */
	public function count()
	{
		return $this->size;
	}

	/**
	 * @return array
	 */
	public function events()
	{
		return array_keys($this->events);
	}

	/**
	 * @param string $event
	 * @return bool
	 */
	public function eventRegistered($event)
	{
		return isset($this->events[$event]);
	}

	/**
	 * @param string   $event
	 * @param \Closure $listener
	 * @return mixed
	 */
	public function eventContains($event, \Closure $listener)
	{
		return array_search(spl_object_hash($listener), $this->events[$event]);
	}

	/**
	 * @param \Closure $listener
	 * @return array
	 */
	public function listenerEvents(\Closure $listener)
	{
		$events = array();
		$hash   = spl_object_hash($listener);
		foreach($this->events as $event => $listeners) {
			if(array_search($hash, $listeners) !== false) {
				$events[] = $event;
			}
		}

		return $events;
	}

	/**
	 * @param \Closure $listener
	 * @return bool
	 */
	public function contains(\Closure $listener)
	{
		return isset($this->listeners[spl_object_hash($listener)]);
	}

	/**
	 * @param string   $event
	 * @param \Closure $listener
	 * @return \Aqua\Event\EventDispatcher
	 */
	public function attach($event, \Closure $listener)
	{
		$hash = spl_object_hash($listener);
		if(isset($this->events[$event])) {
			$this->events[$event][] = $hash;
		} else {
			if(!isset($this->events[$event][$hash])) {
				$this->events[$event] = array( $hash );
			}
		}
		if(!isset($this->listeners[$hash])) {
			$this->listeners[$hash] = $listener;
			++$this->size;
		}

		return $this;
	}

	/**
	 * @param string   $event
	 * @param \Closure $listener
	 * @return \Aqua\Event\EventDispatcher
	 */
	public function detach($event, \Closure $listener)
	{
		$hash = spl_object_hash($listener);
		if(!$event) {
			foreach($this->events as $event => $listeners) {
				if(array_search($hash, $listeners) !== false) {
					unset($this->events[$event][$hash]);
				}
			}
			unset($this->listeners[$hash]);
			--$this->size;
		} else {
			if($this->eventRegistered($event) && ($key = array_search($hash, $this->events[$event]))) {
				unset($this->events[$event][$key]);
				$events = $this->listenerEvents($listener);
				if(empty($events)) {
					unset($this->listeners[$hash]);
					--$this->size;
				}
			}
		}

		return $this;
	}

	/**
	 * @param string $event
	 * @param mixed  $feedback
	 * @return mixed
	 */
	public function notify($event, &$feedback = null)
	{
		$ret = null;
		if($this->eventRegistered($event)) {
			if(is_array($feedback)) {
				array_unshift($feedback, $event);
			} else {
				$feedback = array( $event );
			}
			foreach($this->events[$event] as $hash) {
				if(isset($this->listeners[$hash])) {
					$ret = call_user_func_array($this->listeners[$hash], $feedback);
					if($ret === false) {
						break;
					}
				}
			}
			array_shift($feedback);
		}

		return $ret;
	}
}
