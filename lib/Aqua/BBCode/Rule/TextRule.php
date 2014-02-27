<?php
namespace Aqua\BBCode\Rule;

use Aqua\BBCode\AbstractRule;

class TextRule
extends AbstractRule
{
	public function __construct(array $settings = array())
	{
		parent::__construct($settings);
		$this->addTag('b', array(
				'htmlTag' => 'strong'
			))->addTag('i', array(
				'htmlTag' => 'em',
			))->addTag('u', array(
				'htmlTag' => 'u',
			))->addTag('s', array(
				'htmlTag' => 'strike',
			))->addTag('sub', array(
				'htmlTag' => 'sub',
			))->addTag('sup', array(
				'htmlTag' => 'sup',
			));
	}
}