<?php
namespace Aqua\UI\Form;

use Aqua\Core\App;
use Aqua\Http\Request;
use Aqua\UI\AbstractForm;
use Aqua\UI\Form;
use Aqua\UI\Tag;

class Checkbox
implements FieldInterface
{
	/**
	 * @var string
	 */
	public $name;
	/**
	 * @var bool
	 */
	public $required = false;
	/**
	 * @var bool
	 */
	public $multiple = false;
	/**
	 * @var string
	 */
	public $class = '';
	/**
	 * @var \Aqua\UI\Tag[]
	 */
	public $values = array();
	/**
	 * @var \Aqua\UI\Tag[]
	 */
	public $labels = array();
	/**
	 * @var array
	 */
	public $checked = null;
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
	 * @var array
	 */
	public $errorMessage = array();

	const VALIDATION_INVALID_OPTION = 1;

	/**
	 * @param string|null $name
	 */
	public function __construct($name = null)
	{
		$this->name = $name;
	}

	/**
	 * @param string $class
	 * @return \Aqua\UI\Form\Checkbox
	 */
	public function setClass($class)
	{
		$this->class = $class;

		return $this;
	}

	/**
	 * @param bool $bool
	 * @return \Aqua\UI\Form\Checkbox
	 */
	public function required($bool = true)
	{
		$this->required = (bool)$bool;

		return $this;
	}

	/**
	 * @param bool $bool
	 * @return \Aqua\UI\Form\Checkbox
	 */
	public function multiple($bool = true)
	{
		$this->multiple = (bool)$bool;
		foreach($this->values as $option) {
			$option->attr('name', $this->name . ($this->multiple ? '[]' : ''));
		}

		return $this;
	}

	/**
	 * @param array $values
	 * @return \Aqua\UI\Form\Checkbox
	 */
	public function value(array $values)
	{
		foreach($values as $value => $title) {
			$id = 'ac-frm-field-' . App::uid();
			if($this->multiple) {
				$name = $this->name . '[]';
			} else {
				$name = $this->name;
			}
			$opt   = new Tag('input');
			$label = new Tag('label');
			$label->append($title)
				->attr('for', $id);
			$opt->closeTag = false;
			$opt->attr('type', 'checkbox')
				->attr('value', $value)
				->attr('name', $name)
				->attr('id', $id);
			$this->values[$value] = $opt;
			$this->labels[$value] = $label;
			if($this->checked && in_array($value, $this->checked, true)) {
				$opt->bool('checked', true);
			}
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
	 * @param string $key
	 * @return \Aqua\UI\Tag|null
	 */
	public function label($key)
	{
		if(!isset($this->labels[$key])) {
			return null;
		} else {
			return $this->labels[$key];
		}
	}

	/**
	 * @param string|array $value
	 * @param bool         $override
	 * @return \Aqua\UI\Form\Checkbox
	 */
	public function checked($value, $override = true)
	{
		if($override || $this->checked === null) {
			if(!is_array($value)) {
				$value = array( $value );
			}
			$this->checked = $value;
		}
		foreach($this->values as $value => $option) {
			$option->bool('checked', in_array($value, $this->checked));
		}

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
	 * @param string $label
	 * @return \Aqua\UI\Form\Checkbox
	 */
	public function setLabel($label)
	{
		$this->label = $label;

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
	 * @param string $description
	 * @return \Aqua\UI\Form\Checkbox
	 */
	public function setDescription($description)
	{
		$this->description = $description;

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
	 * @param string $warning
	 * @return \Aqua\UI\Form\Checkbox
	 */
	public function setWarning($warning)
	{
		$this->warning = $warning;

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
	 * @param int $error
	 * @return \Aqua\UI\Form\Checkbox
	 */
	public function setError($error)
	{
		$this->error = $error;

		return $this;
	}

	/**
	 * @param string $message
	 * @param int    $type
	 * @return \Aqua\UI\Form\Checkbox
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
		$html = '<div class="ac-checkbox-group ' . $this->class . '">';
		foreach($this->values as $key => $opt) {
			$html .= '<div class="ac-checkbox">';
			$html .= $opt->render();
			$html .= $this->labels[$key]->render();
			$html .= '</div>';
		}
		$html .= '</div>';

		return $html;
	}

	/**
	 * @param \Aqua\UI\AbstractForm $form
	 * @param                       $message
	 * @return int
	 */
	public function validate(AbstractForm $form, &$message = null)
	{
		if(($data = $form->getString($this->name, '')) === '' &&
		   ($data = $form->getArray($this->name, null)) === null) {
			return ($this->required ? Form::VALIDATION_INCOMPLETE : Form::VALIDATION_SUCCESS);
		}
		if(!is_array($data)) {
			$data = array( $data );
		}
		foreach($data as $value) {
			if(!isset($this->values[$value])) {
				$this->error = self::VALIDATION_INVALID_OPTION;
				$this->_setWarning(__('form', 'invalid-value-selected'));
				reset($this->values);

				return Form::VALIDATION_FAIL;
			}
		}

		return Form::VALIDATION_SUCCESS;
	}

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
		$labels = $this->labels;
		$this->values = array();
		$this->labels = array();
		foreach($labels as $key => $label) {
			$this->value(array(
				$key => $label->content[0]
			));
		}
	}
}
