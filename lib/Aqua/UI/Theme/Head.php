<?php
namespace Aqua\UI\Theme;

use Aqua\Event\EventDispatcher;
use Aqua\UI\ScriptManager;
use Aqua\UI\Tag\Meta;

class Head
extends AbstractThemeComponent
{
	/**
	 * @var \Aqua\UI\Tag\Meta[]
	 */
	public $meta = array();

	/**
	 * @var string
	 */
	public $title = '';

	/**
	 * @var string
	 */
	public $section = '';

	/**
	 * @var string
	 */
	public $titleFormat = '%2$s - %1$s';

	/**
	 * @var \Aqua\Event\EventDispatcher
	 */
	protected $_dispatcher;

	public function __construct()
	{
		$this->_dispatcher = new EventDispatcher;
	}

	/**
	 * @param string $key
	 * @return \Aqua\UI\Tag\Meta
	 */
	public function enqueueMeta($key)
	{
		$this->meta[$key] = new Meta;

		return $this->meta[$key];
	}

	/**
	 * @param string $key
	 * @return \Aqua\UI\Theme\Head
	 */
	public function dequeueMeta($key)
	{
		unset($this->meta[$key]);

		return $this;
	}

	/**
	 * @param string $key
	 * @return \Aqua\UI\Tag\Meta|null
	 */
	public function meta($key)
	{
		return isset($this->meta[$key]) ? $this->meta[$key] : null;
	}

	/**
	 * @return string
	 */
	public function render()
	{
		$html = '<title>' . $this->title() . '</title>';
		$html .= "\n" . $this->renderMeta();
		$html .= "\n" . $this->renderLinks();
		$html .= "\n" . $this->renderScripts();
		$html .= "\n" . $this->renderStylesheets();
		$this->notify('render');

		return $html;
	}

	/**
	 * @return string
	 */
	public function title()
	{
		if(!$this->section) {
			return $this->title;
		} else {
			return sprintf($this->titleFormat, $this->title, $this->section);
		}
	}

	/**
	 * @return string
	 */
	public function renderMeta()
	{
		$html = '';
		foreach($this->meta as $meta) {
			$html .= $meta->render();
		}

		return $html;
	}

	public function notify($event, &$feedback = null)
	{
		$this->_dispatcher->notify("head.$event", $feedback);
	}

	public function attach($event, \Closure $listener)
	{
		$this->_dispatcher->attach("head.$event", $listener);
	}

	public function detach($event, \Closure $listener)
	{
		$this->_dispatcher->detach("head.$event", $listener);
	}
}
