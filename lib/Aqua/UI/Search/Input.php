<?php
namespace Aqua\UI\Search;

use Aqua\SQL\Search;
use Aqua\UI\AbstractForm;
use Aqua\UI\Form\Input as FormInput;

class Input
extends FormInput
implements SearchFieldInterface
{
	public $column;
	public $parser;
	public $parserData = array();
	public $type = self::SEARCH_LIKE_BOTH;

	const SEARCH_LIKE_LEFT  = 0;
	const SEARCH_LIKE_RIGHT = 1;
	const SEARCH_LIKE_BOTH  = 2;
	const SEARCH_EXACT      = 3;

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
		$type = $this->getAttr('type');
		if(empty($type) || $type === 'file' || $type === 'submit' || $type === 'image' || $type === 'button' ||
		   !$this->getAttr('name') || !($value = $form->getString($this->getAttr('name')))) {
			return false;
		}
		if($this->parser) {
			$data = array_merge(array( $this, $form, $value ), $this->parserData);
			return call_user_func_array($this->parser, $data);
		} else {
			return $this->_parse($value);
		}
	}

	protected function _parse($value)
	{
		switch($this->getAttr('type')) {
			case 'time': $value = date('H:i:s', strtotime($value)); break;
			case 'datetime': $value = date('Y-m-d H:i:s', strtotime($value)); break;
		}
		if($this->type === self::SEARCH_EXACT) {
			return $value;
		} else {
			$value = addcslashes($value, '%_\\');
			switch($this->type) {
				case self::SEARCH_LIKE_RIGHT: $value .= '%'; break;
				case self::SEARCH_LIKE_LEFT: $value = '%' . $value; break;
				case self::SEARCH_LIKE_BOTH: $value = '%' . $value . '%';
			}
			return array( Search::SEARCH_LIKE, $value );
		}
	}
}
