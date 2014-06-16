<?php
namespace Aqua\UI\Search;

use Aqua\Core\Exception\InvalidArgumentException;
use Aqua\SQL\Search;
use Aqua\UI\AbstractForm;
use Aqua\UI\Form\Select as FormSelect;

class Select
extends FormSelect
implements SearchFieldInterface
{
	public $column;
	public $parser;
	public $parserData = array();
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

	public function setParser($parser, array $data = array())
	{
		if(!is_callable($parser)) {
			throw new InvalidArgumentException(0, 'callable', $parser);
		}
		$this->parser     = $parser;
		$this->parserData = $data;

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
			if(empty($value)) {
				return false;
			}
		} else {
			$value = $form->getString($this->getAttr('name'));
			if($value === '') {
				return false;
			}
		}
		if($this->parser) {
			$data = array_merge(array( $this, $form, $value ), $this->parserData);
			return call_user_func_array($this->parser, $data);
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
						$x |= (int)$y;
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
 