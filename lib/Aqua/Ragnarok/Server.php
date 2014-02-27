<?php
namespace Aqua\Ragnarok;

use Aqua\Core\Settings;
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
		$this->key          = $key;
		$this->name         = $options['name'];
		$this->emulator     = (int)$options['emulator'];
		$this->login        = new Login($this, $options['login']);
		$this->charmapCount = count($options['charmap']);
		$this->uri          = new Uri;
		if(self::$serverCount > 1) {
			$this->uri->path = array( 'ro', $this->key );
		} else {
			$this->uri->path = array( 'ragnarok' );
		}
		foreach($options['charmap'] as $k => $charmap) {
			$this->charmap[$k] = new CharMap($k, $this, $charmap);
		}
	}

	/**
	 * @param $name
	 * @return \Aqua\Ragnarok\Server\CharMap|null
	 */
	public function charmap($name)
	{
		$name = strtolower($name);
		return isset($this->charmap[$name]) ? $this->charmap[$name] : null;
	}

	public function url(array $options = array())
	{
		return $this->uri->url($options);
	}

	public static function init()
	{
		$servers = include \Aqua\ROOT . '/settings/ragnarok.php';
		self::$serverCount = count($servers);
		foreach($servers as $key => $data) {
			self::$servers[$key] = new self($key, $data);
		}
	}

	public static function get($key)
	{
		return (isset(self::$servers[$key]) ? self::$servers[$key] : null);
	}
}
