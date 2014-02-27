<?php
namespace Aqua\Storage\Adapter;

use Aqua\Storage\Exception\StorageException;
use Aqua\Storage\StorageInterface;
use Aqua\Storage\NumberStorageInterface;
use Aqua\Storage\FlushPrefixStorageInterface;
use Aqua\Storage\FlushableStorageInterface;

class xCache
implements StorageInterface,
           NumberStorageInterface,
           FlushableStorageInterface,
           FlushPrefixStorageInterface
{
	/**
	 * @var string
	 */
	public $prefix = '';

	/**
	 * @param array $options
	 * @throws \Aqua\Storage\Exception\StorageException
	 */
	public function __construct(array $options = array())
	{
		if(!extension_loaded('xcache')) {
			throw new StorageException(
				__('exception', 'missing-extension', __CLASS__, 'xcache'),
				StorageException::MISSING_EXTENSION
			);
		}
		$options += array( 'prefix' => '' );
		$this->prefix = $options['prefix'];
	}

	/**
	 * @param string $option
	 * @param mixed  $value
	 * @return bool
	 */
	public function setOption($option, $value)
	{
		if($option === 'prefix') {
			$this->prefix = (string)$value;
		}

		return true;
	}

	/**
	 * @param string $option
	 * @return mixed
	 */
	public function getOption($option)
	{
		if($option === 'prefix') {
			return $this->prefix;
		}

		return null;
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function exists($key)
	{
		return \xcache_isset($this->prefix . $key);
	}

	/**
	 * @param string $key
	 * @param mixed  $default
	 * @return mixed
	 */
	public function fetch($key, $default = null)
	{
		$key = $this->prefix . $key;

		return (\xcache_isset($key) ? \xcache_get($key) : $default);
	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 * @param int    $ttl
	 * @return bool
	 */
	public function add($key, $value, $ttl = 0)
	{
		$key = $this->prefix . $key;

		return (!\xcache_isset($key) ? \xcache_set($key, $value, $ttl) : false);
	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 * @param int    $ttl
	 * @return bool
	 */
	public function store($key, $value, $ttl = 0)
	{
		return \xcache_set($this->prefix . $key, $value, $ttl);
	}

	/**
	 * @param string $key
	 * @return array|bool
	 */
	public function delete($key)
	{
		if(is_array($key)) {
			$status = true;
			foreach($key as &$k) {
				if(!\xcache_unset($this->prefix . $k)) {
					$status = false;
				}
			}

			return $status;
		}
		else {
			return \xcache_unset($this->prefix . $key);
		}
	}

	/**
	 * @param string    $key
	 * @param int       $step
	 * @param int       $defaultValue
	 * @param int       $ttl
	 * @return int
	 */
	public function increment($key, $step = 1, $defaultValue = 0, $ttl = 0)
	{
		$key = $this->prefix . $key;
		if($defaultValue !== 0 && !\xcache_isset($key)) {
			$v = $defaultValue + $step;
			\xcache_set($key, $v, $ttl);

			return $v;
		}
		else {
			return \xcache_inc($key, $step, $ttl);
		}
	}

	/**
	 * @param string    $key
	 * @param int       $step
	 * @param int       $defaultValue
	 * @param int       $ttl
	 * @return int
	 */
	public function decrement($key, $step = 1, $defaultValue = 0, $ttl = 0)
	{
		$key = $this->prefix . $key;
		if($defaultValue !== 0 && !\xcache_isset($key)) {
			$v = $defaultValue - $step;
			\xcache_set($key, $defaultValue - $step, $ttl);

			return $v;
		}
		else {
			return \xcache_dec($key, $step, $ttl);
		}
	}

	/**
	 * @return bool
	 */
	public function flush()
	{
		return \xcache_unset_by_prefix($this->prefix);
	}

	/**
	 * @param string $prefix
	 * @return bool
	 */
	public function flushPrefix($prefix)
	{
		return \xcache_unset_by_prefix($this->prefix . $prefix);
	}
}
