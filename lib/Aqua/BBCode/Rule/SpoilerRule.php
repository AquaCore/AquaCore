<?php
namespace Aqua\BBCode\Rule;

use Aqua\BBCode\AbstractRule;

class SpoilerRule
extends AbstractRule
{
	public function __construct(array $settings = array())
	{
		parent::__construct($settings);
		$this->addTag('hide', array(
				'htmlTag' => 'span',
				'trimLineBreaks' => true,
				'attributes' => array(
					'$' => array(
						'map' => 'style',
						'optional' => true,
						'pattern' => '/^(#[a-f0-9]{3,6}|[a-z]+)$/i',
						'format' => 'color: %1$s; background-color: %1$s;'
					),
					'class' => array(
						'map' => ' class',
						'value' => 'bbc-hide-text'
					)
				)
			))->addTag('spoiler', array(
				'template' => 'spoiler',
				'trimLineBreaks' => 1
			));
	}
}
