<?php
namespace Aqua\UI\Form;

use Aqua\Http\Request;
use Aqua\UI\Form;
use Aqua\UI\Tag;

class Textarea
extends Tag
implements FieldInterface
{
	/**
	 * @var string
	 */
	public $label;
	/**
	 * @var string
	 */
	public $description;
	/**
	 * @var string
	 */
	public $warning;
	/**
	 * @var int
	 */
	public $error = 0;
	/**
	 * @var string
	 */
	public $errorMessage = array();

	/**
	 * @param string|null $name
	 */
	public function __construct($name = null)
	{
		parent::__construct('textarea');
		if($name) {
			$this->attributes['name'] = $name;
		}
		$this->closeTag = true;
	}

	/**
	 * @param bool $val
	 * @return \Aqua\UI\Form\Textarea
	 */
	public function required($val = true)
	{
		$this->bool('required', $val);

		return $this;
	}

	/**
	 * @param string $label
	 * @return \Aqua\UI\Form\Textarea
	 */
	public function setLabel($label)
	{
		$this->label = $label;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getLabel()
	{
		return $this->label;
	}

	/**
	 * @param string $description
	 * @return \Aqua\UI\Form\Textarea
	 */
	public function setDescription($description)
	{
		$this->description = $description;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getDescription()
	{
		return $this->description;
	}

	/**
	 * @param string $warning
	 * @return \Aqua\UI\Form\Textarea
	 */
	public function setWarning($warning)
	{
		$this->warning = $warning;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getWarning()
	{
		return $this->warning;
	}

	/**
	 * @param int $error
	 * @return \Aqua\UI\Form\Textarea
	 */
	public function setError($error)
	{
		$this->error = $error;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getError()
	{
		return $this->error;
	}

	/**
	 * @param string $message
	 * @param int    $type
	 * @return \Aqua\UI\Form\Textarea
	 */
	public function setDefaultErrorMessage($message, $type = 0)
	{
		$this->errorMessage[$type] = $message;

		return $this;
	}

	/**
	 * @param \Aqua\Http\Request $request
	 * @param                    $message
	 * @return int
	 */
	public function validate(Request $request, &$message = null)
	{
		if(!isset($this->attributes['name']) || !$this->attributes['name'] || !$this->getBool('required')) {
			return Form::VALIDATION_SUCCESS;
		}
		if(($data = $request->getString($this->attributes['name'], false)) === false) {
			return Form::VALIDATION_INCOMPLETE;
		}

		return Form::VALIDATION_SUCCESS;
	}
}
