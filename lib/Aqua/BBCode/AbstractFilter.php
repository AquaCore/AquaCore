<?php
namespace Aqua\BBCode;

abstract class AbstractFilter
{
	/**
	 * @var array
	 */
	public $settings = array();

	/**
	 * @param array $settings
	 */
	public function __construct(array $settings = array())
	{
		$this->settings = $settings + $this->settings;
	}

	public function beforeParse(BBCode $bbc, Node $node, &$content, &$parse) {}
	public function afterParse(BBCode $bbc, Node $node, &$content, &$parse) {}
}
