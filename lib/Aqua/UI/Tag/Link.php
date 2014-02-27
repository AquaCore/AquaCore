<?php
namespace Aqua\UI\Tag;

use Aqua\UI\Tag;

class Link
extends Tag
{
	const MEDIA_ONLY = 1;
	const MEDIA_NOT  = 2;

	public function __construct()
	{
		parent::__construct('link');
		$this->closeTag = false;
	}

	/**
	 * @param string $value
	 * @return \Aqua\UI\Tag\Link
	 */
	public function type($value)
	{
		$this->attributes['type'] = $value;

		return $this;
	}

	/**
	 * @param string $value
	 * @return \Aqua\UI\Tag\Link
	 */
	public function rel($value)
	{
		$this->attributes['rel'] = $value;

		return $this;
	}

	/**
	 * @param string $value
	 * @return \Aqua\UI\Tag\Link
	 */
	public function href($value)
	{
		$this->attributes['href'] = $value;

		return $this;
	}

	/**
	 * @param string $value
	 * @return $this
	 */
	public function hreflang($value)
	{
		$this->attributes['hreflang'] = $value;

		return $this;
	}

	/**
	 * @param array $devices
	 * @param array $values
	 * @param int   $options
	 * @return \Aqua\UI\Tag\Link
	 */
	public function media(array $devices = array(), array $values = array(), $options = self::MEDIA_ONLY)
	{
		if(empty($devices) && empty($values)) {
			return $this;
		}
		$query = '';
		if($options === self::MEDIA_NOT) {
			$query = 'not ';
		} else {
			if($options === self::MEDIA_ONLY) {
				$query = 'only ';
			}
		}
		$query .= '(' . implode(' and ', $devices);
		foreach($values as $key => $value) {
			if(is_int($key)) {
				$query .= "($value) and";
			} else {
				$query .= "($key: $value) and";
			}
			$query = substr($query, 0, -4);
		}
		$query .= ')';
		if(isset($this->attributes['media'])) {
			$this->attributes['media'] .= ", $query";
		} else {
			$this->attributes['media'] = $query;
		}

		return $this;
	}
}
