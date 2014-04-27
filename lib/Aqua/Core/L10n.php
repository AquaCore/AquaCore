<?php
namespace Aqua\Core;

class L10n
{
	/**
	 * @var int
	 */
	public $id;
	/**
	 * @var string
	 */
	public $code;
	/**
	 * @var string
	 */
	public $name;
	/**
	 * @var string
	 */
	public $direction;
	/**
	 * @var array
	 */
	public $locales = array();
	/**
	 * @var array
	 */
	public $dictionary = array();
	/**
	 * @var \Aqua\Core\L10n[]
	 */
	public static $languages = array();
	/**
	 * @var string
	 */
	protected static $_defaultLanguage = 'en';

	const CACHE_DIR = '/tmp/lang';
	const LANG_DIR  = '/language';

	protected function __construct() { }

	public function __sleep()
	{
		return array( 'id', 'code', 'name', 'direction', 'locales' );
	}

	/**
	 * @param string $namespace
	 * @return array
	 */
	public function getNamespace($namespace)
	{
		isset($this->dictionary[$namespace]) or $this->_namespaceLoad($namespace);

		return $this->dictionary[$namespace];
	}

	/**
	 * @param string $namespace
	 * @param string $key
	 * @return string
	 */
	public function dictionary($namespace, $key)
	{
		isset($this->dictionary[$namespace]) or $this->_namespaceLoad($namespace);

		return isset($this->dictionary[$namespace][$key]) ? $this->dictionary[$namespace][$key] : $key;
	}

