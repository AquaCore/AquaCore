<?php
namespace Aqua\Autoloader;

use Aqua\Storage\StorageInterface;

class Autoloader
{
	/**
	 * @var \Aqua\Autoloader\ClassMap[]
	 */
	public $maps = array();
	/**
	 * @var \Aqua\Storage\StorageInterface
	 */
	public $cache;
	/**
	 * @var string
	 */
	public $cacheKey = 'autoloader_cache';
	/**
	 * @var array
	 */
	public $cacheData;
	/**
	 * @var bool
	 */
	public $updated = false;

	/**
	 * @param string $namespace
	 * @return bool
	 */
	public function exists($namespace)
	{
		return isset($this->maps[strtolower($namespace)]);
	}

	/**
	 * @param string $namespace
	 * @return \Aqua\Autoloader\ClassMap
	 */
	public function map($namespace)
	{
		$key = strtolower($namespace);
		if(!isset($this->maps[$key])) {
			$this->maps[$key] = new ClassMap($namespace);
		}

		return $this->maps[$key];
	}

	/**
	 * @param string $namespace
	 * @return \Aqua\Autoloader\ClassMap
	 */
	public function remove($namespace)
	{
		unset($this->maps[strtolower($namespace)]);

		return $this;
	}

	/**
	 * @param bool $throw
	 * @param bool $prepend
	 * @return bool
	 */
	public function register($throw = true, $prepend = false)
	{
		return spl_autoload_register(array( $this, 'load' ), $throw, $prepend);
	}

	/**
	 * @return bool
	 */
	public function unregister()
	{
		return spl_autoload_unregister(array( $this, 'load' ));
	}

	/**
	 * @param string $class
	 * @return bool
	 */
	public function load($class)
	{
		$namespace = strstr($class, '\\', true);
		if(!$this->exists($namespace)) {
			return false;
		}
		if(!($file = $this->fetchCache($class))) {
			$file = $this->maps[strtolower($namespace)]->findFile($class);
			if($file === false) {
				return false;
			} else {
				if($this->cache) {
					$this->cacheData[strtolower($class)] = $file;
					$this->updated                       = true;
				}
			}
		}
		include $file;

		return true;
	}

	/**
	 * @param \Aqua\Storage\StorageInterface $storage
	 * @paren string                         $key
	 * @return \Aqua\Autoloader\Autoloader
	 */
	public function setCache(StorageInterface $storage, $key = 'autoloader_cache')
	{
		$this->cache    = $storage;
		$this->cacheKey = $key;
		register_shutdown_function(array( $this, 'saveCache' ));

		return $this;
	}

	/**
	 * @param string $class
	 * @return string|null
	 */
	public function fetchCache($class)
	{
		if(!$this->cache) {
			return null;
		}
		if($this->cacheData === null && !($this->cacheData = $this->cache->fetch($this->cacheKey, null))) {
			$this->cacheData = array();
		}

		return (array_key_exists($class, $this->cacheData) ? $this->cacheData[$class] : null);
	}

	/**
	 * @param bool $force
	 * @return \Aqua\Autoloader\Autoloader
	 */
	public function saveCache($force = false)
	{
		if($this->updated || $force) {
			$this->cache->store($this->cacheKey, $this->cacheData);
		}

		return $this;
	}

	/**
	 * @return \Aqua\Autoloader\Autoloader
	 */
	public function flushCache()
	{
		if($this->cache) {
			$this->cache->delete($this->cacheKey);
		}

		return $this;
	}
}
