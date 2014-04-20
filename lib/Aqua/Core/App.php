<?php
namespace Aqua\Core;

use Aqua\Autoloader\Autoloader;
use Aqua\Autoloader\ClassMap;
use Aqua\Captcha\Captcha;
use Aqua\Core\Exception\CoreException;
use Aqua\Http\Request;
use Aqua\Http\Response;
use Aqua\Log\ErrorLog;
use Aqua\Permission\PermissionSet;
use Aqua\Ragnarok\Server;
use Aqua\Router\Router;
use Aqua\Site\Dispatcher;
use Aqua\Storage\StorageFactory;

class App
{
	/**
	 * @var int
	 */
	public static $uid = 1;
	/**
	 * @var int
	 */
	public static $uidSeed = 0;
	/**
	 * @var \Aqua\Storage\StorageInterface
	 */
	public static $cache;
	/**
	 * @var \Aqua\Http\Request
	 */
	public static $request;
	/**
	 * @var \Aqua\Http\Response
	 */
	public static $response;
	/**
	 * @var \PDO
	 */
	public static $dbh;
	/**
	 * @var \Aqua\Core\User
	 */
	public static $user;
	/**
	 * @var \Aqua\Core\Settings
	 */
	public static $settings;
	/**
	 * @var \Aqua\Captcha\Captcha
	 */
	public static $captcha;
	/**
	 * @var array
	 */
	public static $registry = array();
	/**
	 * @var \Aqua\Ragnarok\Server
	 */
	public static $activeServer;
	/**
	 * @var \Aqua\Ragnarok\Server\CharMap
	 */
	public static $activeCharMapServer;
	/**
	 * @var \Aqua\Ragnarok\Account
	 */
	public static $activeRagnarokAccount;
	/**
	 * @var \Aqua\Ragnarok\Character
	 */
	public static $activeRagnarokCharacter;
	/**
	 * @var string
	 */
	public static $styleSheet = '';
	/**
	 * @var \Aqua\Autoloader\Autoloader
	 */
	public static $autoloader;
	/**
	 * @var \Aqua\Site\Dispatcher
	 */
	public static $dispatcher;

	/**
	 * Installed version of AquaCore (String)
	 */
	const VERSION = '0.1.2';
	/**
	 * Installed version of AquaCore (Integer)
	 */
	const VERSION_LONG = 102;

	private function __construct() { }

	public static function registerAutoloaders()
	{
		self::$autoloader = new Autoloader;
		self::$autoloader->map('Aqua')
			->addDirectory(\Aqua\ROOT . '/lib/Aqua');
		self::$autoloader->map('Page')
			->addDirectory(\Aqua\ROOT . '/lib/Page')
			->case = ClassMap::CASE_LOWER;
		self::$autoloader->map('PHPass')
			->addDirectory(\Aqua\ROOT . '/lib/PHPass');
		self::$autoloader->map('PHPMailer')
			->addDirectory(\Aqua\ROOT . '/lib/PHPMailer');
		self::$autoloader->map('CharGen')
			->addDirectory(\Aqua\ROOT . '/lib/CharGen');
		self::$autoloader->map('Cron')
			->addDirectory(\Aqua\ROOT . '/lib/Cron');
		self::$autoloader->register();
	}

