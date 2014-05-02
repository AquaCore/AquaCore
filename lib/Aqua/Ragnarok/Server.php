<?php
namespace Aqua\Ragnarok;

use Aqua\Event\Event;
use Aqua\Http\Uri;
use Aqua\Ragnarok\Server\CharMap;
use Aqua\Ragnarok\Server\Login;

class Server
{
	/**
	 * @var string
	 */
	public $key;
	/**
	 * @var string
	 */
	public $name;
	/**
	 * @var string
	 */
	public $defaultServer;
	/**
	 * @var \Aqua\Ragnarok\Server\Login
	 */
	public $login;
	/**
	 * @var \Aqua\Ragnarok\Server\CharMap[]
	 */
	public $charmap = array();
	/**
	 * @var int
	 */
	public $charmapCount = 0;
	/**
	 * @var int
	 */
	public $emulator;
	/**
	 * @var \Aqua\Http\URI
	 */
	public $uri;
	/**
	 * @var \Aqua\Ragnarok\Server[]
	 */
	public static $servers = array();
	/**
	 * @var int
	 */
	public static $serverCount;
	/**
	 * @var \Aqua\Core\Settings
	 */
	protected static $_settings;

	const EMULATOR_RATHENA  = 1;
	const EMULATOR_HERCULES = 2;

	public function __construct($key, $options)
	{
		$this->key           = $key;
		$this->name          = $options['name'];
		$this->emulator      = (int)$options['emulator'];
		$this->login         = new Login($this, $options['login']);
		$this->charmapCount  = count($options['charmap']);
		$this->defaultServer = (isset($options['default_server']) ? $options['default_server'] : '');
		$this->uri           = new Uri(array());
		if(self::$serverCount > 1) {
			$this->uri->path = array( 'ro', $this->key );
		} else {
			$this->uri->path = array( 'ragnarok' );
		}
		foreach($options['charmap'] as $k => $charmap) {
			$this->charmap[$k] = new CharMap($k, $this, $charmap);
		}
		if(!$this->defaultServer || !array_key_exists($this->defaultServer, $this->charmap)) {
			$this->defaultServer = key($this->charmap);
		}
	}

	/**
	 * @param $name
	 * @return \Aqua\Ragnarok\Server\CharMap|null
	 */
	public function charmap($name = null)
	{
		if($name) {
			$name = strtolower($name);
		} else {
			$name = $this->defaultServer;
		}
		return (isset($this->charmap[$name]) ? $this->charmap[$name] : null);
	}

	public function url(array $options = array(), $admin = null)
	{
		if($admin === null) {
			$admin = \Aqua\PROFILE === 'ADMINISTRATION';
		}
		if($admin) {
			$options += array(
				'action'    => 'index',
				'arguments' => array(),
				'query'     => array()
			);
			$options['base_dir'] = \Aqua\DIR . '/admin';
			$path = array( 'r', $this->key );
			if(isset($options['path'])) {
				$options['path'] = array_merge($path, $options['path']);
			} else {
				$options['path'] = $path;
			}
			return ac_build_url($options);
		} else {
			$options += array( 'base_dir' => \Aqua\DIR );
			return $this->uri->url($options);
		}
	}

	public static function init()
	{
		$servers = include \Aqua\ROOT . '/settings/ragnarok.php';
		self::$serverCount = count($servers);
		foreach($servers as $key => $data) {
			self::$servers[$key] = new self($key, $data);
		}
		Event::fire('ragnarok.init');
	}

	public static function get($key)
	{
		return (isset(self::$servers[$key]) ? self::$servers[$key] : null);
	}
}
