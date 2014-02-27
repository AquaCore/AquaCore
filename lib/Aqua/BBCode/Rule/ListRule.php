<?php
namespace Aqua\BBCode\Rule;

use Aqua\BBCode\AbstractRule;
use Aqua\BBCode\Node;

class ListRule
extends AbstractRule
{
	public $settings = array(
		'listTypePattern' => '/^([01]|01|none|a|i|bullet|circle|square)$/iS'
	);

	public function __construct(array $settings = array())
	{
		parent::__construct($settings);
		$this->addTag('list', array(
				'htmlTag' => 'ul',
				'children' => array( '*' ),
				'stripContent' => Node::NODE_TEXT,
				'attributes' => array(
					'$' => array(
						'map' => 'class',
						'optional' => true,
						'pattern' => &$this->settings['listTypePattern'],
						'format' => function($type) {
							switch($type) {
								case '0':
								case 'none': $type = 'none'; break;
								case '1': $type = 'decimal'; break;
								case '01': $type = 'leading-zero'; break;
								case 'a': $type = 'lower-alpha'; break;
								case 'A': $type = 'upper-alpha'; break;
								case 'i': $type = 'lower-roman'; break;
								case 'I': $type = 'upper-roman'; break;
							}
							return "bbc-list-$type";
						}
					)
				)
			))->addTag('*', array(
				'htmlTag' => 'li',
				'trimLineBreaks' => 1,
				'context' => array( 'list' => 1 )
			));
	}
}