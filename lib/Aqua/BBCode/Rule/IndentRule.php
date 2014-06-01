<?php
namespace Aqua\BBCode\Rule;

use Aqua\BBCode\AbstractRule;
use Aqua\Core\L10n;

class IndentRule
extends AbstractRule
{
	public $settings = array(
		'direction' => 'ltr',
		'max' => 100,
		'offset' => 40,
		'unit' => 'px'
	);

	public function __construct(array $settings = array())
	{
		$this->settings['direction'] = strtolower(L10n::$direction);
		parent::__construct($settings);
		$self = $this;
		$this->addTag('indent', array(
				'htmlTag' => 'div',
				'trimLineBreaks' => 1,
				'attributes' => array(
					'$' => array(
						'map' => 'style',
						'optional' => false,
						'pattern' => '/\d+/',
						'format' => function($offset) use($self) {
							$offset = intval($offset);
							if($self->settings['max'] && $offset > $self->settings['max']) {
								return false;
							}
							if($self->settings['direction'] === 'rtl') {
								$style = 'margin-right: ';
							} else {
								$style = 'margin-left: ';
							}
							$style.= ($offset * $self->settings['offset']);
							$style.= $self->settings['unit'];
							$style.= ';';
							return $style;
						}
					)
				)
			));
	}
}
