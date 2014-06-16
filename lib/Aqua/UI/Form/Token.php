<?php
namespace Aqua\UI\Form;

use Aqua\Core\App;
use Aqua\UI\AbstractForm;
use Aqua\UI\Form;
use Aqua\UI\Tag;

class Token
extends Tag
implements FieldInterface
{
	/**
	 * @var int
	 */
	public $length;
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
	 * @var array
	 */
	public $errorMessage = array();
	/**
	 * @var int
	 */
	public $error = 0;

	const VALIDATION_INVALID_TOKEN = 1;

	/**
	 * @param string|null $name
	 * @param int         $length
	 */
	public function __construct($name = null, $length = 32)
	{
		parent::__construct('input');
		if($name) {
			$this->attributes['name'] = $name;
		}
		$this->attributes['type'] = 'hidden';
		$this->closeTag           = false;
		$this->length             = $length;
	}

	/**
	 * @param string $label
	 * @return \Aqua\UI\Form\Input
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
	 * @param string $desc
	 * @return \Aqua\UI\Form\Input
	 */
	public function setDescription($desc)
	{
		$this->description = $desc;

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
	 * @return \Aqua\UI\Form\Input
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
	 * @return \Aqua\UI\Form\Input
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
	 * @return \Aqua\UI\Form\Input
	 */
	public function setDefaultErrorMessage($message, $type = self::VALIDATION_INVALID_TOKEN)
	{
		$this->errorMessage[$type] = $message;

		return $this;
	}

	/**
	 * @return string
	 */
	public function render()
	{
		$this->attributes['value'] = App::user()->setToken($this->getAttr('name'), $this->length);

		return parent::render();

	}

	/**
	 * @param \Aqua\UI\AbstractForm $form
	 * @param                       $errorMessage
	 * @return int
	 */
	public function validate(AbstractForm $form, &$errorMessage = null)
	{
		if(empty($this->attributes['name'])) {
			return Form::VALIDATION_SUCCESS;
		}
		if(!($input = $form->getString($this->getAttr('name')))) {
			return Form::VALIDATION_INCOMPLETE;
		}
		$token = App::user()->getToken($this->getAttr('name'));
		if(empty($token)) {
			return Form::VALIDATION_INCOMPLETE;
		} else {
			if($token !== $input) {
				$this->error  = self::VALIDATION_INVALID_TOKEN;
				$errorMessage = (!empty($this->errorMessage[$this->error]) ? $this->errorMessage[$this->error] : __('form', 'invalid-token'));

				return Form::VALIDATION_FAIL;
			} else {
				return Form::VALIDATION_SUCCESS;
			}
		}
	}
}