	/**
	 * @param string $namespace
	 * @param string $key
	 * @return bool
	 */
	public function exists($namespace, $key = null)
	{
		isset($this->dictionary[$namespace]) or $this->_namespaceLoad($namespace);
		if(empty($this->dictionary[$namespace]) || ($key && empty($this->dictionary[$namespace][$key]))) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * @param string $key
	 * @param array  $replacements
	 * @param        $title
	 * @param        $content
	 * @return bool
	 */
	public function email($key, array $replacements, &$title, &$content)
	{
		$tbl = ac_table('email_translations');
		$sth = App::connection()->prepare("
		SELECT _title, _body
		FROM `$tbl`
		WHERE _language_id = ? AND _email_name = ?
		LIMIT 1
		");
		$sth->bindValue(1, $this->id, \PDO::PARAM_INT);
		$sth->bindValue(2, $key, \PDO::PARAM_STR);
		$sth->execute();
		if(!($data = $sth->fetch(\PDO::FETCH_NUM))) {
			return false;
		}
		list($title, $content) = $data;
		$search  = array();
		$replace = array();
		foreach($replacements as $key => $word) {
			$search[]  = ":$key";
			$replace[] = $word;
		}
		unset($replacements);
		$title   = str_replace($search, $replace, $title);
		$content = str_replace($search, $replace, $content);

		return true;
	}

	/**
	 * @return array
	 */
	public function emails()
	{
		$tbl = ac_table('emails');
		$sth = App::connection()->prepare("SELECT _name, _placeholders FROM `$tbl`");
		$sth->bindValue(1, $this->id, \PDO::PARAM_INT);
		$sth->execute();
		$emails = array();
		while($data = $sth->fetch(\PDO::FETCH_NUM)) {
			$emails[$data[0]] = unserialize($data[1]);
		}

		return $emails;
	}

	/**
	 * @param string $key
	 * @param string $title
	 * @param string $body
	 * @return bool
	 */
	public function updateEmail($key, $title, $body)
	{
		$etbl = ac_table('emails');
		$ttbl = ac_table('email_translations');
		$sth  = App::connection()->prepare("
		SELECT COUNT(1) FROM `$etbl`
		WHERE _name = ?
		");
		$sth->bindValue(1, $key, \PDO::PARAM_STR);
		$sth->execute();
		if(!$sth->fetchColumn(0)) {
			return false;
		}
		$sth = App::connection()->prepare("
		INSERT INTO `$ttbl` (_language_id, _email_name, _title, _body)
		VALUES (:language, :key, :title, :body)
		ON DUPLICATE KEY UPDATE _title = VALUES(_title), _body = VALUES(_body)
		");
		$sth->bindValue(':key', $key, \PDO::PARAM_STR);
		$sth->bindValue(':language', $this->id, \PDO::PARAM_INT);
		$sth->bindValue(':title', $title, \PDO::PARAM_STR);
		$sth->bindValue(':body', $body, \PDO::PARAM_STR);
		$sth->execute();

		return (bool)$sth->rowCount();
	}

	public function rangeList($namespace)
	{
		$list = array();
		$count = func_num_args();
		for($i = 1; $i < $count; ++$i) {
			foreach(func_get_arg($i) as $name) {
				$list[$name] = $this->dictionary($namespace, $name) ?: $name;
			}
		}
		return $list;
	}

	/**
	 * @param string $namespace
	 * @access protected
	 */
	protected function _namespaceLoad($namespace)
	{
		$file = \Aqua\ROOT . self::CACHE_DIR . "/$this->id/$namespace.cache";
		if(file_exists($file)) {
			$this->dictionary[$namespace] = unserialize(file_get_contents($file));
		} else {
			$this->dictionary[$namespace] = array();
		}
	}

	/**
	 * @param string $default_language
	 */
	public static function init($default_language)
	{
		$file = \Aqua\ROOT . self::CACHE_DIR . '/lang.cache';
		if(!file_exists($file)) {
			self::rebuildCache();
		} else {
			self::$languages = unserialize(file_get_contents($file));
		}
		self::setDefault($default_language);
	}

	/**
	 * @param string $namespace
	 * @param mixed  $str
	 * @param array  $sprintf
	 * @param string $locale
	 * @return string
	 * @static
	 */
	public static function translate($namespace, $str, array $sprintf = array(), $locale = null)
	{
		if(!$locale) {
			$locale = self::$_defaultLanguage;
		}
		if($locale = self::get($locale)) {
			$str = $locale->dictionary($namespace, $str);
		}
		if(empty($sprintf)) {
			return $str;
		} else {
			array_unshift($sprintf, $str);

			return call_user_func_array('sprintf', $sprintf);
		}
	}

	/**
	 * Set the application's language
	 *
	 * @param int $id
	 */
	public static function setDefault($id)
	{
		if(!isset(self::$languages[$id])) {
			self::$_defaultLanguage = key(self::$languages);
		} else {
			self::$_defaultLanguage = $id;
		}
		if(!($lang = self::getDefault())) {
			return;
		}
		$locales = $lang->locales;
		if(empty($locales)) return;
		array_unshift($locales, LC_ALL);
		call_user_func_array('setlocale', $locales);
	}

	/**
	 * @return \Aqua\Core\L10n
	 */
	public static function getDefault()
	{
		return self::get(self::$_defaultLanguage);
	}

	/**
	 * @param string $code
	 * @return \Aqua\Core\L10n|null
	 */
	public static function get($code)
	{
		if(!isset(self::$languages[$code])) return null;

		return self::$languages[$code];
	}

	/**
	 * @param \SimpleXMLElement $xml
	 * @param int|null          $plugin_id
	 */
	public static function import(\SimpleXMLElement $xml, $plugin_id = null)
	{
		if($plugin_id === null) {
			$tbl      = ac_table('languages');
			$lang_sth = App::connection()->prepare("
			INSERT IGNORE INTO `$tbl` (_code, _name, _direction)
			VALUES (:code, :name, :direction)
			");
			$tbl        = ac_table('language_locales');
			$locale_sth = App::connection()->prepare("
			REPLACE INTO `$tbl` (_language_id, _locale)
			VALUES (:language, :locale)
			");
			foreach($xml->language as $language) {
				if(!($code = (string)$language->code) ||
				   !($name = (string)$language->name) ||
				   self::get($code)) {
					continue;
				}
				switch(strtoupper((string)$language->direction)) {
					default:
					case 'LTR':
					case '1':
						$dir = 'LTR';
						break;
					case 'RTL':
					case '2':
						$dir = 'RTL';
						break;
				}
				$lang_sth->bindValue(':code', $code, \PDO::PARAM_STR);
				$lang_sth->bindValue(':name', $name, \PDO::PARAM_STR);
				$lang_sth->bindValue(':direction', $dir, \PDO::PARAM_STR);
				$lang_sth->execute();
				$id = App::connection()->lastInsertId();
				$lang_sth->closeCursor();
				if(!$id) {
					continue;
				}
				$lang                   = new self;
				$lang->id               = (int)$id;
				$lang->code             = $code;
				$lang->name             = $name;
				$lang->direction        = $dir;
				self::$languages[$code] = $lang;
				foreach($language->locale as $locale) {
					$locale_sth->bindValue(':language', $lang->id, \PDO::PARAM_INT);
					$locale_sth->bindValue(':locale', (string)$locale, \PDO::PARAM_STR);
					$locale_sth->execute();
					$locale_sth->closeCursor();
					$lang->locales[] = (string)$locale;
				}
			}
			unset($lang_sth, $locale_sth);
		}
		$tbl = ac_table('language_words');
		$sth = App::connection()->prepare("
		INSERT IGNORE INTO `$tbl` (_language_id, _namespace, _key, _word, _plugin_id)
		VALUES (:language, :namespace, :key, :word, :plugin)
		");
		foreach($xml->wordgroup as $wordgroup) {
			$lang = (string)$wordgroup->attributes()->language;
			if($lang === '' || !($lang = self::get($lang))) {
				continue;
			}
			foreach($wordgroup->word as $word) {
				$namespace = (string)$word->attributes()->namespace;
				$key       = (string)$word->attributes()->key;
				if($namespace === '' || $key === '') {
					continue;
				}
				$sth->bindValue(':language', $lang->id, \PDO::PARAM_INT);
				$sth->bindValue(':namespace', $namespace, \PDO::PARAM_STR);
				$sth->bindValue(':key', $key, \PDO::PARAM_STR);
				$sth->bindValue(':word', (string)$word, \PDO::PARAM_STR);
				if($plugin_id) $sth->bindValue(':plugin', $plugin_id, \PDO::PARAM_INT);
				else $sth->bindValue(':plugin', null, \PDO::PARAM_NULL);
				$sth->execute();
				$sth->closeCursor();
			}
		}
		$tbl       = ac_table('emails');
		$email_sth = App::connection()->prepare("
		INSERT IGNORE INTO `$tbl` (_name, _plugin_id, _placeholders)
		VALUES (:name, :plugin, :placeholders)
		");
		$tbl             = ac_table('email_translations');
		$translation_sth = App::connection()->prepare("
		REPLACE INTO `$tbl` (_email_name, _language_id, _title, _body)
		VALUES (:name, :language, :title, :body)
		");
		foreach($xml->emailgroup as $emailgroup) {
			$lang = (string)$emailgroup->attributes()->language;
			if($lang === '' || !($lang = self::get($lang))) {
				continue;
			}
			foreach($emailgroup->email as $email) {
				$key = (string)$email->attributes()->key;
				if($key === '') continue;
				$x = (array)$email;
				if(isset($x['placeholder'])) {
					$placeholders = array_values($x['placeholders']);
				} else {
					$placeholders = array();
				}
				$email_sth->bindValue(':name', $key, \PDO::PARAM_STR);
				$email_sth->bindValue(':placeholders', serialize($placeholders), \PDO::PARAM_STR);
				if($plugin_id === null) $email_sth->bindValue(':plugin', null, \PDO::PARAM_NULL);
				else $email_sth->bindValue(':plugin', $plugin_id, \PDO::PARAM_INT);
				$email_sth->execute();
				$email_sth->closeCursor();
				$title = (string)$email->title;
				$body  = (string)$email->body;
				$translation_sth->bindValue(':name', $key, \PDO::PARAM_STR);
				$translation_sth->bindValue(':language', $lang->id, \PDO::PARAM_INT);
				$translation_sth->bindValue(':title', $title, \PDO::PARAM_STR);
				$translation_sth->bindValue(':body', $body, \PDO::PARAM_STR);
				$translation_sth->execute();
				$translation_sth->closeCursor();
			}
		}
		self::rebuildCache();
	}

	public static function rebuildCache()
	{
		ac_delete_dir(\Aqua\ROOT . self::CACHE_DIR, false);
		self::$languages = array();
		$tbl = ac_table('languages');
		$sth = App::connection()->query("
		SELECT id,
		       _code,
		       _name,
		       _direction
		FROM `$tbl`
		");
		$languages = array();
		while($data = $sth->fetch(\PDO::FETCH_NUM)) {
			$lang                         = new self;
			$lang->id                     = (int)$data[0];
			$lang->code                   = $data[1];
			$lang->name                   = $data[2];
			$lang->direction              = $data[3];
			$languages[$lang->id]         = $lang;
			self::$languages[$lang->code] = $lang;
		}
		$tbl = ac_table('language_locales');
		$sth = App::connection()->query("
		SELECT _locale, _language_id
		FROM  `$tbl`
		");
		while($data = $sth->fetch(\PDO::FETCH_NUM)) {
			if(!isset($languages[$data[1]])) continue;
			$languages[$data[1]]->locales[] = $data[0];
		}
		$tbl = ac_table('language_words');
		$sth = App::connection()->query("
		SELECT _language_id,
		       _namespace,
		       _key,
		       _word
		FROM `$tbl`
		ORDER BY _language_id, _namespace, _key
		");
		while($data = $sth->fetch(\PDO::FETCH_NUM)) {
			if(!isset($languages[$data[0]])) continue;
			$languages[$data[0]]->dictionary[$data[1]][$data[2]] = $data[3];
		}
		$old = umask(0);
		foreach($languages as $lang) {
			$dir = \Aqua\ROOT . self::CACHE_DIR . "/{$lang->id}";
			mkdir($dir, \Aqua\PRIVATE_DIRECTORY_PERMISSION);
			foreach($lang->dictionary as $namespace => $words) {
				krsort($words, SORT_NUMERIC);
				file_put_contents("$dir/{$namespace}.cache", serialize($words));
				chmod("$dir/{$namespace}.cache", \Aqua\PRIVATE_FILE_PERMISSION);
			}
		}
		file_put_contents(\Aqua\ROOT . self::CACHE_DIR . '/lang.cache', serialize(self::$languages));
		chmod(\Aqua\ROOT . self::CACHE_DIR . '/lang.cache', \Aqua\PRIVATE_FILE_PERMISSION);
		umask($old);
	}
}
