<?php
namespace Aqua\Storage\Adapter;

use Aqua\Storage\Exception\StorageException;
use Aqua\Storage\StorageInterface;
use Aqua\Storage\FlushPrefixStorageInterface;
use Aqua\Storage\FlushableStorageInterface;

class APC
implements StorageInterface,
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
		if(!extension_loaded('apc') || !ini_get('apc.enabled')) {
			throw new StorageException(
				__('exception', 'missing-extension', __CLASS__, 'apc'),
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
		return \apc_exists($this->prefix . $key);
	}

	/**
	 * @param string  $key
	 * @param mixed   $default
	 * @return mixed
	 */
	public function fetch($key, $default = null)
	{
		$value = \apc_fetch($this->prefix . $key, $success);

		return ($success ? $value : $default);
	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 * @param int    $ttl
	 * @return bool
	 */
	public function add($key, $value, $ttl = 0)
	{
		return \apc_add($this->prefix . $key, $value, $ttl);
	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 * @param int    $ttl
	 * @return bool
	 */
	public function store($key, $value, $ttl = 0)
	{
		return \apc_store($this->prefix . $key, $value, $ttl);
	}

	/**
	 * @param string $key
	 * @return array|bool|\string[]
	 */
	public function delete($key)
	{
		if(is_array($key)) {
			foreach($key as &$k) {
				$k = $this->prefix . $k;
			}
		} else {
			$key = $this->prefix . $key;
		}

		return \apc_delete($key);
	}

	/**
	 * @param string    $key
	 * @param int       $step
	 * @param int       $defaultValue
	 * @param int       $ttl
	 * @return bool|int
	 */
	public function increment($key, $step = 1, $defaultValue = 0, $ttl = 0)
	{
		if($this->exists($key)) {
			return \apc_inc($this->prefix . $key, $step);
		} else {
			$value = $defaultValue + $step;

			return ($this->add($key, $value, $ttl) ? $value : false);
		}
	}

	/**
	 * @param string    $key
	 * @param int       $step
	 * @param int       $defaultValue
	 * @param int       $ttl
	 * @return bool|int
	 */
	public function decrement($key, $step = 1, $defaultValue = 0, $ttl = 0)
	{
		if($this->exists($key)) {
			return \apc_dec($this->prefix . $key, $step);
		} else {
			$value = $defaultValue - $step;

			return ($this->add($key, $value, $ttl) ? $value : false);
		}
	}

	/**
	 * @return bool
	 */
	public function flush()
	{
		if(!$this->prefix) {
			return \apc_clear_cache('user');
		}
		else {
			$deletedKeys = $this->flushPrefix('');

			return !empty($deletedKeys);
		}
	}

	/**
	 * @param string $prefix
	 * @return array
	 */
	public function flushPrefix($prefix)
	{
		$deletedKeys = array();
		$regex       = preg_quote($this->prefix . $prefix, '/');
		foreach($this->_iterator("/^$regex/") as $key) {
			\apc_delete($key);
			$deletedKeys[] = $key;
		}

		return $deletedKeys;
	}

	protected function _iterator($search)
	{
		return new \APCIterator('user', $search, \APC_ITER_KEY);
	}
}
