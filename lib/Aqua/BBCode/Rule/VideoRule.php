<?php
namespace Aqua\BBCode\Rule;

use Aqua\BBCode\AbstractRule;
use Aqua\BBCode\Node;

class VideoRule
extends AbstractRule
{
	public function __construct(array $settings = array())
	{
		parent::__construct($settings);
		$this->addTag('youtube', array(
				'template' => 'youtube',
				'attributes' => array(
					'$' => array(
						'map' => 'start',
						'optional' => true,
						'pattern' => '/(^\d+$)|(^(?:\d+:)?(?:\d\d?[:.])?\d\d?$)/',
						'format' => function($value) {
							if(ctype_digit($value)) {
								return intval($value);
							}
							$time = array_map('intval', array_reverse(preg_split('/[:.]/', $value)));
							if(empty($time) || count($time) > 3 || $time[0] > 59) {
								return false;
							}
							$int = intval($time[0]);
							if(isset($time[1])) {
								if($time[1] > 59) { return false; }
								$int+= $time[1] * 60;
							}
							if(isset($time[2])) {
								$int+= intval($time[2]) * 3600;
							}
							return $int;
						}
					),
					'src' => array(
						'map' => 'video_id',
						'optional' => false,
						'pattern' => '%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i',
						'format' => '%1$s'
					)
				)
			))->addTag('vimeo', array(
				'template' => 'vimeo',
				'attributes' => array(
					'src' => array(
						'map' => 'video_id',
						'optional' => false,
						'pattern' => '%^(?:https?://)?(?:www\.)?(?:player\.)?vimeo\.com/(?:[a-z]*/)*([0-9]{6,})[?]?.%i',
						'format' => '%1$s'
					)
				)
			));
	}

	public function parse(Node $node)
	{
		$src = trim($node->content());
		if(!$src) {
			return false;
		}
		$node->attributes['src'] = $src;
		$node->clear();
		return parent::parse($node);
	}
}
