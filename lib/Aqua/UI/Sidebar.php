<?php
namespace Aqua\UI;

class Sidebar
extends AbstractContent
{
	/**
	 * @var \Aqua\UI\Tag
	 */
	public $wrapper;

	/**
	 * @param string $template
	 * @return string
	 */
	public function render($template = 'default')
	{
		$tpl = new Template;
		$tpl->set('class', implode(' ', $this->classes));
		$tpl->set('content', $this->content);
		$content = $tpl->render("sidebar/$template");
		if($this->wrapper) {
			$this->wrapper->append($content);
			$content = $this->wrapper->render();
			$this->wrapper->clearContent();
		}

		return $content;
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function hasTabs($key)
	{
		return (isset($this->content[$key]) && count($this->content[$key]) > 2);
	}

	/**
	 * @param \Aqua\UI\Tag $tag
	 * @return \Aqua\UI\Sidebar
	 */
	public function wrapper(Tag $tag)
	{
		$this->wrapper = $tag;

		return $this;
	}

	protected function _parseContent(array &$content)
	{
		$_content = array( 'class' => '' );
		if(isset($content['class'])) {
			$_content['class'] = is_array($content['class']) ? implode($content['class']) : $content['class'];
			unset($content['class']);
		}
		foreach($content as &$tab) {
			if(is_array($tab)) {
				$this->_parseSidebar($tab);
				$_content['content'][] = $tab;
			}
		}
		$content = $_content;
	}

	protected function _parseSidebar(array &$sidebar)
	{
		$sidebar += array(
			'title'   => '',
			'content' => ''
		);
	}
}
