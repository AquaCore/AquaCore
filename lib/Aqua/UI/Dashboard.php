<?php
namespace Aqua\UI;

class Dashboard
extends AbstractContent
{
	/**
	 * @var \Aqua\UI\DashboardGroup[]
	 */
	public $content = array();

	/**
	 * @param string $template
	 * @return string
	 */
	public function render($template = 'default')
	{
		$html = '';
		foreach($this->content as $group) {
			$html .= $group->render($template);
		}

		return $html;
	}

	public function _parseContent(array &$content)
	{
		$content += array(
			'class'         => '',
			'items_per_row' => 3
		);
		$content = new DashboardGroup($content);
	}
}
