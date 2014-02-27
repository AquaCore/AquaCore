<?php
namespace Aqua\Storage;

use Aqua\Storage\Adapter\APC;
use Aqua\Storage\Adapter\APCu;
use Aqua\Storage\Adapter\File;
use Aqua\Storage\Adapter\Memcached;
use Aqua\Storage\Adapter\Redis;
use Aqua\Storage\Adapter\SQLite;
use Aqua\Storage\Adapter\xCache;
use Aqua\Storage\Exception\StorageException;

class StorageFactory
{
	public static function build($adapter, array $options)
	{
		switch(strtolower($adapter)) {
			case 'apc': $storage = self::APC($options); break;
			case 'apcu': $storage = self::APCu($options); break;
			case 'file': $storage = self::File($options); break;
			case 'memcache': $storage = self::Memcache($options); break;
			case 'memcached': $storage = self::Memcached($options); break;
			case 'redis': $storage = self::Redis($options); break;
			case 'xcache': $storage = self::xCache($options); break;
			case 'sqlite': $storage = self::SQLite($options); break;
			default:
				throw new StorageException(
					__('exception', 'Invalid storage adapter %s.', $adapter),
					StorageException::INVALID_STORAGE_ADAPTER
				);
		}
		return $storage;
	}

	public static function APC(array $options = array())
	{
		return new APC($options);
	}

	public static function APCu(array $options = array())
	{
		return new APCu($options);
	}

	public static function File(array $options = array())
	{
		return new File($options);
	}

	public static function Memcached(array $options = array())
	{
		$options += array(
			'servers' => array( array( '127.0.0.1', 11211 ) )
		);
		return new Memcached($options);
	}

	public static function Redis(array $options = array())
	{
		return new Redis($options);
	}

	public static function xCache(array $options = array())
	{
		return new xCache($options);
	}

	public static function SQLite(array $options = array())
	{
		return new SQLite($options);
	}
}
