<?php
namespace Aqua\BBCode\Rule;

use Aqua\BBCode\AbstractRule;

class HeadingRule
extends AbstractRule
{
	public function __construct(array $settings = array())
	{
		parent::__construct($settings);
		for($i = 1; $i < 7; ++$i) {
			$this->addTag("h$i", array( 'htmlTag' => "h$i" ));
		}
	}
}