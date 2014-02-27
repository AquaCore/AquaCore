<?php
namespace Aqua\UI\Theme;

class Footer
extends AbstractThemeComponent
{
	/**
	 * @return string
	 */
	public function render()
	{
		return $this->renderLinks() . "\n" . $this->renderScripts() . "\n" . $this->renderStylesheets();
	}
}