	/**
	 * @throws \Aqua\Core\Exception\CoreException
	 */
	public static function defineConstants()
	{
		$settings = self::settings();

		if(!defined('Aqua\HTTPS')) {
			/**
			 * Whether the current request is over SSL
			 * @name \Aqua\HTTPS
			 */
			define('Aqua\HTTPS', ($https = getenv('HTTPS')) && ($https === 'on' || $https === '1'));
		}
		$protocol = 'http';
		if(\Aqua\HTTPS) {
			$protocol .= 's';
		}
		$protocol .= '://';
		if(!$domain = $settings->get('domain', '')) {
			throw new CoreException(__('exception', 'undefined-domain'));
		};
		if($directory = $settings->get('base_dir', '')) {
			$directory = '/' . $directory;
		}
		if(!defined('Aqua\DIR')) {
			/**
			 * The path to AquaCore's root directory relative to the web root
			 * @name \Aqua\DIR
			 */
			define('Aqua\DIR' , str_replace('\\', '/', trim($settings->get('base_dir', ''), '/\\')));
		}
		if(!defined('Aqua\WORKING_DIR')) {
			/**
			 * The current directory
			 * @name \Aqua\WORKING_DIR
			 */
			define('Aqua\WORKING_DIR', str_replace('\\', '/', trim(substr(dirname(getenv('SCRIPT_FILENAME')), strlen(\Aqua\ROOT)), '/\\')));
		}
		if(!defined('Aqua\TABLE_PREFIX')) {
			/**
			 * Prefix for AquaCore's tables
			 * @name \Aqua\TABLE_PREFIX
			 */
			define('Aqua\TABLE_PREFIX', $settings->get('db')->get('prefix', ''));
		}
		if(!defined('Aqua\PUBLIC_FILE_PERMISSION')) {
			/**
			 * Permission for public files, meant to be viewed by anyone. Defaults to 0644
			 * @name \Aqua\PUBLIC_FILE_PERMISSION
			 */
			define('Aqua\PUBLIC_FILE_PERMISSION', 0775);
		}
		if(!defined('Aqua\PUBLIC_DIRECTORY_PERMISSION')) {
			/**
			 * Permission for public directories, meant to be viewed by anyone. Defaults to 0775
			 * @name \Aqua\PUBLIC_DIRECTORY_PERMISSION
			 */
			define('Aqua\PUBLIC_DIRECTORY_PERMISSION', 0775);
		}
		if(!defined('Aqua\PRIVATE_FILE_PERMISSION')) {
			/**
			 * Permission for private files. Not meant to be viewed by non-users. Defaults to 0600
			 * @name \Aqua\PRIVATE_FILE_PERMISSION
			 */
			define('Aqua\PRIVATE_FILE_PERMISSION', 0770);
		}
		if(!defined('Aqua\PRIVATE_DIRECTORY_PERMISSION')) {
			/**
			 * Permission for private directories, not meant to be viewed by non-users. Defaults to 0700
			 * @name \Aqua\PRIVATE_DIRECTORY_PERMISSION
			 */
			define('Aqua\PRIVATE_DIRECTORY_PERMISSION', 0770);
		}
		if(!defined('Aqua\BLANK')) {
			/**
			 * A 1x1px transparent base64 encoded image, used to replace missing images.
			 * @name \Aqua\BLANK
			 */
			define('Aqua\BLANK', 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==');
		}
		if(!defined('Aqua\ASSERT')) {
			/**
			 * Whether to enable assertions
			 * @name \Aqua\ASSERT
			 */
			define('Aqua\ASSERT', false);
		}
		if(!defined('Aqua\DOMAIN')) {
			/**
			 * The applications domain
			 * @name \Aqua\DOMAIN
			 */
			define('Aqua\DOMAIN', $domain);
		}
		if(!defined('Aqua\URL')) {
			/**
			 * The applications main url
			 * @name \Aqua\URL
			 */
			define('Aqua\URL', $protocol . $domain . $directory);
		}
		if(!defined('Aqua\WORKING_URL')) {
			/**
			 * The current request's base url
			 * @name \Aqua\WORKING_URL
			 */
			define('Aqua\WORKING_URL', rtrim(\Aqua\URL . '/' . \Aqua\WORKING_DIR, '/'));
		}
		if(!defined('Aqua\REWRITE')) {
			/**
			 * Whether to use "pretty urls". Support for mod_rewrite is automatically detected if rewrite_url
			 * isn't set in the application's settings file.
			 * @name \Aqua\REWRITE
			 */
			define('Aqua\REWRITE', $settings->exists('rewrite_url') ?
				(bool)$settings->get('rewrite_url', false) : (getenv('APACHE_MOD_REWRITE') === 'On') ||
				                                              getenv('IIS_UrlRewriteModule'));
		}
	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 */
	public static function registrySet($key, $value)
	{
		self::$registry[$key] = $value;
	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 */
	public static function registryBind($key, &$value)
	{
		self::$registry[$key] = &$value;
	}

	/**
	 * @param string $key
	 * @param mixed  $default
	 * @return mixed
	 */
	public static function &registryGet($key, $default = null)
	{
		if(self::registryExists($key)) {
			return self::$registry[$key];
		} else {
			return $default;
		}
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public static function registryExists($key)
	{
		return isset(self::$registry[$key]);
	}

	/**
	 * @return \Aqua\Core\Settings
	 */
	public static function settings()
	{
		if(!self::$settings) {
			self::$settings = new Settings(array(
				'title' => 'AquaCore',
			    'ssl' => 0,
			    'date_format' => '%x',
			    'time_format' => '%X',
			    'datetime_format' => '%x %X',
			    'rewrite_url' => false,
			    'language' => 'en',
			    'domain' => getenv('HTTP_HOST') ?: getenv('SERVER_NAME') ?: getenv('SERVER_NAME') ?: '',
			    'base_dir' => '',
				'cache' => array(
					'storage_adapter' => 'File',
					'storage_options' => array(
						'prefix' => '',
						'hash' => null,
						'directory' => \Aqua\ROOT . '/tmp/cache',
						'gc_probability' => 0,
					)
				),
			    'account' => array(
				    'username' => array(
					    'min_length' => 3,
					    'max_length' => 50
				    ),
			        'display_name' => array(
				        'min_length' => 2,
			            'max_length' => 50,
			        ),
			        'password' => array(
				        'min_length' => 3,
			        ),
			        'email' => array(
				        'max_length' => 32
			        )
			    )
			));
		}

		return self::$settings;
	}

	/**
	 * @return \Aqua\Storage\StorageInterface
	 */
	public static function cache()
	{
		if(!self::$cache) {
			$settings    = self::settings()->get('cache');
			self::$cache = StorageFactory::build(
				$settings->get('storage_adapter', 'File'),
				$settings->get('storage_options')->toArray()
			);
		}

		return self::$cache;
	}

	/**
	 * @return \Aqua\Core\User
	 */
	public static function user()
	{
		if(!self::$user) {
			self::$user = new User(self::request(), self::response(), self::settings()->get('session'));
		}

		return self::$user;
	}

	/**
	 * @return \Aqua\Http\Request
	 */
	public static function request()
	{
		if(!self::$request) {
			self::$request = Request::parseGlobals();
		}

		return self::$request;
	}

	/**
	 * @return \Aqua\Http\Response
	 */
	public static function response()
	{
		if(!self::$response) {
			self::$response = new Response;
		}

		return self::$response;
	}

	/**
	 * @return \PDO
	 */
	public static function connection()
	{
		if(!self::$dbh) {
			$settings  = self::settings()->get('db')->toArray();
			self::$dbh = ac_mysql_connection($settings);
		}

		return self::$dbh;
	}

	/**
	 * @return \Aqua\Captcha\Captcha
	 */
	public static function captcha()
	{
		if(!self::$captcha) {
			self::$captcha = new Captcha(self::settings()->get('captcha'));
		}

		return self::$captcha;
	}

	/**
	 * @param string $namespace
	 * @return \Aqua\Autoloader\Autoloader|\Aqua\Autoloader\ClassMap
	 */
	public static function autoloader($namespace = null)
	{
		if(!$namespace) {
			return self::$autoloader;
		} else {
			return self::$autoloader->map($namespace);
		}
	}

	/**
	 * @param string|\Aqua\Router\Router            $router
	 * @param string|\Aqua\Permission\PermissionSet $permissionSet
	 * @return \Aqua\Site\Dispatcher
	 */
	public static function dispatcher($router = null, $permissionSet = null)
	{
		if(!self::$dispatcher) {
			$appDir = rtrim(\Aqua\ROOT . '/' . \Aqua\WORKING_DIR, '/') . '/application';
			if(!$router) {
				$router = include "$appDir/routing.php";
			} else if(is_string($router)) {
				$router = include $router;
			}
			if(!$permissionSet) {
				$permissionSet = include "$appDir/permission.php";
			} else if(is_string($permissionSet)) {
				$permissionSet = include $permissionSet;
			}
			self::$dispatcher = new Dispatcher($router, $permissionSet);
		}
		return self::$dispatcher;
	}

	/**
	 * @return string
	 */
	public static function logo()
	{
		return \Aqua\URL . '/assets/images/logo.png';
	}

	/**
	 * @param int $seed
	 * @return int
	 */
	public static function uid($seed = 0)
	{
		$id = self::$uid;
		self::$uidSeed += $seed;
		if(self::$uidSeed === 0) ++self::$uid;
		else --self::$uidSeed;

		return $id;
	}

	public static function upgrade()
	{
		$oldVersion = (file_exists(\Aqua\ROOT . '/upgrade/version') ? file_get_contents(\Aqua\ROOT . '/upgrade/version') : '0.1.1');
		if(version_compare(self::VERSION, $oldVersion, '>')) {
			foreach(glob(\Aqua\ROOT . '/upgrade/sql/*.sql') as $file) {
				if(!ac_parse_upgrade_file_name($file, $version, $num, $type) ||
				   !$type || !version_compare($version, $oldVersion, '>')) { continue; }
				$query = str_replace('#', \Aqua\TABLE_PREFIX, file_get_contents($file));
				if($type === 'aquacore') {
					try { App::connection()->exec($query); } catch(\Exception $e) { }
				} else {
					foreach(Server::$servers as $server) {
						if($type === 'login') {
							try { $server->login->connection()->exec($query); } catch(\Exception $e) { }
						} else if($type === 'loginlog') {
							try { $server->login->log->connection()->exec($query); } catch(\Exception $e) { }
						} else {
							foreach($server->charmap as $charmap) {
								if($type === 'charmap') {
									try { $charmap->connection()->exec($query); } catch(\Exception $e) { }
								} else if($type === 'charmaplog') {
									try { $charmap->log->connection()->exec($query); } catch(\Exception $e) { }
								}
							}
							reset($server->charmap);
						}
					}
					reset(Server::$servers);
				}
			}
			foreach(glob(\Aqua\ROOT . '/upgrade/lang/*.xml') as $file) {
				if(!ac_parse_upgrade_file_name($file, $version) ||
				   !version_compare($version, $oldVersion, '>')) { continue; }
				L10n::import(new \SimpleXMLElement(file_get_contents($file)), null);
			}
			foreach(glob(\Aqua\ROOT . '/upgrade/*.php') as $file) {
				if(!ac_parse_upgrade_file_name($file, $version) ||
				   !version_compare($version, $oldVersion, '>')) { continue; }
				include $file;
			}
			$old = umask(0);
			file_put_contents(\Aqua\ROOT . '/upgrade/version', self::VERSION);
			chmod(\Aqua\ROOT . '/upgrade/version', \Aqua\PRIVATE_FILE_PERMISSION);
		}
	}
}
