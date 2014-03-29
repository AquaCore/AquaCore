<?php
namespace Aqua\UI\Form;

use Aqua\Http\Request;
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
	 * @param \Aqua\Http\Request $request
	 * @param                    $errorMessage
	 * @return int
	 */
	public function validate(Request $request, &$errorMessage = null)
	{
		$type = strtolower($this->attributes['type']);
		if(empty($this->attributes['name']) || $type === 'submit' || $type === 'image' || $type === 'button' || $type === 'reset') {
			return Form::VALIDATION_SUCCESS;
		}
		$error = null;
		$value = $request->getString($this->getAttr('name'), null);
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
						break;
					}
					$hex = $match[1];
					switch(strlen($hex)) {
						case 3:
							$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
							break;
						case 4:
							$hex .= $hex[2] . $hex[3];
							break;
						case 5:
							$hex .= '0';
							break;
					}
					$request->data[$this->attributes['name']] = "#$hex";
					break;
				case 'date':
					do {
						try {
							$date  = \DateTime::createFromFormat('Y-m-d', $value);
							$error = \DateTime::getLastErrors();
							if($date && empty($error['warning_count'])) {
								$timestamp = strtotime('midnight', $date->getTimestamp());
								break;
							}
						} catch(\Exception $e) { }
						$this->error = self::VALIDATION_INVALID_TYPE;
						$error       = __('form', 'invalid-date');
						break 2;
					} while(0);
					if(($this->getAttr('max') && $timestamp > strtotime($this->getAttr('max'))) ||
					   ($this->getAttr('min') && $timestamp < strtotime($this->getAttr('min')))) {
						$this->error = self::VALIDATION_INVALID_RANGE;
						$error       = __('form', 'invalid-date-range');
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
}
