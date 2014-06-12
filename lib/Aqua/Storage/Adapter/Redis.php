<?php
namespace Aqua\Storage\Adapter;

use Aqua\Storage\Exception\StorageException;
use Aqua\Storage\FlushableStorageInterface;
use Aqua\Storage\NumberStorageInterface;
use Aqua\Storage\StorageInterface;

/**
 * Class Redis
 *
 * @package Aqua\Storage\Adapter
 */
class Redis
implements StorageInterface,
           FlushableStorageInterface
{
	/**
	 * @var \Redis
	 */
	public $redis;
	/**
	 * @var string
	 */
	public $prefix = '';
	/**
	 * @var int
	 */
	public $serializer = \Redis::SERIALIZER_PHP;
	/**
	 * @var string
	 */
	public $host = '127.0.0.1';
	/**
	 * @var int
	 */
	public $port = 6379;
	/**
	 * @var string|null
	 */
	public $persistentId;
	/**
	 * @var bool
	 */
	public $password = false;
	/**
	 * @var int
	 */
	public $timeout = 0;

	/**
	 * @param array $options
	 * @throws \Aqua\Storage\Exception\StorageException
	 */
	public function __construct(array $options = array())
	{
		if(!extension_loaded('redis') || !class_exists('Redis', false)) {
			throw new StorageException(
				__('exception', 'missing-extension', __CLASS__, 'phpredis'),
				StorageException::MISSING_EXTENSION
			);
		}
		foreach($options as $opt => $value) {
			$this->setOption($opt, $value);
		}
		$this->redis = new \Redis;
		if($this->persistentId !== null) {
			$this->redis->pconnect(
				$this->host,
				$this->port,
				$this->timeout,
				$this->persistentId
			);
		} else {
			$this->redis->connect(
				$this->host,
				$this->port,
				$this->timeout
			);
		}
		if($this->password !== false) {
			$this->redis->auth($this->password);
		}
		$this->redis->setOption(\Redis::OPT_SERIALIZER, $this->serializer);
		$this->redis->setOption(\Redis::OPT_PREFIX, $this->prefix);
	}

	/**
	 * @param string $option
	 * @param mixed   $value
	 * @return bool
	 */
	public function setOption($option, $value = null)
	{
		switch($option) {
			case 'serializer':
			case \Redis::OPT_SERIALIZER:
				$this->serializer = $value;
				break;
			case 'prefix':
			case \Redis::OPT_PREFIX:
				$this->prefix = $value;
				break;
			case 'host':
				$this->host = $value;
				break;
			case 'password':
				$this->password = $value;
				break;
			case 'persistent_id':
				$this->persistentId = $value;
				break;
			case 'port':
				$this->port = (int)$value;
				break;
			case 'timeout':
				$this->timeout = (int)$value;
				break;
		}
		return true;
	}

	/**
	 * @param string $option
	 * @return mixed
	 */
	public function getOption($option)
	{
		switch($option) {
			case 'serializer':
			case \Redis::OPT_SERIALIZER:
				return $this->serializer;
			case 'prefix':
			case \Redis::OPT_PREFIX:
				return $this->prefix;
			default: return null;
		}
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function exists($key)
	{
		return $this->redis->exists($key);
	}

	/**
	 * @param string $key
	 * @param mixed   $default
	 * @return mixed
	 */
	public function fetch($key, $default = null)
	{
		return ($this->exists($key) ? $this->redis->get($key) : $default);
	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 * @param int    $ttl
	 * @return bool
	 */
	public function add($key, $value, $ttl = 0)
	{
		if(is_int($value) || is_float($value)) {
			if($this->exists($key)) {
				return false;
			} else if(is_float($value)) {
				$this->redis->incrByFloat($key, $value);
			} else if($value > 0) {
				$this->redis->incr($key, $value);
			} else {
				$this->redis->decr($key, $value);
			}
			if($ttl) {
				$this->redis->expire($key, $ttl);
			}
			return true;
		} else if($ttl === 0) {
			return $this->redis->setnx($key, $value);
		} else if(!$this->exists($key)) {
			return $this->redis->setex($key, $ttl, $value);
		} else {
			return false;
		}
	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 * @param int    $ttl
	 * @return bool
	 */
	public function store($key, $value, $ttl = 0)
	{
		if(is_int($value) || is_float($value)) {
			if($this->exists($key)) {
				$this->redis->del($key);
			}
			if(is_float($value)) {
				$this->redis->incrByFloat($key, $value);
			} else if($value > 0) {
				$this->redis->incr($key, $value);
			} else {
				$this->redis->decr($key, $value);
			}
			if($ttl) {
				$this->redis->expire($key, $ttl);
			}
			return true;
		} else if($ttl > 0) {
			return $this->redis->setex($key, $ttl, $value);
		} else {
			return $this->redis->set($key, $value);
		}
	}

	/**
	 * @param string    $key
	 * @param int $step
	 * @param int $defaultValue
	 * @param int $ttl
	 * @return bool|int
	 */
	function increment($key, $step = 1, $defaultValue = 0, $ttl = 0)
	{
		if($this->exists($key)) {
			$ttl = null;
		} else {
			$step += $defaultValue;
		}
		if(is_float($step)) {
			$value = $this->redis->incrByFloat($key, $step);
		} else {
			$value = $this->redis->incrBy($key, $step);
		}
		if($ttl) {
			$this->redis->expire($key, $ttl);
		}
		return $value;
	}

	/**
	 * @param string    $key
	 * @param int $step
	 * @param int $defaultValue
	 * @param int $ttl
	 * @return bool|int
	 */
	function decrement($key, $step = 1, $defaultValue = 0, $ttl = 0)
	{
		if($this->exists($key)) {
			$ttl = null;
		} else {
			$step = $defaultValue + $step;
		}
		if(is_float($step)) {
			$value = $this->redis->incrByFloat($key, -$step);
		} else {
			$value = $this->redis->decrBy($key, $step);
		}
		if($ttl) {
			$this->redis->expire($key, $ttl);
		}
		return $value;
	}

	/**
	 * @param string $key
	 * @return array|bool
	 */
	public function delete($key)
	{
		return $this->redis->del($key);
	}

	/**
	 * @return bool
	 */
	public function flush()
	{
		return $this->redis->flushAll();
	}
}
