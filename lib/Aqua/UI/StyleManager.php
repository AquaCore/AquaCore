<?php
namespace Aqua\UI;

use Aqua\UI\Tag\Link;

class StyleManager
	extends Link
{
	/**
	 * @var string
	 */
	public $key;
	/**
	 * @var \Aqua\UI\StyleManager[]
	 */
	public static $styles;

	/**
	 * @param string $key
	 */
	public function __construct($key)
	{
		$this->key = $key;
		$this->rel('stylesheet');
		$this->type('text/css');
		parent::__construct();
		self::$styles[$key] = $this;
	}

	/**
	 * @return string
	 */
	public function getKey()
	{
		return $this->key;
	}

	/**
	 * @param $key
	 * @return \Aqua\UI\StyleManager
	 * @static
	 */
	public static function register($key)
	{
		return new self($key);
	}

	/**
	 * @param $key
	 * @return \Aqua\UI\StyleManager
	 * @static
	 */
	public static function style($key)
	{
		return isset(self::$styles[$key]) ? self::$styles[$key] : null;
	}
}
