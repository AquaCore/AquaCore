<?php
namespace Aqua\UI\Search;

use Aqua\Http\Request;
use Aqua\SQL\Search;
use Aqua\UI\AbstractForm;
use Aqua\UI\Form\Range as FormRange;

class Range
extends FormRange
implements SearchFieldInterface
{
	public $column;
	public $parser;

	public function setColumn($column)
	{
		$this->column = $column;
		return $this;
	}

	public function getColumn()
	{
		return $this->column;
	}

	public function setParser($parser)
	{
		$this->parser = $parser;

		return $this;
	}

	public function parse(AbstractForm $form)
	{
		$range = $form->getArray($this->name, array( 'min' => '', 'max' => '' ));
		if($range['max'] === '' && $range['min'] === '') {
			return false;
		}
		if($this->parser) {
			return call_user_func($this->parser, $this, $form, $range);
		} else {
			return $this->_parse($range);
		}
	}

	protected function _parse($range)
	{
		$range = array_map('trim', $range);
		switch($this->min->getAttr('type')) {
			case 'time':
				foreach($range as &$time) {
					if($time) {
						$time = date('H:i:s', $time);
					}
				}
				break;
			case 'datetime':
				foreach($range as &$time) {
					if($time) {
						$time = date('Y-m-d H:i:s', $time);
					}
				}
				break;
			case 'date':
				foreach($range as &$time) {
					if($time) {
						$time = date('Y-m-d', $time);
					}
				}
				break;
		}
		if($range['min'] === '') {
			return array( Search::SEARCH_LOWER, $range['max'] );
		} else if($range['max'] === '') {
			return array( Search::SEARCH_HIGHER, $range['min'] );
		} else if($range['max'] === $range['min']) {
			return $range['min'];
		} else {
			return array( Search::SEARCH_BETWEEN, $range['min'], $range['max'] );
		}
	}
}
