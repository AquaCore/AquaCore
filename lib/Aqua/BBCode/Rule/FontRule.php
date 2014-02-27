<?php
namespace Aqua\BBCode\Rule;

use Aqua\BBCode\AbstractRule;

class FontRule
extends AbstractRule
{
	public $settings = array(
		'font_sizes' => array(
			1 => '25%',
			2 => '50%',
			3 => '75%',
			4 => '100%',
			5 => '125%',
			6 => '150%',
			7 => '175%',
			8 => '200%',
			9 => '225%',
			10 => '250%',
			11 => '275%',
			12 => '300%',
		),
		'font_families' => array(
			'georgia' => 'Georgia, serif',
			'palatino' => '\'Palatino Linotype\', \'Book Antiqua\', Palatino, serif',
			'times new roman' => '\'Times New Roman\', Times, serif',
			'arial' => 'Arial, Helvetica, sans-serif',
			'helvetica' => 'Helvetica, sans-serif',
			'arial black' => '\'Arial Black\', Gadget, sans-serif',
			'comic sans ms' => '\'Comic Sans MS\', cursive, sans-serif',
			'impact' => 'Impact, Charcoal, sans-serif',
			'lucida sans' => '\'Lucisa Sans Unicode\', \'Lucida Grande\', sans-serif',
			'tahoma' => 'Tahoma, Geneva, sans-serif',
			'trebuchet ms' => '\'Trebuchet MS\', Helvetica, sans-serif',
			'verdana' => 'Verdana, Geneva, sans-serif',
			'courier new' => '\'Courier New\', Courier, monospace',
			'lucida console' => '\'Lucida Console\', Monaco, monospace'
		)
	);

	public function __construct(array $settings = array())
	{
		parent::__construct($settings);
		$self = $this;
		$this->addTag('size', array(
				'htmlTag' => 'span',
				'attributes' => array(
					'$' => array(
						'map' => 'style',
						'encode' => false,
						'optional' => false,
						'pattern' => '/\d+/',
						'format' => function($size) use($self) {
							if(!isset($self->settings['font_sizes'][$size])) {
								return false;
							}
							return 'font-size: ' . $self->settings['font_sizes'][$size] . ';';
						}
					)
				)
			))->addTag('font', array(
				'htmlTag' => 'span',
				'attributes' => array(
					'$' => array(
						'map' => 'style',
						'encode' => false,
						'optional' => false,
						'pattern' => '/([a-z ]+)/i',
						'format' => function($font_family) use($self) {
							$font_family = strtolower($font_family);
							if(!isset($self->settings['font_families'][$font_family])) {
								return false;
							}
							return 'font-family: ' . $self->settings['font_families'][$font_family] . ';';
						}
					)
				)
		));
	}
}
