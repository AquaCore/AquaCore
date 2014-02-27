<?php
namespace Aqua\UI\Tag;

use Aqua\UI\Tag;

class Script
extends Tag
{
	/**
	 * @var integer
	 */
	public $execKey;
	/**
	 * @var string
	 */
	public $version;

	public function __construct()
	{
		parent::__construct('script');
		$this->closeTag = true;
	}

	/**
	 * @param string $value
	 * @return $this
	 */
	public function charset($value)
	{
		$this->attributes['charset'] = $value;

		return $this;
	}

	/**
	 * @param string $value
	 * @return $this
	 */
	public function src($value)
	{
		$this->attributes['src'] = $value;

		return $this;
	}

	/**
	 * @param string $value
	 * @return $this
	 */
	public function type($value)
	{
		$this->attributes['type'] = $value;

		return $this;
	}
}
