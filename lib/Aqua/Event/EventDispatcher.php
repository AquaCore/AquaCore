<?php
namespace Aqua\Event;

use Aqua\Core\Exception\InvalidArgumentException;

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
	 * @param callable $listener
	 * @return mixed
	 * @throws \Aqua\Core\Exception\InvalidArgumentException
	 */
	public function eventContains($event, $listener)
	{
		if(!is_callable($listener)) {
			throw new InvalidArgumentException(1, 'callable', $listener);
		}
		return array_search($this->getHash($listener), $this->events[$event]);
	}

	/**
	 * @param callable $listener
	 * @return array
	 * @throws \Aqua\Core\Exception\InvalidArgumentException
	 */
	public function listenerEvents($listener)
	{
		if(!is_callable($listener)) {
			throw new InvalidArgumentException(1, 'callable', $listener);
		}
		$events = array();
		$hash   = $this->getHash($listener);
		foreach($this->events as $event => $listeners) {
			if(array_search($hash, $listeners) !== false) {
				$events[] = $event;
			}
		}

		return $events;
	}

	/**
	 * @param callable $listener
	 * @return bool
	 * @throws \Aqua\Core\Exception\InvalidArgumentException
	 */
	public function contains($listener)
	{
		if(!is_callable($listener)) {
			throw new InvalidArgumentException(1, 'callable', $listener);
		}
		return isset($this->listeners[$this->getHash($listener)]);
	}

	/**
	 * @param string   $event
	 * @param callable $listener
	 * @return \Aqua\Event\EventDispatcher
	 * @throws \Aqua\Core\Exception\InvalidArgumentException
	 */
	public function attach($event, $listener)
	{
		if(!is_callable($listener)) {
			throw new InvalidArgumentException(2, 'callable', $listener);
		}
		$hash = $this->getHash($listener);
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
	 * @param callable $listener
	 * @return \Aqua\Event\EventDispatcher
	 * @throws \Aqua\Core\Exception\InvalidArgumentException
	 */
	public function detach($event, $listener)
	{
		if(!is_callable($listener)) {
			throw new InvalidArgumentException(2, 'callable', $listener);
		}
		$hash = $this->getHash($listener);
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

	public function getHash($listener)
	{
		if(is_object($listener)) {
			return 'o::' . spl_object_hash($listener);
		} else if(is_string($listener)) {
			return 's::' . $listener;
		} else if(is_array($listener)) {
			if(is_object($listener[0])) {
				return 'o::' . spl_object_hash($listener[0]) . '->' . $listener[1];
			} else {
				return 's::' . $listener[0] . '::' . $listener[1];
			}
		}
		return null;
	}
}
