<?php
namespace Aqua\UI;

use Aqua\Core\L10n;
use Aqua\UI\Tag\Script;

class ScriptManager
extends Script
{
	/**
	 * @var string
	 */
	public $key;
	/**
	 * @var array
	 */
	public $dependencies = array();
	/**
	 * @var array
	 */
	public $extra = array();
	/**
	 * @var array
	 */
	public $styles = array();
	/**
	 * @var string
	 */
	public $language;
	/**
	 * @var string
	 */
	public $defaultLanguage;
	/**
	 * @var \Aqua\UI\ScriptManager[]
	 */
	public static $scripts = array();

	/**
	 * @param string $key
	 */
	public function __construct($key)
	{
		$this->key = $key;
		$this->type('text/javascript');
		parent::__construct();
		self::$scripts[$key] = $this;
	}

	/**
	 * @param string|array $key
	 * @return \Aqua\UI\ScriptManager
	 */
	public function dependsOn($key)
	{
		if(is_array($key)) {
			$this->dependencies += $key;
		} else {
			$this->dependencies[] = $key;
		}

		return $this;
	}

	/**
	 * @param string|array $key
	 * @return \Aqua\UI\ScriptManager
	 */
	public function compliesWith($key)
	{
		if(is_array($key)) {
			$this->extra += $key;
		} else {
			$this->extra[] = $key;
		}

		return $this;
	}

	/**
	 * @param string|array $key
	 * @return \Aqua\UI\ScriptManager
	 */
	public function stylesheet($key)
	{
		if(is_array($key)) {
			$this->styles += $key;
		} else {
			$this->styles[] = $key;
		}

		return $this;
	}

	public function language($format, $default = null)
	{
		$this->language        = $format;
		$this->defaultLanguage = $default;

		return $this;
	}

	public function render()
	{
		$script = parent::render();
		if($this->language && $this->defaultLanguage !== L10n::getDefault()->code) {
			$script.= "\r\n<script type=\"text/javascript\" src=\"" .
			          sprintf($this->language, (strtolower(L10n::getDefault()->code))) .
		              "\"></script>";
		}
		return $script;
	}

	/**
	 * @param string $key
	 * @return \Aqua\UI\ScriptManager
	 */
	public static function register($key)
	{
		return new self($key);
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public static function exists($key)
	{
		return isset(self::$scripts[$key]);
	}

	/**
	 * @param string $key
	 * @param array  $options
	 * @return \Aqua\UI\ScriptManager
	 * @static
	 */
	public static function script($key, array $options = array())
	{
		if(!isset(self::$scripts[$key])) {
			return null;
		}
		$script = clone self::$scripts[$key];
		$src    = $script->getAttr('src');
		foreach($options as $option => $value) {
			$src = str_replace(":$option", $value, $src);
		}
		$src = preg_replace('/:[a-z0-9_-]+/i', '', $src);
		$script->src($src);

		return $script;
	}
}
