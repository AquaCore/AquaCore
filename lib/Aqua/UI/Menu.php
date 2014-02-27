<?php
namespace Aqua\UI;

use Aqua\UI\Template;
use Aqua\UI\AbstractContent;

class Menu
extends AbstractContent
implements \Iterator, \Countable
{
	/**
	 * @var int
	 */
	public $depth = 0;

	/**
	 * @param string $key
	 * @param string $option
	 * @return mixed
	 */
	public function getOption($key, $option)
	{
		return isset($this->content[$key][$option]) ?
			$this->content[$key][$option] : null;
	}

	/**
	 * @param string $key
	 * @param        $option
	 * @param        $value
	 * @return \Aqua\UI\Menu
	 */
	public function setOption($key, $option, $value)
	{
		if($option !== 'submenu' && isset($this->content[$key])) {
			$this->content[$key][$option] = $value;
		}

		return $this;
	}

	/**
	 * @param string $key
	 * @return \Aqua\UI\Menu|null
	 */
	public function submenu($key)
	{
		if(!isset($this->content[$key])) {
			return null;
		} else {
			if(!$this->content[$key]['submenu'] instanceof self) {
				$this->content[$key]['submenu']         = new static;
				$this->content[$key]['submenu']->depth = $this->depth + 1;
			}
		}

		return $this->content[$key]['submenu'];
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function hasSubmenu($key)
	{
		return isset($this->content[$key]) && $this->content[$key]['submenu'] instanceof self;
	}

	/**
	 * @param string $template
	 * @return string
	 */
	public function render($template = 'default')
	{
		$tpl     = new Template();
		$options = $this->_prepareOptions($template);
		$tpl->set('options', $options);
		$tpl->set('depth', $this->depth);
		$tpl->set('class', implode(' ', $this->classes));

		return $tpl->render("menu/$template");
	}

	protected function _prepareOptions($template)
	{
		$options = array();
		foreach($this as $key => $option) {
			if($option['submenu'] instanceof self) {
				$option['submenu'] = $option['submenu']->render($template);
			}
			$options[] = $option;
		}

		return $options;
	}

	protected function _parseContent(array &$options)
	{
		$options = array_change_key_case($options);
		$options += array(
			'class'    => array(),
			'title'    => '',
			'url'      => '#',
			'submenu'  => null,
			'warnings' => 0
		);
		if(!empty($options['class'])) {
			$options['class'] = implode(' ', $options['class']);
		} else {
			$options['class'] = '';
		}
		$options['depth'] = $this->depth;
		if($options['submenu']) {
			$submenu                    = $options['submenu'];
			$options['submenu']         = new static;
			$options['submenu']->depth = $this->depth + 1;
			foreach($submenu as $key => $option) {
				$options['submenu']->append($key, $option);
			}
		}
	}
}
