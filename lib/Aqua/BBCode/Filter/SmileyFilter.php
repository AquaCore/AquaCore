<?php
namespace Aqua\BBCode\Filter;

use Aqua\BBCode\AbstractFilter;
use Aqua\BBCode\BBCode;
use Aqua\BBCode\Node;

/**
 * BBCode filter that replaces smileys with their respective images
 *
 * @package Aqua\BBCode\Filter
 */
class SmileyFilter
extends AbstractFilter
{
	/**
	 * @var array
	 */
	public $settings = array(
		'base_url' => null,
		'smileys' => array()
	);
	/**
	 * @var string
	 */
	public $pattern = '';

	/**
	 * @param array $settings
	 */
	public function __construct(array $settings = array())
	{
		$this->settings['base_url'] = \Aqua\URL . '/uploads/smiley/';
		if(empty($settings['smileys'])) {
			$this->settings['smileys'] = include \Aqua\ROOT . '/settings/smiley.php';
		}
		parent::__construct($settings);
		$this->rebuildPattern();
	}

	public function rebuildPattern()
	{
		if(empty($this->settings['smileys'])) {
			$this->pattern = null;
		} else {
			$smileys = array_keys($this->settings['smileys']);
			foreach($smileys as &$key) {
				$key = preg_quote($key, '/');
			}
			$this->pattern = '/(' . implode('|', $smileys) . ')/i';
		}
	}

	public function afterParse(BBCode $bbcode, Node $node, &$content, &$parse)
	{
		if(!$parse || !$this->pattern) {
			return;
		}
		$content = preg_replace_callback($this->pattern, array($this, 'replaceSmiley'), $content);
	}

	public function replaceSmiley($match)
	{
		$key = $match[0];
		if(!isset($this->settings['smileys'][$key])) {
			return htmlspecialchars($key);
		}
		return '<img src="' . $this->settings['base_url'] . $this->settings['smileys'][$key] . '" alt="' . $key . '" title="' . $key . '">';
	}
}
