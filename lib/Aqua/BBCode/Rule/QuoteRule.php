<?php
namespace Aqua\BBCode\Rule;

use Aqua\BBCode\AbstractRule;
use Aqua\Core\App;

class QuoteRule
extends AbstractRule
{
	public function __construct(array $settings = array())
	{
		parent::__construct($settings);
		$this->addTag('quote', array(
				'trimLineBreaks' => 1,
				'template' => 'quote',
				'attributes' => array(
					'author' => array(
						'map' => 'author',
						'optional' => true,
						'encode' => true
					),
					'date' => array(
						'map' => 'date',
						'optional' => true,
						'encode' => false,
						'format' => function($date) {
							if(ctype_digit($date)) {
								return intval($date);
							} else if($timestamp = strtotime($date)) {
								return $timestamp;
							} else {
								return false;
							}
						}
					)
				)
			));
	}
}