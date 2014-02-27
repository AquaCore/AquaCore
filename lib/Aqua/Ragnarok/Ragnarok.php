<?php
namespace Aqua\Ragnarok;

use Aqua\Storage\StorageFactory;

class Ragnarok
{
	/**
	 * @var \Aqua\Ragnarok\Server[]
	 */
	public static $servers = array();
	protected static $available_servers = array();
	protected static $server_count = 0;
	public static $pincode_min_length = 4;
	public static $pincode_max_length = 4;
	public static $shop_max_amount = 999;
	public static $item_display_script = true;

	public static function init()
	{
		$settings = include \Aqua\ROOT . '/settings/ragnarok.php';
		$cache = StorageFactory::build(
			$settings['cache']['storage_adapter'],
			$settings['cache']['storage_options']
		);
		if(isset($settings['show_script'])) { self::$item_display_script = (bool)$settings['show_script']; }
		if(isset($settings['cash_shop_max_amount'])) { self::$shop_max_amount = (int)$settings['cash_shop_max_amount']; }
		if(isset($settings['pincode']['max_len'])) { self::$pincode_max_length = (int)$settings['pincode']['max_len']; }
		if(isset($settings['pincode']['min_len'])) { self::$pincode_max_length = (int)$settings['pincode']['min_len']; }
		if(!isset($settings['servers'])) {
			return;
		}
		self::$server_count = count($settings['servers']);
		foreach($settings['servers'] as $name => $server_options) {
			$name = strtolower($name);
			self::$servers[$name] = new Server($name, $server_options, $cache);
			self::$available_servers[] = $name;
		}
	}

	public static function availableServers()
	{
		return self::$server_count;
	}

	public static function servers()
	{
		return self::$available_servers;
	}

	public static function server($server)
	{
		$server = strtolower($server);
		return isset(self::$servers[$server]) ? self::$servers[$server] : null;
	}

	public function loadAccounts()
	{

	}
}
