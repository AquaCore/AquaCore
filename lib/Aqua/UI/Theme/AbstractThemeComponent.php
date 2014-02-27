<?php
namespace Aqua\UI\Theme;

use Aqua\UI\ScriptManager;
use Aqua\UI\StyleManager;
use Aqua\UI\Tag\Link;
use Aqua\UI\Tag\Script;

abstract class AbstractThemeComponent
{
	/**
	 * @var \Aqua\UI\Tag\Link[]
	 */
	public $link = array();

	/**
	 * @var \Aqua\UI\Tag\Script[]
	 */
	public $script = array();

	/**
	 * @var \Aqua\UI\Tag\Link[]
	 */
	public $stylesheets = array();

	/**
	 * @param string $name
	 * @param string $css
	 * @param bool   $override
	 * @return \Aqua\UI\Theme\AbstractThemeComponent
	 */
	public function appendStylesheet($name, $css, $override = true)
	{
		if($override || !isset($this->stylesheets[$name])) {
			$this->stylesheets[$name] = $css;
		}

		return $this;
	}

	/**
	 * @param string $name
	 * @param string $css
	 * @param bool   $override
	 * @return \Aqua\UI\Theme\AbstractThemeComponent
	 */
	public function bindStylesheet($name, &$css, $override = true)
	{
		if($override || !isset($this->stylesheets[$name])) {
			$this->stylesheets[$name] = & $css;
		}

		return $this;
	}

	/**
	 * @param string $name
	 * @return \Aqua\UI\Tag\Link
	 */
	public function &stylesheet($name)
	{
		if(!isset($this->stylesheets[$name])) {
			$this->stylesheets[$name] = '';
		}

		return $this->stylesheets[$name];
	}

	/**
	 * @param string $name
	 * @return \Aqua\UI\Theme\AbstractThemeComponent
	 */
	public function removeStylesheet($name)
	{
		unset($this->stylesheets[$name]);

		return $this;
	}

	/**
	 * @return string
	 */
	public function renderStylesheets()
	{
		if(empty($this->stylesheets)) {
			return '';
		} else {
			return '<style type="text/css">' . implode($this->stylesheets) . '</style>';
		}
	}

	/**
	 * @param \Aqua\UI\StyleManager|string $key
	 * @return $this
	 */
	public function dequeueLink($key)
	{
		if($key instanceof ScriptManager) {
			unset($this->link[$key->key]);
		} else {
			unset($this->link[$key]);
		}

		return $this;
	}

	/**
	 * @param string $key
	 * @return \Aqua\UI\Tag\Link|null
	 */
	public function link($key)
	{
		return isset($this->link[$key]) ? $this->link[$key] : null;
	}

	/**
	 * @return string
	 */
	public function renderLinks()
	{
		$html = '';
		foreach($this->link as $link) {
			$html .= $link->render() . "\n";
		}

		return $html;
	}

	/**
	 * @param \Aqua\UI\ScriptManager|string $key
	 * @param bool                          $stylesheets
	 * @param bool                          $override
	 * @return \Aqua\UI\Tag\Script|null
	 */
	public function enqueueScript($key, $stylesheets = true, $override = false)
	{
		if($key instanceof ScriptManager) {
			if(!$override && isset($this->script[$key->key])) {
				return $this->script[$key->key];
			}
			foreach($key->dependencies as $dependency) {
				$this->enqueueScript(ScriptManager::script($dependency), $stylesheets, $override);
			}
			if($stylesheets) {
				foreach($key->styles as $style) {
					$this->enqueueLink(StyleManager::style($style));
				}
			}
			$this->script[$key->key] = $key;

			return $key;
		} else {
			if(!$override && ($script = $this->script($key))) {
				return $script;
			} else {
				$this->script[$key] = new Script;
				$this->script[$key]->type('text/javascript');

				return $this->script[$key];
			}
		}
	}

	/**
	 * @param \Aqua\UI\StyleManager|string $key
	 * @return \Aqua\UI\Tag\Link|Link
	 */
	public function enqueueLink($key)
	{
		if($key instanceof StyleManager) {
			$this->link[$key->key] = $key;

			return $key;
		} else {
			$this->link[$key] = new Link;

			return $this->link[$key];
		}
	}

	/**
	 * @param string $key
	 * @return \Aqua\UI\Tag\Script|null
	 */
	public function script($key)
	{
		return isset($this->script[$key]) ? $this->script[$key] : null;
	}

	/**
	 * @param string $key
	 * @return \Aqua\UI\Theme\AbstractThemeComponent
	 */
	public function dequeueScript($key)
	{
		if($key instanceof ScriptManager) {
			unset($this->script[$key->key]);
		} else {
			unset($this->script[$key]);
		}

		return $this;
	}

	/**
	 * @return string
	 */
	public function renderScripts()
	{
		$scripts  = array();
		$position = array();
		$keys     = array();
		$i        = 0;
		foreach($this->script as $name => $script) {
			if(isset($keys[$name])) {
				continue;
			}
			$keys[$name] = true;
			if($script instanceof ScriptManager) {
				$pos = null;
				foreach($script->extra as $key) {
					if(isset($position[$key])) {
						$pos = ($pos === null ? $position[$key] : min($pos, $position[$key]));
						unset($scripts[$position[$key]]);
						unset($position[$key]);
					}
					$keys[$key] = true;
				}
				if($pos !== null) {
					$position[$name] = $pos;
					$scripts[$pos]   = $script->render();
					continue;
				}
			}
			$scripts[$i]     = $script->render();
			$position[$name] = $i;
			++$i;
		}

		return implode("\n", $scripts);
	}

	abstract public function render();
}
