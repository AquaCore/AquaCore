<?php
namespace Aqua\UI\Form;

use Aqua\Http\Request;

interface FieldInterface
{
	public function setLabel($label);
	public function getLabel();
	public function setDescription($desc);
	public function getDescription();
	public function render();
	public function setWarning($warning);
	public function getWarning();
	public function setError($error);
	public function getError();
	public function setDefaultErrorMessage($message, $type = 1);
	public function validate(Request $request, &$errorMessage = null);
}