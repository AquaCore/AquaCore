<?php
namespace Aqua\UI;

class Tag
{
	/**
	 * @var string
	 */
	public $tag;
	/**
	 * @var array
	 */
	public $attributes = array();
	/**
	 * @var array
	 */
	public $boolean = array();
	/**
	 * @var bool
	 */
	public $closeTag = true;
	/**
	 * @var array
	 */
	public $content = array();
	/**
	 * @var array
	 */
	public $style = array();

	/**
	 * @param string $tag
	 */
	public function __construct($tag)
	{
		$this->tag = $tag;
	}

	/**
	 * @param string $attr
	 * @param string $value
	 * @return static
	 */
	public function attr($attr, $value)
	{
		$attr = strtolower($attr);
		if($value === false || $value === null) {
			unset($this->attributes[$attr]);
		} else {
			$this->attributes[$attr] = $value;
		}

		return $this;
	}

	/**
	 * @param string $attr
	 * @param string $default
	 * @return string
	 */
	public function getAttr($attr, $default = null)
	{
		$attr = strtolower($attr);

		return (isset($this->attributes[$attr]) ? $this->attributes[$attr] : $default);
	}

	/**
	 * @param string $style
	 * @param string $val
	 * @return static
	 */
	public function css($style, $val = null)
	{
		if(is_array($style)) {
			$this->style = array_merge($this->style, $style);
		} else {
			$this->style[$style] = $val;
		}

		return $this;
	}

	/**
	 * @param string $style
	 * @return string
	 */
	public function getCss($style)
	{
		return (isset($this->style[$style]) ? $this->style['style'] : null);
	}

	/**
	 * @param string $attr
	 * @param bool   $value
	 * @return static
	 */
	public function bool($attr, $value = true)
	{
		if($value) {
			$this->boolean[strtolower($attr)] = 1;
		} else {
			unset($this->boolean[strtolower($attr)]);
		}

		return $this;
	}

	/**
	 * @param string $attr
	 * @return bool
	 */
	public function getBool($attr)
	{
		return (isset($this->boolean[$attr]) && $this->boolean[$attr] ||
		        isset($this->attributes[$attr]) && $this->attributes[$attr] === $attr);
	}

	/**
	 * @param mixed $content
	 * @return static
	 */
	public function append($content)
	{
		$this->content[] = $content;

		return $this;
	}

	/**
	 * @param mixed $content
	 * @return static
	 */
	public function prepend($content)
	{
		array_unshift($this->content, $content);

		return $this;
	}

	/**
	 * @return \Aqua\UI\Tag
	 */
	public function clearContent()
	{
		$this->content = array();

		return $this;
	}

	/**
	 * @param int $position
	 * @return mixed
	 */
	public function get($position)
	{
		return isset($this->content[$position]) ?
			$this->content[$position] : null;
	}

	/**
	 * @return string
	 */
	public function render()
	{
		return $this->_renderOpenTag() . $this->_renderCloseTag();
	}

	protected function _renderOpenTag()
	{
		$tag = '<' . $this->tag;
		if(($style = $this->getAttr('style', '')) || !empty($this->style)) {
			$this->attributes['style'] = $style;
			if(!empty($style)) {
				$this->attributes['style'] .= ';';
			}
			foreach($this->style as $key => $value) {
				$this->attributes['style'] .= "$key: $value;";
			}
		}
		foreach($this->attributes as $attribute => $value) {
			if(is_array($value)) {
				$value = implode(' ', $value);
			}
			$tag .= " $attribute=\"$value\"";
		}
		if(!empty($this->boolean)) {
			$bool = array_keys($this->boolean);
			$tag .= ' ' . implode(' ', $bool);
		}
		$tag .= '>';
		$this->attributes['style'] = $style;

		return $tag;
	}

	protected function _renderCloseTag()
	{
		$tag = '';
		$tag .= $this->_renderContent();
		if($this->closeTag) {
			$tag .= "</{$this->tag}>";
		}

		return $tag;
	}

	protected function _renderContent()
	{
		$tag = '';
		foreach($this->content as $content) {
			if($content instanceof self) {
				$tag .= $content->render();
			} else {
				$tag .= $content;
			}
			$tag .= "\n";
		}

		return $tag;
	}

	public function __toString()
	{
		return $this->render();
	}

	public function __clone()
	{
		foreach($this->content as $key => $content) {
			if(is_object($content)) {
				$this->content[$key] = clone $content;
			}
		}
	}

	public function __call($attr, $arguments)
	{
		if(empty($arguments)) {
			return $this->bool($attr);
		} else {
			return $this->attr($attr, $arguments[0]);
		}
	}

}
