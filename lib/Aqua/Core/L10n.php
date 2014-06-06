<?php
namespace Aqua\Core;

use Aqua\Content\ContentType;
use Aqua\SQL\Query;
use Aqua\SQL\Search;
use Aqua\User\Role;

class L10n
{
	/**
	 * @var string
	 */
	public static $code;
	/**
	 * @var string
	 */
	public static $name;
	/**
	 * @var string
	 */
	public static $direction;
	/**
	 * @var array
	 */
	public static $locales = array();
	/**
	 * @var array
	 */
	public static $phrases = array();
	/**
	 * @var string
	 */
	protected static $_defaultLanguage = 'en';

	const CACHE_DIR = '/tmp/lang';

	protected function __construct() { }

	/**
	 * @param string $namespace
	 * @return array
	 */
	public static function getNamespace($namespace)
	{
		isset(self::$phrases[$namespace]) or self::_namespaceLoad($namespace);

		return self::$phrases[$namespace];
	}

	/**
	 * @param string $namespace
	 * @param string $key
	 * @return string
	 */
	public static function dictionary($namespace, $key)
	{
		isset(self::$phrases[$namespace]) or self::_namespaceLoad($namespace);

		return (isset(self::$phrases[$namespace][$key]) ? self::$phrases[$namespace][$key] : $key);
	}

