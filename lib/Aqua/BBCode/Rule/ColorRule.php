<?php
namespace Aqua\BBCode\Rule;

use Aqua\BBCode\AbstractRule;

class ColorRule
extends AbstractRule
{
	public $settings = array(
		'colorPattern' => '/^(#[a-f0-9]{3,6}|[a-z]+)$/iS'
	);

	public function __construct(array $settings = array())
	{
		parent::__construct($settings);
		$this->addTag('color', array(
				'htmlTag' => 'span',
				'attributes' => array(
					'$' => array(
						'map'      => 'style',
						'optional' => false,
						'encode'   => false,
						'pattern'  => &$this->settings['colorPattern'],
						'format'   => 'color: %1$s;',
					)
				)
			))->addTag('background', array(
				'htmlTag' => 'span',
				'attributes' => array(
					'$' => array(
						'map'      => 'style',
						'optional' => false,
						'encode'   => false,
						'pattern'  => &$this->settings['colorPattern'],
						'format'   => 'background-color: %1$s;',
					)
				)
			));
	}
}
