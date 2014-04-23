<?php
namespace Aqua\UI\Search;

use Aqua\Http\Request;
use Aqua\SQL\Search;
use Aqua\UI\AbstractForm;
use Aqua\UI\Form\Select as FormSelect;

class Select
extends FormSelect
implements SearchFieldInterface
{
	public $column;
	public $parser;
	public $type = null;

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

	public function searchType($type)
	{
		$this->type = $type;
		return $this;
	}

	public function parse(AbstractForm $form)
	{
		if(!$this->getAttr('name')) {
			return false;
		}
		if($this->getAttr('multiple') || $this->getBool('multiple')) {
			$value = $form->getArray($this->getAttr('name'));
		} else {
			$value = $form->getString($this->getAttr('name'));
		}
		if(is_string($value) && $value === '' || empty($value)) {
			return false;
		}
		if($this->parser) {
			return call_user_func($this->parser, $this, $form, $value);
		} else {
			return $this->_parse($value);
		}
	}

	public function _parse($value)
	{
		if(is_array($value)) {
			if($this->type === null) {
				$typeFull = $type = Search::SEARCH_IN;
			} else {
				$typeFull = $this->type;
				$type     = $this->type ^ ($this->type & Search::SEARCH_DIFFERENT);
			}
			switch($type) {
				case Search::SEARCH_AND:
				case Search::SEARCH_OR:
				case Search::SEARCH_NOT:
				case Search::SEARCH_XOR:
					$x = 0;
					foreach($value as &$y) {
						if(is_int($y)) $x |= (int)$y;
					}
					return array( $typeFull, $x );
				default:
					array_unshift($value, $typeFull);
					return $value;
			}
		} else {
			return array( $this->type === null ? Search::SEARCH_NATURAL : $this->type, $value );
		}
	}
}
 