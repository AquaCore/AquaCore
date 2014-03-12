<?php
namespace Aqua\Plugin;

use Aqua\Core\App;
use Aqua\Core\L10n;
use Aqua\Event\Event;
use Aqua\Plugin\Exception\PluginManagerException;
use Aqua\SQL\Query;
use Aqua\UI\Form;
use Aqua\UI\Tag;
use Aqua\User\Role;

class Plugin
{
	/**
	 * @var string
	 */
	public $directory;
	/**
	 * @var string
	 */
	public $folder;
	/**
	 * @var string
	 */
	public $url;
	/**
	 * @var int
	 */
	public $id;
	/**
	 * @var string
	 */
	public $guid;
	/**
	 * @var string
	 */
	public $name;
	/**
	 * @var string
	 */
	public $description;
	/**
	 * @var string
	 */
	public $author;
	/**
	 * @var string
	 */
	public $authorUrl;
	/**
	 * @var string
	 */
	public $pluginUrl;
	/**
	 * @var string
	 */
	public $version;
	/**
	 * @var string
	 */
	public $license;
	/**
	 * @var bool
	 */
	public $isEnabled = false;
	/**
	 * @var \Aqua\Plugin\PluginSettings
	 */
	public $settings;
	/**
	 * @var \Aqua\Plugin\Plugin[]
	 */

	public static $pluginIds = array();
	/**
	 * @var \Aqua\Plugin\Plugin[]
	 */
	public static $pluginKeys = array();

	const CACHE_KEY = 'plugins';
	const DIRECTORY = '/plugins';

	protected function __construct()
	{
		$this->settings = new PluginSettings($this);
	}

