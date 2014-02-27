<?php
namespace Aqua\BBCode\Rule;

use Aqua\BBCode\AbstractRule;
use Aqua\BBCode\Node;

class JustifyRule
extends AbstractRule
{
	public function __construct(array $settings = array())
	{
		parent::__construct($settings);
		$this->addTag('center', array(
				'htmlTag' => 'div',
				'trimLineBreaks' => 1
			))->addTag('right', array(
				'htmlTag' => 'div',
				'trimLineBreaks' => 1
			))->addTag('left', array(
				'htmlTag' => 'div',
				'trimLineBreaks' => 1
			))->addTag('justify', array(
				'htmlTag' => 'div',
				'trimLineBreaks' => 1
			));
		$this->attach('parse', function($event, Node $node, $tag, &$html) {
			$html['attributes']['style'] = "text-align: {$node->name};";
		});
	}
}