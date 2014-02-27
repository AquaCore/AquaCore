<?php
namespace Aqua\UI\Tag;

use Aqua\UI\Tag;

class Meta
extends Tag
{
	public function __construct()
	{
		parent::__construct('meta');
	}

	/**
	 * @param string $value
	 * @return \Aqua\UI\Tag\Meta
	 */
	public function charset($value)
	{
		$this->attributes['charset'] = $value;

		return $this;
	}

	/**
	 * @param string $value
	 * @return \Aqua\UI\Tag\Meta
	 */
	public function http_equiv($value)
	{
		$this->attributes['http-equiv'] = $value;

		return $this;
	}

	/**
	 * @param string $value
	 * @return \Aqua\UI\Tag\Meta
	 */
	public function content($value)
	{
		$this->attributes['content'] = $value;

		return $this;
	}

	/**
	 * @param string $value
	 * @return \Aqua\UI\Tag\Meta
	 */
	public function name($value)
	{
		$this->attributes['name'] = $value;

		return $this;
	}
}