	/**
	 * @return bool
	 */
	public function enable()
	{
		if($this->isEnabled) {
			return false;
		}
		$tbl = ac_table('plugins');
		$sth = App::connection()->prepare("
		UPDATE `$tbl`
		SET _enabled = 'y'
		WHERE id = ?
		");
		$sth->bindValue(1, $this->id, \PDO::PARAM_INT);
		$sth->execute();
		$this->run('activate');
		$this->settings->exportToDatabase();
		$feedback = array( $this );
		if($xml = $this->xml('language')) {
			L10n::import($xml, $this->id);
		}
		if($xml = $this->xml('permission')) {
			Role::importPermissions($xml, $this->id);
		}
		self::rebuildCache(true);
		Event::fire('plugin.enable', $feedback);

		return true;
	}

	/**
	 * @return bool
	 */
	public function disable()
	{
		if(!$this->isEnabled) {
			return false;
		}
		$sth = App::connection()->prepare("CALL disablePlugin(?)");
		$sth->bindValue(1, $this->id, \PDO::PARAM_INT);
		$sth->execute();
		$sth->closeCursor();
		$this->run('deactivate');
		$feedback = array( $this );
		self::rebuildCache(true);
		L10n::rebuildCache();
		Role::rebuildCache();
		Event::fire('plugin.disable', $feedback);

		return true;
	}

	/**
	 * @return bool
	 */
	public function delete()
	{
		$this->disable();
		ac_delete_dir($this->directory, true);
		$tbl = ac_table('plugins');
		$sth = App::connection()->prepare("DELETE FROM `$tbl` WHERE id = :id");
		$sth->bindValue(':id', $this->id, \PDO::PARAM_INT);
		$sth->execute();
		$sth->closeCursor();
		$feedback = array( $this );
		Event::fire('plugin.delete', $feedback);

		return true;
	}

	/**
	 * Run a script in the plugin's base directory
	 *
	 * @param string $name
	 * @param array  $vars
	 * @return mixed
	 */
	public function run($name, array &$vars = array())
	{
		extract($vars, EXTR_REFS);
		$file = "{$this->directory}/{$name}.php";
		if(file_exists($file)) {
			return include $file;
		}
		else {
			return null;
		}
	}

	/**
	 * Get a \SimpleXMLElement from a xml file in the plugin's /xml folder
	 *
	 * @param string $name
	 * @return \SimpleXMLElement|null
	 */
	public function xml($name)
	{
		$file = "{$this->directory}/xml/{$name}.xml";
		if(!file_exists($file)) {
			return null;
		}
		else {
			return new \SimpleXMLElement(file_get_contents($file));
		}
	}

	/**
	 * Initialize enabled plugins.
	 */
	public static function init()
	{
		if((self::$pluginIds = App::cache()->fetch(self::CACHE_KEY, false)) === false) {
			self::$pluginIds = array();
			self::rebuildCache(true);
		}
		foreach(self::$pluginIds as $plugin) {
			self::$pluginKeys[$plugin->id] = $plugin;
			if($plugin->isEnabled) {
				$plugin->run('startup');
			}
		}
		register_shutdown_function(array( __CLASS__, 'rebuildCache' ));
	}

	/**
	 * @return \Aqua\SQL\Search
	 */
	public static function search()
	{
		return Query::search(App::connection())
			->columns(array(
				'id'          => 'id',
				'guid'        => '_guid',
				'directory'   => '_directory',
				'name'        => '_name',
				'author'      => '_author',
				'author_url'  => '_author_url',
				'plugin_url'  => '_plugin_url',
				'license'     => '_license',
				'description' => '_description',
				'version'     => '_version',
				'enabled'     => '_enabled',
			))
			->whereOptions(array(
				'id'          => 'id',
				'guid'        => '_guid',
				'directory'   => '_directory',
				'name'        => '_name',
				'author'      => '_author',
				'author_url'  => '_author_url',
				'plugin_url'  => '_plugin_url',
				'license'     => '_license',
				'description' => '_description',
				'version'     => '_version',
				'enabled'     => '_enabled',
			))
			->parser(array( __CLASS__, 'parsePluginSql' ))
			->from(ac_table('plugins'));
	}

	/**
	 * Get a plugin by ID or key
	 *
	 * @param string|int $id
	 * @param string     $type
	 * @return \Aqua\Plugin\Plugin|null
	 */
	public static function get($id, $type = 'id')
	{
		if($type === 'id' && isset(self::$pluginIds[$id])) {
			return self::$pluginIds[$id];
		} else if($type === 'guid' && isset(self::$pluginKeys[$id])) {
			return self::$pluginKeys[$id];
		}
		$select = Query::select(App::connection())
			->columns(array(
				'id'          => 'id',
				'guid'        => '_guid',
				'directory'   => '_directory',
				'name'        => '_name',
				'author'      => '_author',
				'author_url'  => '_author_url',
				'plugin_url'  => '_plugin_url',
				'license'     => '_license',
				'description' => '_description',
				'version'     => '_version',
				'enabled'     => '_enabled',
			))
			->from(ac_table('plugins'))
			->limit(1)
			->parser(array( __CLASS__, 'parsePluginSql' ));
		switch($type) {
			case 'id':
				$select->where(array( 'id' => $id ));
				break;
			case 'guid':
				$select->where(array( '_guid' => $id ));
				break;
			default:
				return null;
		}
		$select->query();

		return ($select->valid() ? $select->current() : null);
	}

	/**
	 * Scan folder for a plugin and add it to the database
	 *
	 * @param string $dir
	 * @param string $dir_name
	 * @return \Aqua\Plugin\Plugin
	 * @throws \Aqua\Plugin\Exception\PluginManagerException|\Exception
	 */
	public static function import($dir, $dir_name = null)
	{
		if(!file_exists("$dir/xml/plugin.xml") ||
		   !file_exists("$dir/startup.php")
		) {
			throw new PluginManagerException(
				__('exception', 'invalid-plugin-structure'),
				PluginManagerException::IMPORT_INVALID_STRUCTURE
			);
		}
		$xml = new \SimpleXMLElement(file_get_contents("$dir/xml/plugin.xml"));
		if(empty($xml->guid) || empty($xml->title)) {
			throw new PluginManagerException(
				__('exception', 'missing-plugin-data', (empty($xml->guid) ? 'guid' : 'title')),
				PluginManagerException::IMPORT_MISSING_DATA
			);
		}
		$guid = strtolower((string)$xml->guid);
		if($plugin = self::get($guid, 'guid')) {
			throw new PluginManagerException(
				__('exception', 'duplicate-plugin-guid', $plugin->guid, $plugin->name),
				PluginManagerException::IMPORT_DUPLICATE_GUID
			);
		}
		try {
			$tbl = ac_table('plugins');
			$sth = App::connection()->prepare("
			INSERT INTO `$tbl` (_guid, _directory, _name, _description, _author, _author_url, _plugin_url, _version, _license, _enabled)
			VALUES (:guid, :dir, :title, :description, :author, :author_url, :url, :version, :license, :enabled)
			");
			$sth->bindValue(':guid', $guid, \PDO::PARAM_STR);
			$sth->bindValue(':dir', ($dir_name ? $dir_name : $guid), \PDO::PARAM_STR);
			$sth->bindValue(':title', (string)$xml->title, \PDO::PARAM_STR);
			$sth->bindValue(':description', (string)$xml->description, \PDO::PARAM_LOB);
			$sth->bindValue(':author', (string)$xml->author, \PDO::PARAM_STR);
			$sth->bindValue(':author_url', (string)$xml->authorurl, \PDO::PARAM_STR);
			$sth->bindValue(':url', (string)$xml->pluginurl, \PDO::PARAM_STR);
			$sth->bindValue(':version', (string)$xml->version, \PDO::PARAM_STR);
			$sth->bindValue(':license', (string)$xml->license, \PDO::PARAM_STR);
			$sth->bindValue(':enabled', 'n', \PDO::PARAM_STR);
			$sth->execute();
			$id          = App::connection()->lastInsertId();
			$plugin_dir  = \Aqua\ROOT . self::DIRECTORY . '/' . ($dir_name ? $dir_name : $id . '-' . $guid);
			$old         = umask(0);
			if(!is_dir($plugin_dir)) {
				mkdir($dir, 644, true);
				$iter = new \RecursiveIteratorIterator(
					new \RecursiveDirectoryIterator(
						$dir,
						\RecursiveDirectoryIterator::SKIP_DOTS
					), \RecursiveIteratorIterator::SELF_FIRST);
				foreach($iter as $item) {
					if($item->isDir()) {
						mkdir($plugin_dir . '/' . $iter->getSubPathName(), \Aqua\PUBLIC_DIRECTORY_PERMISSION);
					}
					else {
						$name = $plugin_dir . '/' . $iter->getSubPathName();
						copy($item, $name);
						chmod($name, \Aqua\PUBLIC_FILE_PERMISSION);
					}
				}
			}
			umask($old);
			$plugin   = self::get($id);
			$feedback = array( $dir, $plugin );
			Event::fire('plugin.import', $feedback);

			return $plugin;
		} catch(\Exception $exception) {
			if(!empty($id)) {
				$tbl = ac_table('plugins');
				$sth = App::connection()->prepare("
				DELETE FROM `$tbl` WHERE id = :id;
				");
				$sth->bindParam(':id', $id, \PDO::PARAM_INT);
				$sth->execute();
			}
			throw $exception;
		}
	}

	/**
	 * Check whether a plugin exists and is enabled without fetching it from the database
	 *
	 * @param string $plugin
	 * @return bool
	 */
	public static function isEnabled($plugin)
	{
		return (isset(self::$pluginKeys[$plugin]) && self::$pluginKeys[$plugin]->isEnabled);
	}

	/**
	 * Scan /plugins for new plugins
	 */
	public static function scanDir()
	{
		$folders = glob(\Aqua\ROOT . self::DIRECTORY . '/*', GLOB_ONLYDIR);
		$plugins = self::search()->query()->results;
		$dirs    = array();
		foreach($plugins as $plugin) $dirs[basename($plugin->directory)] = 0;
		foreach($folders as $dir) {
			$base_name = basename($dir);
			if(isset($dirs[$base_name])) continue;
			try {
				self::import($dir, $base_name);
			} catch(PluginManagerException $e) {
			}
		}
	}

	/**
	 * Cache enabled plugins
	 * @param bool $force
	 * @return bool
	 */
	public static function rebuildCache($force = false)
	{
		$plugins = array();
		if($force) {
			$search = self::search()
				->where(array( 'enabled' => 'y' ))
				->query();
			foreach($search as $plugin) {
				$plugins[$plugin->id] = $plugin;
			}
		} else {
			$update = false;
			foreach(self::$pluginIds as $plugin) {
				if($plugin->isEnabled) {
					$plugins[$plugin->id] = $plugin;
					if($plugin->settings->updated) {
						$update = true;
					}
				}
			}
			if(!$update) {
				return false;
			}
		}
		App::cache()->store(self::CACHE_KEY, $plugins);

		return true;
	}

	/**
	 * @param array $data
	 * @return \Aqua\Plugin\Plugin
	 */
	public static function parsePluginSql(array $data)
	{
		if(isset(self::$pluginIds[$data['id']])) {
			$plugin = self::$pluginIds[$data['id']];
		}
		else {
			$plugin = new self;
		}
		$plugin->id          = (int)$data['id'];
		$plugin->guid        = $data['guid'];
		$plugin->author      = $data['author'];
		$plugin->authorUrl   = $data['author_url'];
		$plugin->pluginUrl   = $data['plugin_url'];
		$plugin->name        = $data['name'];
		$plugin->description = $data['description'];
		$plugin->license     = $data['license'];
		$plugin->version     = $data['version'];
		$plugin->folder      = $data['directory'];
		$plugin->directory   = \Aqua\ROOT . self::DIRECTORY . '/' . $data['directory'];
		$plugin->url         = \Aqua\URL . self::DIRECTORY . '/' . $data['directory'];
		if($data['enabled'] === 'y') {
			$plugin->isEnabled = true;
		}
		self::$pluginIds[$data['id']]     = $plugin;
		self::$pluginKeys[$data['guid']]  = $plugin;

		return $plugin;
	}
}
