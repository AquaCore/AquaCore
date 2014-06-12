<?php
namespace Aqua\Storage\Adapter;

use Aqua\Storage\Exception\StorageException;
use Aqua\Storage\FlushableStorageInterface;
use Aqua\Storage\NumberStorageInterface;
use Aqua\Storage\StorageInterface;

class Memcached
implements StorageInterface,
           FlushableStorageInterface
{
	/**
	 * @var \Memcached
	 */
	public $memcached;
	/**
	 * @var string
	 */
	public $prefix = '';
	/**
	 * @var string|null
	 */
	public $persistentId = null;
	/**
	 * @var array
	 */
	public $servers = array();
	/**
	 * @var array
	 */
	public $options = array();

	/**
	 * @param array $options
	 * @throws \Aqua\Storage\Exception\StorageException
	 */
	public function __construct(array $options)
	{
		if(!extension_loaded('memcached') || !class_exists('Memcached', false)) {
			throw new StorageException(
				__('exception', 'missing-extension', __CLASS__, 'memcached'),
				StorageException::MISSING_EXTENSION
			);
		}
		foreach($options as $opt => $value) {
			$this->setOption($opt, $value);
		}
		if($this->persistentId) {
			$this->memcached = new \Memcached($this->persistentId);
		} else {
			$this->memcached = new \Memcached;
		}
		$this->memcached->setOptions(array_intersect_key($this->options, array(
			\Memcached::OPT_LIBKETAMA_COMPATIBLE => null,
			\Memcached::OPT_HASH                 => null,
			\Memcached::OPT_COMPRESSION          => null,
			\Memcached::OPT_SERIALIZER           => null,
			\Memcached::OPT_DISTRIBUTION         => null,
			\Memcached::OPT_TCP_NODELAY          => null,
			\Memcached::OPT_CONNECT_TIMEOUT      => null,
			\Memcached::OPT_RETRY_TIMEOUT        => null,
			\Memcached::OPT_SEND_TIMEOUT         => null,
			\Memcached::OPT_RECV_TIMEOUT         => null,
			\Memcached::OPT_BUFFER_WRITES        => null,
			\Memcached::OPT_BINARY_PROTOCOL      => null,
			\Memcached::OPT_NO_BLOCK             => null,
			\Memcached::OPT_POLL_TIMEOUT         => null,
			\Memcached::OPT_CACHE_LOOKUPS        => null,
			\Memcached::OPT_SERVER_FAILURE_LIMIT => null
		)));
		if(empty($this->servers)) {
			$this->servers = array(array(
					'host'       => '127.0.0.1',
					'port'       => 11211,
					'persistent' => false,
					'weight'     => 1,
					'timeout'    => 1,
					'callback'   => array( $this, '_failureCallback' )
				));
		}
		$this->memcached->addServers($this->servers);
		$this->_open = true;
	}

	/**
	 * @param string|int $option
	 * @param mixed      $value
	 * @return bool
	 */
	public function setOption($option, $value)
	{
		switch($option) {
			case 'prefix':
				$this->prefix = (string)$value;
				break;
			case 'persistent_id':
				$this->persistentId = $value;
				break;
			case 'servers':
				foreach($value as &$server) {
					$this->_normalizeServer($server);
				}
				$this->servers = $value;
				break;
			case \Memcached::OPT_LIBKETAMA_COMPATIBLE:
			case 'libketama_compatible':
				$this->options[\Memcached::OPT_LIBKETAMA_COMPATIBLE] = (bool)$value;
				break;
			case \Memcached::OPT_HASH:
			case 'hash':
				$this->options[\Memcached::OPT_HASH] = (int)$value;
				break;
			case \Memcached::OPT_COMPRESSION:
			case 'compression':
				$this->options[\Memcached::OPT_COMPRESSION] = (bool)$value;
				break;
			case \Memcached::OPT_SERIALIZER:
			case 'serializer':
				$this->options[\Memcached::OPT_SERIALIZER] = (int)$value;
				break;
			case \Memcached::OPT_DISTRIBUTION:
			case 'distribution':
				$this->options[\Memcached::OPT_DISTRIBUTION] = (int)$value;
				break;
			case \Memcached::OPT_TCP_NODELAY:
			case 'tcp_no_delay':
				$this->options[\Memcached::OPT_TCP_NODELAY] = (int)$value;
				break;
			case \Memcached::OPT_CONNECT_TIMEOUT:
			case 'connect_timeout':
				$this->options[\Memcached::OPT_CONNECT_TIMEOUT] = (int)$value;
				break;
			case \Memcached::OPT_RETRY_TIMEOUT:
			case 'retry_timeout':
				$this->options[\Memcached::OPT_RETRY_TIMEOUT] = (int)$value;
				break;
			case \Memcached::OPT_RECV_TIMEOUT:
			case 'receive_timeout':
				$this->options[\Memcached::OPT_RECV_TIMEOUT] = (int)$value;
				break;
			case \Memcached::OPT_SEND_TIMEOUT:
			case 'send_timeout':
				$this->options[\Memcached::OPT_SEND_TIMEOUT] = (int)$value;
				break;
			case \Memcached::OPT_NO_BLOCK:
			case 'no_block':
				$this->options[\Memcached::OPT_NO_BLOCK] = (bool)$value;
				break;
			case \Memcached::OPT_POLL_TIMEOUT:
			case 'poll_timeout':
				$this->options[\Memcached::OPT_POLL_TIMEOUT] = (bool)$value;
				break;
			case \Memcached::OPT_CACHE_LOOKUPS:
			case 'cache_lookups':
				$this->options[\Memcached::OPT_CACHE_LOOKUPS] = (bool)$value;
				break;
			case \Memcached::OPT_BUFFER_WRITES:
			case 'buffer_writes':
				$this->options[\Memcached::OPT_BUFFER_WRITES] = (bool)$value;
				break;
			case \Memcached::OPT_BINARY_PROTOCOL:
			case 'binary_protocol':
				if($this->memcached) {
					return false;
				}
				$this->options[\Memcached::OPT_BINARY_PROTOCOL] = (bool)$value;
				break;
			case \Memcached::OPT_SERVER_FAILURE_LIMIT:
			case 'server_failure_limit':
				$this->options[\Memcached::OPT_SERVER_FAILURE_LIMIT] = (int)$value;
				break;
		}
		return true;
	}

	/**
	 * @param string|int $option
	 * @return mixed
	 */
	public function getOption($option)
	{
		switch($option) {
			case 'prefix':
				return $this->prefix;
			case 'persistent_id':
				return $this->persistentId;
			case 'servers':
				return $this->servers;
			case \Memcached::OPT_LIBKETAMA_COMPATIBLE:
			case 'libketama_compatible':
				$option = \Memcached::OPT_LIBKETAMA_COMPATIBLE;
				break;
			case \Memcached::OPT_BUFFER_WRITES:
			case 'buffer_writes':
				$option = \Memcached::OPT_BUFFER_WRITES;
				break;
			case \Memcached::OPT_BINARY_PROTOCOL:
			case 'binary_protocol':
				$option = \Memcached::OPT_BINARY_PROTOCOL;
				break;
			case \Memcached::OPT_NO_BLOCK:
			case 'no_block':
				$option = \Memcached::OPT_NO_BLOCK;
				break;
			case \Memcached::OPT_SERVER_FAILURE_LIMIT:
			case 'server_failure_limit':
				$option = \Memcached::OPT_SERVER_FAILURE_LIMIT;
				break;
			case \Memcached::OPT_SERIALIZER:
			case 'serializer':
				$option = \Memcached::OPT_SERIALIZER;
				break;
			case \Memcached::OPT_HASH:
			case 'hash':
				$option = \Memcached::OPT_HASH;
				break;
			case \Memcached::OPT_COMPRESSION:
			case 'compression':
				$option = \Memcached::OPT_COMPRESSION;
				break;
			case \Memcached::OPT_DISTRIBUTION:
			case 'distribution':
				$option = \Memcached::OPT_DISTRIBUTION;
				break;
			case \Memcached::OPT_TCP_NODELAY:
			case 'tcp_no_delay':
				$option = \Memcached::OPT_TCP_NODELAY;
				break;
			case \Memcached::OPT_CONNECT_TIMEOUT:
			case 'connect_timeout':
				$option = \Memcached::OPT_CONNECT_TIMEOUT;
				break;
			case \Memcached::OPT_RETRY_TIMEOUT:
			case 'retry_timeout':
				$option = \Memcached::OPT_RETRY_TIMEOUT;
				break;
			case \Memcached::OPT_SEND_TIMEOUT:
			case 'send_timeout':
				$option = \Memcached::OPT_SEND_TIMEOUT;
				break;
			case \Memcached::OPT_RECV_TIMEOUT:
			case 'receive_timeout':
				$option = \Memcached::OPT_RECV_TIMEOUT;
				break;
			case \Memcached::OPT_CACHE_LOOKUPS:
			case 'cache_lookups':
				$option = \Memcached::OPT_CACHE_LOOKUPS;
				break;
			default:
				return null;
		}
		if(array_key_exists($option, $this->options)) {
			return $this->options[$option];
		} else {
			return null;
		}
	}

	/**
	 * @param string $key
	 * @return bool
	 * @throws \MemcachedException
	 */
	public function exists($key)
	{
		$mc  = $this->memcached;
		$val = $mc->get($this->prefix . $key);
		if($val !== false && $val !== null) {
			return true;
		}
		switch($mc->getResultCode()) {
			case \Memcached::RES_SUCCESS:
				return true;
			case \Memcached::RES_NOTFOUND:
				return false;
			default:
				throw new \MemcachedException($mc->getResultMessage(), $mc->getResultCode());
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
		if($ttl > 2592000) {
			$ttl = 0;
		}

		return $this->memcached->add($this->prefix . $key, $value, $ttl);
	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 * @param int    $ttl
	 * @return bool
	 */
	public function store($key, $value, $ttl = 0)
	{
		if($ttl > 2592000) {
			$ttl = 0;
		}

		return $this->memcached->set($this->prefix . $key, $value, $ttl);
	}

	/**
	 * @param string $key
	 * @param mixed  $default
	 * @return mixed
	 * @throws \MemcachedException
	 */
	public function fetch($key, $default = null)
	{
		$val = $this->memcached->get($this->prefix . $key);
		if($val !== false && $val !== null) {
			return $val;
		}
		switch($this->memcached->getResultCode()) {
			case \Memcached::RES_SUCCESS:
				return $val;
			case \Memcached::RES_NOTFOUND:
				return $default;
			default:
				throw new \MemcachedException($this->memcached->getResultMessage(), $this->memcached->getResultCode());
		}
	}

	/**
	 * @param string $key
	 * @return array|bool
	 */
	public function delete($key)
	{
		if(is_array($key)) {
			foreach($key as &$k) {
				$k = $this->prefix . $k;
			}

			return $this->memcached->deleteMulti($key);
		} else {
			return $this->memcached->delete($this->prefix . $key);
		}
	}

	/**
	 * @return bool
	 */
	public function flush()
	{
		return $this->memcached->flush();
	}

	/**
	 * @param int $key
	 * @param int $step
	 * @param int $default
	 * @param int $ttl
	 * @return bool|int
	 */
	public function increment($key, $step = 1, $default = 0, $ttl = 0)
	{
		$val = $this->fetch($key, false);
		if($val == false) {
			$val = $default + $step;

			return ($this->store($key, $val, $ttl) ? $val : false);
		} else {
			if(is_int($val)) {
				return $this->memcached->increment($this->prefix . $key, $step);
			} else {
				return false;
			}
		}
	}

	/**
	 * @param int $key
	 * @param int $step
	 * @param int $default
	 * @param int $ttl
	 * @return bool|int
	 */
	public function decrement($key, $step = 1, $default = 0, $ttl = 0)
	{
		$val = $this->fetch($key, false);
		if($val == false) {
			$val = $default - $step;

			return ($this->store($key, $val, $ttl) ? $val : false);
		} else if(is_int($val)) {
			return $this->memcached->decrement($this->prefix . $key, $step);
		} else {
			return false;
		}
	}

	/**
	 * @param array $server
	 */
	protected function _normalizeServer(array &$server)
	{
		$server += array(
			'host'       => '127.0.0.1',
			'port'       => 11211,
			'persistent' => false,
			'weight'     => 1,
			'timeout'    => 1,
			'callback'   => array( $this, '_failureCallback' )
		);
		$server['port']       = (int)$server['port'];
		$server['persistent'] = (bool)$server['persistent'];
	}
}
