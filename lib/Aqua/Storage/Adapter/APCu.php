<?php
namespace Aqua\Storage\Adapter;

use Aqua\Storage\Exception\StorageException;

class APCu
extends APC
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
		if(!extension_loaded('apcu')) {
			throw new StorageException(
				__('exception', 'missing-extension', __CLASS__, 'apcu'),
				StorageException::MISSING_EXTENSION
			);
		}
		$options += array( 'prefix' => '' );
		$this->prefix = $options['prefix'];
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function exists($key)
	{
		return \apcu_exists($this->prefix . $key);
	}

	/**
	 * @param string  $key
	 * @param mixed   $default
	 * @return mixed
	 */
	public function fetch($key, $default = null)
	{
		$value = \apcu_fetch($this->prefix . $key, $success);

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
		return \apcu_add($this->prefix . $key, $value, $ttl);
	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 * @param int    $ttl
	 * @return bool
	 */
	public function store($key, $value, $ttl = 0)
	{
		return \apcu_store($this->prefix . $key, $value, $ttl);
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
		}
		else {
			$key = $this->prefix . $key;
		}

		return \apcu_delete($key);
	}

	/**
	 * @param string $key
	 * @param int    $step
	 * @param int    $defaultValue
	 * @param int    $ttl
	 * @return bool|int
	 */
	public function increment($key, $step = 1, $defaultValue = 0, $ttl = 0)
	{
		if($this->exists($key)) {
			return \apcu_inc($key, $step);
		}
		else {
			$value = $defaultValue + $step;

			return ($this->add($key, $value, $ttl) ? $value : false);
		}
	}

	/**
	 * @param string $key
	 * @param int    $step
	 * @param int    $defaultValue
	 * @param int    $ttl
	 * @return bool|int
	 */
	public function decrement($key, $step = 1, $defaultValue = 0, $ttl = 0)
	{
		if($this->exists($key)) {
			return \apcu_dec($key, $step);
		}
		else {
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
			return \apcu_clear_cache('user');
		}
		else {
			$deletedKeys = $this->flushPrefix('');

			return !empty($deletedKeys);
		}
	}
}
