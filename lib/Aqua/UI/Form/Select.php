<?php
namespace Aqua\UI\Form;

use Aqua\Http\Request;
use Aqua\UI\AbstractForm;
use Aqua\UI\Form;
use Aqua\UI\Tag;

class Select
extends Tag
implements FieldInterface
{
	/**
	 * @var \Aqua\UI\Tag[]
	 */
	public $values = array();
	/**
	 * @var array
	 */
	public $selected = array();
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
	protected $errorMessage = array();

	const VALIDATION_INVALID_OPTION = 1;
	const VALIDATION_FIELD_REQUIRED = 2;

	public function __construct($name = null)
	{
		parent::__construct('select');
		if($name) {
			$this->attributes['name'] = $name;
		}
		$this->closeTag      = true;
		$this->_errorMessage = __('application', 'invalid-value-selected');
	}

	/**
	 * @return \Aqua\UI\Form\Select
	 */
	public function multiple()
	{
		$this->attr('multiple', 'multiple');

		return $this;
	}

	/**
	 * @param array $values
	 * @return \Aqua\UI\Form\Select
	 */
	public function value(array $values)
	{
		foreach($values as $key => $title) {
			$opt = new Tag('option');
			$opt->append($title)
			->attr('value', $key);
			$this->values[$key] = $opt;
		}

		return $this;
	}

	/**
	 * @param string $key
	 * @return \Aqua\UI\Tag|null
	 */
	public function option($key)
	{
		if(!isset($this->values[$key])) {
			return null;
		} else {
			return $this->values[$key];
		}
	}

	/**
	 * @param string|array $key
	 * @param bool         $override
	 * @return \Aqua\UI\Form\Select
	 */
	public function selected($key, $override = true)
	{
		if($override || empty($this->selected)) {
			if(!is_array($key)) {
				$key = array( $key );
			}
			$this->selected = $key;
		}

		return $this;
	}

	/**
	 * @param bool $bool
	 * @return \Aqua\UI\Form\Select
	 */
	public function required($bool = true)
	{
		$this->bool('required', $bool);

		return $this;
	}

	/**
	 * @param string $label
	 * @return \Aqua\UI\Form\Select
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
	 * @return \Aqua\UI\Form\Select
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
	 * @return \Aqua\UI\Form\Select
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
	 * @return \Aqua\UI\Form\Select
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
	 * @return \Aqua\UI\Form\Select
	 */
	public function setDefaultErrorMessage($message, $type = self::VALIDATION_INVALID_OPTION)
	{
		$this->errorMessage[$type] = $message;

		return $this;
	}

	/**
	 * @return string
	 */
	public function render()
	{
		$html = $this->_renderOpenTag() . $this->_renderContent();
		foreach($this->values as $key => $option) {
			if(array_search($key, $this->selected) !== false) {
				$option->attr('selected', 'selected');
			}
			$html .= $option->render();
			$option->attr('selected', false);
		}
		$html .= $this->_renderCloseTag();

		return $html;
	}

	/**
	 * @param \Aqua\UI\AbstractForm $form
	 * @param                       $message
	 * @return int
	 */
	public function validate(AbstractForm $form, &$message = null)
	{
		if(!$this->getAttr('name', null)) {
			return Form::VALIDATION_SUCCESS;
		}
		if(($data = $form->getString($this->getAttr('name'), null)) === null &&
			($data = $form->getArray($this->getAttr('name'), null)) === null) {
			return Form::VALIDATION_INCOMPLETE;
		}
		if(is_array($data) && !$this->getBool('multiple')) {
			$this->error = self::VALIDATION_INVALID_OPTION;
			$this->_setWarning(__('form', 'invalid-value-selected'));

			return Form::VALIDATION_FAIL;
		}
		if(!is_array($data)) {
			$data = array( $data );
		}
		foreach($data as $value) {
			if(!isset($this->values[$value]) || $this->values[$value]->getBool('disabled')) {
				$this->error = self::VALIDATION_INVALID_OPTION;
				$this->_setWarning(__('form', 'invalid-value-selected'));
				reset($this->values);

				return Form::VALIDATION_FAIL;
			}
		}

		return Form::VALIDATION_SUCCESS;
	}

	/**
	 * @param string $warning
	 */
	public function _setWarning($warning)
	{
		if(empty($this->errorMessage[$this->error])) {
			$this->setWarning($warning);
		} else {
			$this->setWarning($this->errorMessage[$this->error]);
		}
	}

	public function __clone()
	{
		foreach($this->values as $key => $opt) {
			$this->values[$key] = clone $opt;
		}
	}
}