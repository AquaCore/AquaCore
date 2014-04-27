<?php
namespace Aqua\UI\Search;

use Aqua\UI\AbstractForm;
use Aqua\UI\Form\FieldInterface;

interface SearchFieldInterface
extends  FieldInterface
{
	public function setColumn($column);
	public function getColumn();
	public function setParser($parser, array $data = array());
	public function parse(AbstractForm $form);
}
