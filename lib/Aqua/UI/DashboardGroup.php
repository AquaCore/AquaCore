<?php
namespace Aqua\UI;

class DashboardGroup
	extends AbstractContent
{
	/**
	 * @var int
	 */
	public $itemsPerRow = 3;
	/**
	 * @var string
	 */
	public $class = '';

	/**
	 * @param array $options
	 */
	public function __construct(array $options)
	{
		$options += array(
			'class'         => '',
			'items_per_row' => 1
		);
		$this->class       = $options['class'];
		$this->itemsPerRow = $options['items_per_row'];
	}

	/**
	 * @param string $template
	 * @return string
	 */
	public function render($template = 'default')
	{
		$tpl = new Template;
		$tpl->set('content', $this->content)
		->set('class', $this->class)
		->set('maxItems', $this->itemsPerRow);

		return $tpl->render('dashboard/' . $template);
	}

	public function _parseContent(array &$content)
	{
		$content += array(
			'colspan' => null,
			'span'    => 1,
			'class'   => '',
			'title'   => '',
			'header'  => null,
			'content' => array(),
			'footer'  => ''
		);
		if($content['colspan'] === null) {
			if(is_array($content['header'])) {
				$content['colspan'] = count($content['header']);
			} else {
				$content['colspan'] = 1;
			}
		}
		if(!is_array($content['content'])) {
			$content['content'] = array( $content['content'] );
		}
		$content['count'] = count($content['content']);
	}
}