	/**
	 * @param string $namespace
	 * @param string $key
	 * @return bool
	 */
	public static function exists($namespace, $key = null)
	{
		isset(self::$phrases[$namespace]) or self::_namespaceLoad($namespace);
		if(empty(self::$phrases[$namespace]) || ($key && empty(self::$phrases[$namespace][$key]))) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * @param string $namespace
	 * @return array
	 */
	public static function rangeList($namespace)
	{
		$list = array();
		$count = func_num_args();
		for($i = 1; $i < $count; ++$i) {
			foreach(func_get_arg($i) as $name) {
				$list[$name] = self::dictionary($namespace, $name) ?: $name;
			}
		}
		return $list;
	}

	/**
	 * @param string $namespace
	 * @access protected
	 */
	protected static function _namespaceLoad($namespace)
	{
		$file = \Aqua\ROOT . self::CACHE_DIR . "/$namespace";
		if(file_exists($file)) {
			self::$phrases[$namespace] = unserialize(file_get_contents($file));
		} else {
			self::$phrases[$namespace] = array();
		}
	}

	public static function init()
	{
		$settings = App::settings()->get('language');
		self::$code      = $settings->get('code', 'en');
		self::$name      = $settings->get('name', 'English');
		self::$direction = $settings->get('direction', 'LTR');
		self::$locales   = $settings->get('locales')->toArray();
		$locales = self::$locales;
		if(empty(self::$locales)) {
			return;
		}
		array_unshift($locales, LC_ALL);
		call_user_func_array('setlocale', $locales);
	}

	/**
	 * @param string $namespace
	 * @param mixed  $str
	 * @param array  $sprintf
	 * @return string
	 * @static
	 */
	public static function replace($namespace, $str, array $sprintf = array())
	{
		$str = self::dictionary($namespace, $str);
		if(empty($sprintf)) {
			return $str;
		} else {
			array_unshift($sprintf, $str);

			return call_user_func_array('sprintf', $sprintf);
		}
	}

	public static function setLanguage(\SimpleXMLElement $xml)
	{
		if(!$xml->language) {
			return false;
		}
		$settings = new Settings(array());
		$settings->set('name', (string)$xml->language->name);
		$settings->set('code', (string)$xml->language->code);
		switch(strtoupper((string)$xml->language->direction)) {
			case 'LTR':
			case '1':
				$settings->set('direction', 'LRT');
				break;
			case 'RTL':
			case '2':
				$settings->set('direction', 'RTL');
				break;
		}
		$locales = array();
		foreach($xml->language->locale as $lc) {
			$locales[] = (string)$lc;
		}
		$settings->set('locales', $locales);
		App::settings()->set('language', $settings)->export(\Aqua\ROOT . '/settings/application.php');
		self::$code = $settings->get('code', '');
		self::$name = $settings->get('name', '');
		self::import($xml, null, true);
		return true;
	}

	/**
	 * @param \SimpleXMLElement $xml
	 * @param int|null          $pluginId
	 * @param bool              $rebuildCache
	 */
	public static function import(\SimpleXMLElement $xml, $pluginId = null, $rebuildCache = true)
	{
		self::importWordGroup($xml, $pluginId, $rebuildCache);
		self::importEmailGroup($xml, $pluginId);
		self::importTaskGroup($xml);
		self::importPermissionGroup($xml, $rebuildCache);
		self::importContentTypeGroup($xml, $rebuildCache);
	}

	public static function importWordGroup(\SimpleXMLElement $xml, $pluginId = null, $rebuildCache = true)
	{
		$sth = App::connection()->prepare(sprintf('
		REPLACE INTO `%s` (_namespace, _key, _phrase, _plugin_id)
		VALUES (:namespace, :key, :word, :plugin)
		', ac_table('phrases')));
		$namespaces = array();
		foreach($xml->wordgroup as $wordgroup) {
			$lang = (string)$wordgroup->attributes()->language;
			if($lang && strcasecmp($lang, self::$code) !== 0) {
				continue;
			}
			foreach($wordgroup->word as $word) {
				$namespace = (string)$word->attributes()->namespace;
				$key       = (string)$word->attributes()->key;
				if($namespace === '' || $key === '') {
					continue;
				}
				$namespaces[] = $namespace;
				$sth->bindValue(':namespace', $namespace, \PDO::PARAM_STR);
				$sth->bindValue(':key', $key, \PDO::PARAM_STR);
				$sth->bindValue(':word', (string)$word, \PDO::PARAM_STR);
				if($pluginId) $sth->bindValue(':plugin', $pluginId, \PDO::PARAM_INT);
				else $sth->bindValue(':plugin', null, \PDO::PARAM_NULL);
				$sth->execute();
			}
		}
		if($rebuildCache) {
			self::rebuildCache($namespaces);
		}
	}

	public static function importEmailGroup(\SimpleXMLElement $xml, $pluginId = null)
	{
		$sth = App::connection()->prepare(sprintf('
		REPLACE INTO `%s` (_key, _name, _default_subject, _default_body, _plugin_id)
		VALUES (:key, :name, :subject, :body, :plugin)
		', ac_table('email_templates')));

		foreach($xml->emailgroup as $emailgroup) {
			$lang = (string)$emailgroup->attributes()->language;
			if($lang && strcasecmp($lang, self::$code) !== 0) {
				continue;
			}
			foreach($emailgroup->email as $email) {
				$key = (string)$email->attributes()->key;
				if($key === '') {
					continue;
				}
				$sth->bindValue(':key', $key, \PDO::PARAM_STR);
				$sth->bindValue(':name', (string)$email->name, \PDO::PARAM_STR);
				$sth->bindValue(':subject', (string)$email->subject, \PDO::PARAM_STR);
				$sth->bindValue(':body', (string)$email->body, \PDO::PARAM_STR);
				if($pluginId === null) $sth->bindValue(':plugin', null, \PDO::PARAM_NULL);
				else $sth->bindValue(':plugin', $pluginId, \PDO::PARAM_INT);
				$sth->execute();
				if($email->placeholder) {
					self::_importEmailPlaceholders($email, $key);
				}
			}
		}
	}

	protected static function _importEmailPlaceholders(\SimpleXMLElement $xml, $emailKey)
	{
		$sth = App::connection()->prepare(sprintf('
		REPLACE INTO `%s` (_email, _key, _description)
		VALUES (:email, :key, :description)
		', ac_table('email_placeholders')));
		foreach($xml->placeholder as $placeholder) {
			$key = (string)$placeholder->attributes()->key;
			if($key === '') {
				continue;
			}
			$sth->bindValue(':email', $emailKey, \PDO::PARAM_STR);
			$sth->bindValue(':key', $key, \PDO::PARAM_STR);
			$sth->bindValue(':description', (string)$placeholder, \PDO::PARAM_STR);
			$sth->execute();
		}
	}

	public static function importTaskGroup(\SimpleXMLElement $xml)
	{
		$sth = App::connection()->prepare(sprintf('
		UPDATE `%s`
		SET _title = :title,
			_description = :desc
		WHERE _name = :name
		LIMIT 1
		', ac_table('tasks')));
		foreach($xml->taskgroup as $taskgroup) {
			$lang = (string)$taskgroup->attributes()->language;
			if($lang && strcasecmp($lang, self::$code) !== 0) {
				continue;
			}
			foreach($taskgroup->task as $task) {
				$sth->bindValue(':name', (string)$task->attributes()->name, \PDO::PARAM_STR);
				$sth->bindValue(':title', (string)$task->title, \PDO::PARAM_STR);
				if($task->description) $sth->bindValue(':desc', (string)$task->description, \PDO::PARAM_STR);
				else $sth->bindValue(':desc', null, \PDO::PARAM_NULL);
				$sth->execute();
				$sth->closeCursor();
			}
		}
	}

	public static function importPermissionGroup(\SimpleXMLElement $xml, $rebuildCache = true)
	{
		$sth = App::connection()->prepare(sprintf('
		UPDATE `%s`
		SET _name = :name,
			_description = :desc
		WHERE _permission = :key
		LIMIT 1
		', ac_table('permissions')));
		foreach($xml->permissiongroup as $permissiongroup) {
			$lang = (string)$permissiongroup->attributes()->language;
			if($lang && strcasecmp($lang, self::$code) !== 0) {
				continue;
			}
			foreach($permissiongroup->permission as $permission) {
				$sth->bindValue(':key', (string)$permission->attributes()->key, \PDO::PARAM_STR);
				$sth->bindValue(':name', (string)$permission->name, \PDO::PARAM_STR);
				if($permission->description) $sth->bindValue(':desc', (string)$permission->description, \PDO::PARAM_STR);
				else $sth->bindValue(':desc', null, \PDO::PARAM_NULL);
				$sth->execute();
				$sth->closeCursor();
			}
		}
		if($rebuildCache) {
			Role::rebuildPermissionCache();
		}
	}

	public static function importContentTypeGroup(\SimpleXMLElement $xml, $rebuildCache = true)
	{
		$sth = App::connection()->prepare(sprintf('
		UPDATE `%s`
		SET _name = :name,
			_item_name = :item
		WHERE _key = :key
		LIMIT 1
		', ac_table('content_type')));
		foreach($xml->contenttypegroup as $ctypegroup) {
			$lang = (string)$ctypegroup->attributes()->language;
			if($lang && strcasecmp($lang, self::$code) !== 0) {
				continue;
			}
			foreach($ctypegroup->contenttype as $ctype) {
				$sth->bindValue(':key', (string)$ctype->attributes()->key, \PDO::PARAM_STR);
				$sth->bindValue(':name', (string)$ctype->name, \PDO::PARAM_STR);
				if($ctype->itemname) {
					$sth->bindValue(':item', (string)$ctype->itemname, \PDO::PARAM_STR);
				} else {
					$sth->bindValue(':name', (string)$ctype->name, \PDO::PARAM_STR);
				}
				$sth->execute();
				$sth->closeCursor();
			}
		}
		if($rebuildCache) {
			ContentType::rebuildCache();
		}
	}

	public static function rebuildCache(array $namespaces = null)
	{
		if(empty($namespaces)) {
			ac_delete_dir(\Aqua\ROOT . self::CACHE_DIR, false);
		}
		$select = Query::select(App::connection())
			->columns(array(
				'namespace' => '_namespace',
			    'key'       => '_key',
			    'phrase'    => '_phrase'
			))
			->from(ac_table('phrases'))
			->order(array( '_namespace', '_key' ));
		if(!empty($namespaces)) {
			array_unshift($namespaces, Search::SEARCH_IN);
			$select->where(array( '_namespace' => $namespaces ));
		}
		$select->query();
		if(!$select->count()) {
			return;
		}
		$phrases = array();
		foreach($select as $data) {
			$phrases[$data['namespace']][$data['key']] = $data['phrase'];
		}
		$old = umask(0);
		foreach($phrases as $namespace => $keys) {
			ksort($keys, SORT_NUMERIC);
			$file = \Aqua\ROOT . self::CACHE_DIR . "/$namespace";
			file_put_contents($file, serialize($keys));
			chmod($file, \Aqua\PRIVATE_FILE_PERMISSION);
		}
		umask($old);
	}
}
