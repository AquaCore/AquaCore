<?php
namespace Aqua\UI;

abstract class AbstractContent
implements \Iterator, \Countable
{
	/**
	 * @var array
	 */
	public $classes = array();
	/**
	 * @var array
	 */
	public $content = array();
	/**
	 * @var int
	 */
	public $size = 0;

	public function key()
	{
		return key($this->content);
	}

	public function current()
	{
		return current($this->content);
	}

	public function next()
	{
		next($this->content);
	}

	public function rewind()
	{
		return reset($this->content);
	}

	public function valid()
	{
		return current($this->content) !== false;
	}

	public function count()
	{
		return $this->size;
	}

	/**
	 * @param string $class
	 * @return \Aqua\UI\AbstractContent
	 */
	public function addClass($class)
	{
		$this->classes[] = $class;

		return $this;
	}

	/**
	 * @param string $key
	 * @param int    $position
	 * @param array  $options
	 * @return \Aqua\UI\AbstractContent
	 */
	public function add($key, $position, array $options)
	{
		$this->_parseContent($options);

		if(!is_int($position) || $position >= $this->size) {
			$this->append($key, $options);
		} else {
			$this->content = array_slice($this->content, 0, $position, true) +
				array( $key => $options ) +
				array_slice($this->content, $position, null, true);
			++$this->size;
		}

		return $this;
	}

	/**
	 * @param string $key
	 * @param array  $content
	 * @return \Aqua\UI\AbstractContent
	 */
	public function append($key, array $content)
	{
		$this->_parseContent($content);
		$this->content[$key] = $content;
		++$this->size;

		return $this;
	}

	/**
	 * @param string $key
	 * @param array  $content
	 * @return \Aqua\UI\AbstractContent
	 */
	public function prepend($key, array $content)
	{
		$this->_parseContent($content);
		$this->content[$key] = array( $key => $content ) + $this->content;
		++$this->size;

		return $this;
	}

	/**
	 * @param string $key
	 * @return \Aqua\UI\AbstractContent
	 */
	public function remove($key)
	{
		if(isset($this->content[$key])) {
			unset($this->content[$key]);
			--$this->size;
		}

		return $this;
	}

	/**
	 * @param string $key
	 * @return int|bool
	 */
	public function pos($key)
	{
		return array_search($key, array_keys($this->content));
	}

	/**
	 * @param string $key
	 * @return array
	 */
	public function &get($key)
	{
		if(!isset($this->content[$key])) {
			$this->append($key, array());
		}

		return $this->content[$key];
	}

	public function __toString()
	{
		return $this->render();
	}

	abstract public function render();

	abstract protected function _parseContent(array &$content);
}
