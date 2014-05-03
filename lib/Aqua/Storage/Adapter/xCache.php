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
	 * @var int
	 */
	public $serializer = self::SERIALIZER_PHP;


	const SERIALIZER_NONE     = 0;
	const SERIALIZER_PHP      = 1;
	const SERIALIZER_JSON     = 2;
	const SERIALIZER_IGBINARY = 3;

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
		} else if($option === 'serializer') {
			$this->serializer = (int)$value;
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
		} else if($option === 'serializer') {
			return $this->serializer;
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
		if(\xcache_isset($key)) {
			return $this->_unserialize(\xcache_get($key));
		} else {
			return $default;
		}
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
		if(\xcache_isset($key)) {
			return false;
		} else {
			return \xcache_set($key, $this->_serialize($value), $ttl);
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
		return \xcache_set($this->prefix . $key, $this->_serialize($value), $ttl);
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
		} else {
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
		if(!\xcache_isset($key)) {
			$value = $defaultValue + $step;
			return (\xcache_set($key, $value, $ttl) ? $value : false);
		} else {
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
		} else {
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

	/**
	 * @param string $data
	 * @return string
	 */
	protected function _serialize($data)
	{
		if(is_int($data) || is_float($data)) {
			return $data;
		}
		switch($this->serializer) {
			default:
			case self::SERIALIZER_NONE:
				return (string)$data;
			case self::SERIALIZER_PHP:
				return serialize($data);
			case self::SERIALIZER_JSON:
				return json_encode($data);
			case self::SERIALIZER_IGBINARY:
				return igbinary_serialize($data);
		}
	}

	/**
	 * @param string $data
	 * @return mixed
	 */
	protected function _unserialize($data)
	{
		if(is_int($data) || is_float($data)) {
			return $data;
		}
		switch($this->serializer) {
			default:
			case self::SERIALIZER_NONE:
				return $data;
			case self::SERIALIZER_PHP:
				return unserialize($data);
			case self::SERIALIZER_JSON:
				return json_decode($data, true);
			case self::SERIALIZER_IGBINARY:
				return igbinary_unserialize($data);
		}
	}
}
