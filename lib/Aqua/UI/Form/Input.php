<?php
namespace Aqua\UI\Form;

use Aqua\Http\Request;
use Aqua\UI\AbstractForm;
use Aqua\UI\Form;
use Aqua\UI\Tag;

class Input
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
	 * @var array
	 */
	public $errorMessage = array();
	/**
	 * @var int
	 */
	public $error = 0;

	const VALIDATION_INVALID_TYPE   = 1;
	const VALIDATION_INVALID_RANGE  = 2;
	const VALIDATION_INVALID_LENGTH = 3;
	const VALIDATION_EMPTY_VALUE    = 4;
	const VALIDATION_PATTERN        = 5;

	const TIME_PATTERN = '(((2[0-4])|([01][0-9]))([\.:][0-5][0-9]){1,2}|((0[0-9])|(1[012]))([\.:][0-5][0-9]){1,2} ?(PM|AM))$';

	/**
	 * @param string|null $name
	 */
	public function __construct($name = null)
	{
		parent::__construct('input');
		if($name) {
			$this->attributes['name'] = $name;
		}
		$this->attributes['type'] = 'text';
		$this->closeTag           = false;
	}

	/**
	 * @param string $type
	 * @return \Aqua\UI\Form\Input
	 */
	public function type($type)
	{
		$this->attributes['type'] = $type;
		$this->_checkExtendedTypes();
		return $this;
	}

	/**
	 * @param string $placeholder
	 * @return \Aqua\UI\Form\Input
	 */
	public function placeholder($placeholder)
	{
		$this->attributes['placeholder'] = $placeholder;

		return $this;
	}

	/**
	 * @param string $value
	 * @param bool   $override
	 * @return \Aqua\UI\Form\Input
	 */
	public function value($value, $override = true)
	{
		if($override || !isset($this->attributes['value'])) {
			$this->attributes['value'] = $value;
		}

		return $this;
	}

	/**
	 * @return \Aqua\UI\Form\Input
	 */
	public function required()
	{
		$this->bool('required', true);
		$this->_checkExtendedTypes();

		return $this;
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
	public function setDefaultErrorMessage($message, $type = self::VALIDATION_INVALID_TYPE)
	{
		$this->errorMessage[$type] = $message;

		return $this;
	}

	/**
	 * @param \Aqua\UI\AbstractForm $form
	 * @param                       $errorMessage
	 * @return int
	 */
	public function validate(AbstractForm $form, &$errorMessage = null)
	{
		$type = strtolower($this->attributes['type']);
		if(empty($this->attributes['name']) || $type === 'submit' || $type === 'image' || $type === 'button' || $type === 'reset') {
			return Form::VALIDATION_SUCCESS;
		}
		$error = null;
		$value = $form->getString($this->getAttr('name'), null);
		if($value === null) {
			return Form::VALIDATION_INCOMPLETE;
		} else if($value === '') {
			if($this->getBool('required')) {
				$this->error = self::VALIDATION_EMPTY_VALUE;
				$error       = __('form', 'field-required');
			} else {
				return Form::VALIDATION_SUCCESS;
			}
		} else if($this->getAttr('pattern') !== null && !@preg_match($this->getAttr('pattern'), $value)) {
			$this->error = self::VALIDATION_PATTERN;
			$error       = __('form', 'invalid-pattern');
		} else if($this->getAttr('maxlength') !== null && strlen($value) > (int)$this->getAttr('maxlength')) {
			$this->error = self::VALIDATION_INVALID_LENGTH;
			$error       = __('form', 'value-too-long');
		} else {
			switch($type) {
				case 'color':
					if(!preg_match('/#?([a-f0-9]{3,6})/i', $value, $match)) {
						$this->error = self::VALIDATION_INVALID_TYPE;
						$error       = __('form', 'invalid-color');
					}
					break;
				case 'time':
					$this->_validateTime($value, $this->error, $error);
					break;
				case 'date':
					$this->_validateDate($value, $this->error, $error);
					break;
				case 'datetime':
					$date = explode(' ', $value);
					if(!count($date) === 2) {
						$this->error = self::VALIDATION_INVALID_TYPE;
						$error       = __('form', 'invalid-date');
					} else {
						$this->_validateDate($date[0], $this->error, $error) or
						$this->_validateTime($date[1], $this->error, $error);
					}
					break;
				case 'email':
					if(!filter_var($value, FILTER_VALIDATE_EMAIL)) {
						$this->error = self::VALIDATION_INVALID_TYPE;
						$error       = __('form', 'invalid-email');
					}
					break;
				case 'number':
				case 'range':
					if(!ctype_digit($value)) {
						$this->error = self::VALIDATION_INVALID_TYPE;
						$error       = __('form', 'invalid-number');
						break;
					}
					$value = (int)$value;
					if(($min = $this->getAttr('min', null)) !== null && $value < $min ||
					   ($max = $this->getAttr('max', null)) !== null && $value > $max ||
					   (($value - $this->getAttr('min', 0)) % $this->getAttr('step', 1)) > 0) {
						$this->error = self::VALIDATION_INVALID_RANGE;
						$error       = __('form', 'invalid-range');
					}
					break;
				case 'url':
					if(!filter_var($value, FILTER_VALIDATE_URL)) {
						$this->error = self::VALIDATION_INVALID_TYPE;
						$error       = __('form', 'invalid-url');
					}
					break;
			}
		}
		if($this->error) {
			$this->_setWarning($error);

			return Form::VALIDATION_FAIL;
		} else {
			return Form::VALIDATION_SUCCESS;
		}
	}

	public function render()
	{
		if($pattern = $this->getAttr('pattern')) {
			$delimiter = preg_quote($pattern[0], '%');
			$modifiers = 'imsxeuADSUXJ';
			$this->attr('pattern', preg_replace("%(^{$delimiter})|({$delimiter}[{$modifiers}]*$)%", '', $pattern));
		}
		$html = parent::render();
		$this->attr('pattern', $pattern);
		return $html;
	}

	public function _setWarning($warning)
	{
		if(empty($this->errorMessage[$this->error])) {
			$this->setWarning($warning);
		} else {
			$this->setWarning($this->errorMessage[$this->error]);
		}
	}

	protected function _checkExtendedTypes()
	{
		switch($this->getAttr('type')) {
			case 'time':
			case 'datetime':
				$pattern = self::TIME_PATTERN;
				if(!$this->getBool('required')) {
					$pattern.= '|^$';
				}
				if($this->getAttr('type') === 'datetime') {
					$pattern = '(?:\d{4,}-(?:1[0-2]|0[1-9])-(?:3[01]|[12][0-9]|0[1-9])) ' . $pattern;
				}
				$this->attr('pattern', "/^$pattern/i");
				break;
		}
	}

	protected function _validateDate($value, &$errorId, &$errorMessage)
	{
		do {
			try {
				$date  = \DateTime::createFromFormat('Y-m-d', $value);
				$error = \DateTime::getLastErrors();
				if($date && empty($error['warning_count'])) {
					$timestamp = strtotime('midnight', $date->getTimestamp());
					break;
				}
			} catch(\Exception $e) { }
			$errorId      = self::VALIDATION_INVALID_TYPE;
			$errorMessage = __('form', 'invalid-date');
			return false;
		} while(0);
		if(($this->getAttr('max') && $timestamp > strtotime($this->getAttr('max'))) ||
		   ($this->getAttr('min') && $timestamp < strtotime($this->getAttr('min')))) {
			$errorId      = self::VALIDATION_INVALID_RANGE;
			$errorMessage = __('form', 'invalid-date-range');
			return true;
		}
		return true;
	}

	protected function _validateTime($value, &$errorId, &$errorMessage)
	{
		$timestamp = strtotime("2000-01-01 $value");
		if(!$timestamp) {
			$errorId      = self::VALIDATION_INVALID_TYPE;
			$errorMessage = __('form', 'invalid-date');
			return false;
		}
		if((($min = $this->getAttr('min')) && $timestamp < strtotime("2000-01-01 $min")) ||
		   (($max = $this->getAttr('max')) && $timestamp > strtotime("2000-01-01 $max"))) {
			$errorId      = self::VALIDATION_INVALID_RANGE;
			$errorMessage = __('form', 'invalid-date-range');
			return false;
		}
		return true;
	}
}
