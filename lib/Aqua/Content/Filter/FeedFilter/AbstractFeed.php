<?php
namespace Aqua\Content\Filter\FeedFilter;

abstract class AbstractFeed
extends \DOMDocument
{
	public function __construct(Feed $feed)
	{
		parent::__construct('1.0', 'UTF-8');
		$this->formatOutput = true;
		$this->build($feed);
	}

	public function createElementCDATA($name, $content)
	{
		if($content !== htmlspecialchars(strip_tags($content))) {
			$node = $this->createElement($name);
			$node->appendChild($this->createCDATASection($content));
		} else {
			$node = $this->createElement($name, $content);
		}
		return $node;
	}

	abstract protected function build(Feed $feed);
}
