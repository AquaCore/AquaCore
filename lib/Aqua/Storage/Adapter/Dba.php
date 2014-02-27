<?php
namespace Aqua\Storage\Adapter;

use Aqua\Storage\Exception\StorageException;
use Aqua\Storage\FlushableStorageInterface;
use Aqua\Storage\FlushPrefixStorageInterface;
use Aqua\Storage\GCStorageInterface;
use Aqua\Storage\NumberStorageInterface;
use Aqua\Storage\OptimizableStorageInterface;
use Aqua\Storage\StorageInterface;

class Dba
implements StorageInterface,
           NumberStorageInterface,
           FlushableStorageInterface,
           FlushPrefixStorageInterface,
           OptimizableStorageInterface,
           GCStorageInterface
{
	/**
	 * @var resource
	 */
	public $dba;
	/**
	 * @var string
	 */
	public $prefix = '';
	/**
	 * @var int
	 */
	public $serializer;
	/**
	 * @var string
	 */
	public $handler;
	/**
	 * @var string
	 */
	public $file;
	/**
	 * @var bool
	 */
	public $persistent = false;

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
		if(!extension_loaded('dba')) {
			throw new StorageException(
				__('exception', 'missing-extension', __CLASS__, 'dba'),
				StorageException::MISSING_EXTENSION
			);
		}
		foreach($options as $opt => $value) {
			$this->setOption($opt, $value);
		}
		if($this->persistent) {
			$this->dba = dba_popen($this->file, 'c', $this->handler);
		} else {
			$this->dba = dba_open($this->file, 'c', $this->handler);
		}
		if(!$this->dba) {
			throw new StorageException;
		}
	}

	public function __destruct()
	{
		if(is_resource($this->dba)) {
			dba_close($this->dba);
		}
	}

	/**
	 * @param string $option
	 * @param mixed  $value
	 * @return bool
	 */
	public function setOption($option, $value = null)
	{
		switch($option) {
			case 'persistent':
				$this->persistent = (bool)$value;
				break;
			case 'handler':
				$this->handler = $value;
				break;
			case 'file':
				$this->file = $value;
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
			case 'persistent':
				return $this->persistent;
			case 'handler':
				return $this->handler;
			case 'file':
				return $this->file;
			default:
				return null;
		}
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function exists($key)
	{
		return (dba_exists($this->prefix . $key, $this->dba) && $this->_fetch($key));
	}

	/**
	 * @param string $key
	 * @param mixed  $default
	 * @return mixed
	 */
	public function fetch($key, $default = null)
	{
		if($this->_fetch($this->prefix . $key, $meta, $value)) {
			return $value;
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
		if($this->exists($key)) {
			return false;
		} else {
			return $this->store($key, $value, $ttl);
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
		$ttl = (int)$ttl;
		$ttl = ($ttl === 0 ? 0 : time() + $ttl);
		if(is_int($value)) {
			$type = 'i';
		} else if(is_float($value)) {
			$type = 'f';
		} else if(is_string($value)) {
			$type = 's';
		} else {
			$value = $this->_serialize($value);
			$type  = 'x';
		}
		if($this->exists($key)) {
			return dba_replace(
				$this->prefix . $key,
				json_encode(array( 'type' => $type, 'ttl' => $ttl )) . "\r\n\r\n$value",
				$this->dba
			);
		} else {
			return dba_insert(
				$this->prefix . $key,
				json_encode(array( 'type' => $type, 'ttl' => $ttl )) . "\r\n\r\n$value",
				$this->dba
			);
		}
	}

	/**
	 * @param string    $key
	 * @param int|float $step
	 * @param int|float $default
	 * @param int       $ttl
	 * @return bool|int|float
	 */
	public function increment($key, $step = 0, $default = 0, $ttl = 0)
	{
		if($this->_fetch($key, $meta, $value)) {
			$value += $step;
			if(is_float($value)) {
				$meta['type'] = 'f';
			}
			if(dba_replace($this->prefix . $key, json_encode($meta) . "\r\n\r\n$value", $this->dba)) {
				return $value;
			} else {
				return false;
			}
		} else {
			$value = $default + $step;
			if($this->store($key, $value, $ttl)) {
				return $value;
			} else {
				return false;
			}
		}
	}

	/**
	 * @param string    $key
	 * @param int|float $step
	 * @param int|float $default
	 * @param int       $ttl
	 * @return bool|int|float
	 */
	public function decrement($key, $step = 0, $default = 0, $ttl = 0)
	{
		return $this->increment($key, -$step, $default, $ttl);
	}

	/**
	 * @param string|array $key
	 * @return array|bool
	 */
	public function delete($key)
	{
		if(is_array($key)) {
			$keys = array();
			foreach($key as $k) {
				if(dba_delete($this->prefix . $k, $this->dba)) {
					$keys[] = $this->prefix . $k;
				}
			}

			return $keys;
		}

		return dba_delete($this->prefix . $key, $this->dba);
	}

	/**
	 * @return array|bool
	 */
	public function flush()
	{
		if($this->prefix) {
			return $this->flushPrefix($this->prefix);
		} else {
			return unlink($this->file);
		}
	}

	/**
	 * @param string $prefix
	 * @return array|bool
	 */
	public function flushPrefix($prefix)
	{
		$prefix  = $this->prefix . $prefix;
		$len     = strlen($prefix);
		$deleted = array();
		for($key = dba_firstkey($this->dba); $key !== false; $key = dba_nextkey($this->dba)) {
			if(substr($key, 0, $len) === $prefix) {
				if(dba_delete($key, $this->dba)) {
					$deleted[] = $key;
				}
			}
		}

		return (empty($deleted) ? false : $deleted);
	}

	/**
	 * @return bool
	 */
	public function optimize()
	{
		return dba_optimize($this->dba);
	}

	/**
	 * @return array|bool
	 */
	public function gc()
	{
		$keys = array();
		for($key = dba_firstkey($this->dba); $key !== false; dba_nextkey($this->dba)) {
			if(!$this->_fetch($key, $meta)) {
				$keys[] = $key;
			}
		}

		return $this->delete($keys);
	}

	/**
	 * @param string $data
	 * @return string
	 */
	protected function _serialize($data)
	{
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
		switch($this->serializer) {
			default:
			case self::SERIALIZER_NONE:
				return $data;
			case self::SERIALIZER_PHP:
				return unserialize($data);
			case self::SERIALIZER_JSON:
				return json_decode($data);
			case self::SERIALIZER_IGBINARY:
				return igbinary_unserialize($data);
		}
	}

	/**
	 * @param string $key
	 * @param        $meta
	 * @param        $value
	 * @return bool
	 */
	protected function _fetch($key, &$meta = null, &$value = null)
	{
		if(($data = dba_fetch($this->prefix . $key, $this->dba)) === false) {
			return false;
		}
		$data = explode("\r\n\r\n", $data, 2);
		if(count($data) !== 2) {
			return false;
		}
		list($meta, $value) = $data;
		$meta = json_decode($meta, true);
		if(json_last_error() || ($meta['ttl'] && $meta['ttl'] > time())) {
			return false;
		}
		switch($meta['type']) {
			case 'i':
				$value = intval($value);
				break;
			case 'f':
				$value = floatval($value);
				break;
			case 'x':
				$value = $this->_unserialize($value);
				break;
		}

		return true;
	}
}
