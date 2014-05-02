<?php
namespace Aqua\Storage\Adapter;

use Aqua\Core\Exception\FileSystemException;
use Aqua\Storage\Exception\StorageException;
use Aqua\Storage\FlushableStorageInterface;
use Aqua\Storage\FlushPrefixStorageInterface;
use Aqua\Storage\GCStorageInterface;
use Aqua\Storage\NumberStorageInterface;
use Aqua\Storage\StorageInterface;

class File
implements StorageInterface,
           FlushableStorageInterface,
           FlushPrefixStorageInterface,
           NumberStorageInterface,
	       GCStorageInterface
{
	/**
	 * @var string
	 */
	public $prefix = '';
	/**
	 * @var string
	 */
	public $directory;
	/**
	 * @var string
	 */
	public $extension = 'dat';
	/**
	 * @var int
	 */
	public $permission = \Aqua\PRIVATE_FILE_PERMISSION;
	/**
	 * @var bool
	 */
	public $lock = true;
	/**
	 * @var string
	 */
	public $hash = 'md5';
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
	 */
	public function __construct(array $options)
	{
		$this->directory = \Aqua\ROOT . '/tmp';
		foreach($options as $option => $value) {
			$this->setOption($option, $value);
		}
		if(isset($options['gc_probability'])) {
			if(ac_probability($options['gc_probability'])) {
				$this->gc();
			}
		}
	}

	/**
	 * @param string $option
	 * @param mixed  $value
	 * @return bool
	 * @throws \Aqua\Storage\Exception\StorageException
	 * @throws \Aqua\Storage\Exception\FileStorageException
	 * @throws \Aqua\Core\Exception\FileSystemException
	 */
	public function setOption($option, $value)
	{
		if(is_array($option)) {
			foreach($option as $opt => $val) {
				$this->setOption($opt, $val);
			}

			return $this;
		}
		switch($option) {
			case 'prefix':
				$this->prefix = (string)$value;
				break;
			case 'extension':
				$this->extension = (string)$value;
				break;
			case 'file_locking':
				$this->lock = (bool)$value;
				break;
			case 'hash':
				$this->hash = $value;
				break;
			case 'directory':
				$this->directory = rtrim($value, DIRECTORY_SEPARATOR);
				break;
			case 'serializer':
				switch($value) {
					default:
						$value = self::SERIALIZER_NONE;
					case self::SERIALIZER_NONE:
					case self::SERIALIZER_PHP:
					case self::SERIALIZER_JSON:
						break;
					case self::SERIALIZER_IGBINARY:
						if($value === self::SERIALIZER_IGBINARY && !extension_loaded('igbinary')) {
							throw new StorageException(
								__('exception', 'missing-extension', __CLASS__, 'igbinary'),
								StorageException::MISSING_EXTENSION
							);
						}
						break;
				}
				$this->serializer = $value;
				break;
			case 'directory_permission':
				if(($value & 0600) !== 0600) {
					throw new FileSystemException(
						__('exception', 'invalid-dir-permission', 'Filesystem cache', 'read & write'),
						FileSystemException::INVALID_PERMISSION
					);
				}
				$this->permission = $value;
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
			case 'prefix':
				return $this->prefix;
			case 'extension':
				return $this->extension;
			case 'file_locking':
				return $this->lock;
			case 'hash':
				return $this->hash;
			case 'directory':
				return $this->directory;
			case 'serializer':
				return $this->serializer;
			case 'directory_permission':
				return $this->permission;
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
		return $this->_fetch($key);
	}

	/**
	 * @param string $key
	 * @param mixed  $default
	 * @return mixed
	 */
	public function fetch($key, $default = null)
	{
		return ($this->_fetch($key, $meta, $value) ? $value : $default);
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
		}

		return $this->store($key, $value, $ttl);
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
			$type  = 'i';
			$value = (string)$value;
		} else if(is_float($value)) {
			$type  = 'f';
			$value = (string)$value;
		} else if(is_string($value)) {
			$type = 's';
		} else {
			$type  = 'x';
			$value = $this->_serialize($value);
		}

		return $this->_writeContent(
		            $this->_getCacheFile($key),
		            json_encode(array( 'key' => $key, 'type' => $type, 'ttl' => $ttl )) . "\r\n\r\n$value",
		            $this->permission
		);
	}

	/**
	 * @param string $key
	 * @return array|bool
	 */
	public function delete($key)
	{
		if(is_array($key)) {
			$deleted = array();
			foreach($key as $k) {
				if($this->delete($k)) {
					$deleted[] = $k;
				}
			}
			return $deleted;
		}
		$cacheFile = $this->_getCacheFile($key);
		if(file_exists($cacheFile)) {
			unlink($cacheFile);
			return true;
		}

		return false;
	}

	/**
	 * @param string    $key
	 * @param int|float $step
	 * @param int|float $defaultValue
	 * @param int       $ttl
	 * @return int|float|bool
	 */
	public function increment($key, $step = 1, $defaultValue = 0, $ttl = 0)
	{
		if(!$this->_fetch($key, $meta, $value)) {
			$value = $defaultValue + $step;

			return ($this->store($key, $value, $ttl) ? $value : false);
		} else {
			$value = $value + $step;

			return ($this->store($key, $value, $meta['ttl']) ? $value : false);
		}
	}

	/**
	 * @param string    $key
	 * @param int|float $step
	 * @param int|float $defaultValue
	 * @param int       $ttl
	 * @return int|float|bool
	 */
	public function decrement($key, $step = 1, $defaultValue = 0, $ttl = 0)
	{
		return $this->increment($key, -$step, $defaultValue, $ttl);
	}

	/**
	 * @return array
	 */
	public function flush()
	{
		return $this->flushPrefix('');
	}

	/**
	 * @param string $prefix
	 * @return array
	 */
	public function flushPrefix($prefix)
	{
		$regex     = '/^' . preg_quote($prefix, '/') . '/i';
		$directory = $this->directory . DIRECTORY_SEPARATOR . $this->prefix . '*';
		if($this->extension) {
			$directory .= '.' . $this->extension;
		}
		$iterator = new \GlobIterator($directory,
		                              \FilesystemIterator::SKIP_DOTS |
		                              \FilesystemIterator::KEY_AS_PATHNAME);
		$deletedKeys = array();
		foreach($iterator as $file) {
			$data = explode("\r\n\r\n", $this->_readContent($file), 2);
			if(count($data) !== 2) {
				continue;
			}
			$meta = json_decode($data[0], true);
			if(!json_last_error() && preg_match($regex, $meta['key'])) {
				unlink($file);
				$deletedKeys[] = $meta['key'];
			}
		}

		return $deletedKeys;
	}

	/**
	 * @return array
	 */
	public function gc()
	{
		$directory = $this->directory . DIRECTORY_SEPARATOR . $this->prefix . '*';
		if($this->extension) {
			$directory .= '.' . $this->extension;
		}
		$iterator = new \GlobIterator($directory,
		                              \FilesystemIterator::SKIP_DOTS |
		                              \FilesystemIterator::KEY_AS_PATHNAME);
		foreach($iterator as $file) {
			$data = explode("\r\n\r\n", $this->_readContent($file), 2);
			if(count($data) !== 2 || !($meta = json_decode($data[0], true)) ||
			   json_last_error() || ((int)$meta['ttl'] && (int)$meta['ttl'] <= time())) {
				unlink($file);
			}
		}
	}

	/**
	 * @param string $file
	 * @param string $content
	 * @param int    $permission
	 * @return bool
	 * @throws \Aqua\Core\Exception\FileSystemException
	 */
	protected function _writeContent($file, $content, $permission = null)
	{
		$flag = $this->lock ? LOCK_EX : 0;
		if(file_exists($file)) {
			@unlink($file);
		}
		if(!file_put_contents($file, $content, $flag)) {
			throw new FileSystemException(
				__('exception', 'write-file', $file)
			);
		}
		if($permission !== null && !chmod($file, $permission)) {
			throw new FileSystemException(
				__('exception', 'change-permission', $file)
			);
		}

		return true;
	}

	/**
	 * @param string $file
	 * @return bool|string
	 * @throws \Aqua\Core\Exception\FileSystemException
	 */
	protected function _readContent($file)
	{
		if(!file_exists($file)) {
			return false;
		}
		if($this->lock) {
			$flag = LOCK_SH;
			if(!($fp = fopen($file, 'rb'))) {
				throw new FileSystemException(
					__('exception', 'open-file', $file)
				);
			}
			if(!flock($fp, $flag, $w)) {
				fclose($fp);
				if($w) {
					return false;
				}
				throw new FileSystemException(
					__('exception', 'acquire-lock', $file)
				);
			}
			$content = stream_get_contents($fp);
			flock($fp, LOCK_UN);
			fclose($fp);
		} else {
			$content = file_get_contents($file, false);
		}

		return $content;
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
	 */
	protected function _normalizeKey(&$key)
	{
		if($this->hash) {
			$key = $this->prefix . hash($this->hash, $key);
		} else {
			$key = $this->prefix . $key;
		}
	}

	/**
	 * @param string $key
	 * @return string
	 */
	protected function _getCacheFile($key)
	{
		$this->_normalizeKey($key);
		$file = $this->directory . DIRECTORY_SEPARATOR . $key;
		if($this->extension) {
			$file .= '.' . $this->extension;
		}

		return $file;
	}

	/**
	 * @param string $key
	 * @param        $meta
	 * @param        $value
	 * @return bool
	 */
	protected function _fetch($key, &$meta = null, &$value = null)
	{
		$data = explode("\r\n\r\n", $this->_readContent($this->_getCacheFile($key)), 2);
		do {
			if(count($data) !== 2) {
				break;
			}
			list($meta, $value) = $data;
			$meta = json_decode($meta, true);
			if(json_last_error() || ((int)$meta['ttl'] && (int)$meta['ttl'] <= time())) {
				break;
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
		} while(0);

		return false;
	}
}
