<?php
namespace Aqua\UI\Form;

use Aqua\Core\App;
use Aqua\Http\Request;
use Aqua\UI\AbstractForm;
use Aqua\UI\Form;
use Aqua\UI\Tag;

class Range
implements FieldInterface
{
	/**
	 * @var string
	 */
	public $name;
	/**
	 * @var \Aqua\UI\Form\Input
	 */
	public $min;
	/**
	 * @var \Aqua\UI\Form\Input
	 */
	public $max;
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

	public function __construct($name, $type = 'number')
	{
		$this->name = $name;
		$this->min = new Input("{$name}[0]");
		$this->max = new Input("{$name}[1]");
		$this->min->type($type)->attr('class', 'ac-search-range-min ac-search-range-1');
		$this->max->type($type)->attr('class', 'ac-search-range-max ac-search-range-1');
	}

	public function attr($attr, $value)
	{
		$this->min->attr($attr, $value);
		$this->max->attr($attr, $value);

		return $this;
	}

	public function bool($attr, $value = true)
	{
		$this->min->bool($attr, $value);
		$this->max->bool($attr, $value);

		return $this;
	}

	public function type($type)
	{
		$this->min->type($type);
		$this->max->type($type);

		return $this;
	}

	public function required($bool = true)
	{
		$this->min->type($bool);
		$this->max->type($bool);

		return $this;
	}

	/**
	 * @param string $label
	 * @return \Aqua\UI\Form\Range
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
	 * @return \Aqua\UI\Form\Range
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
	 * @return \Aqua\UI\Form\Range
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
	 * @return \Aqua\UI\Form\Range
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
	 * @return \Aqua\UI\Form\Range
	 */
	public function setDefaultErrorMessage($message, $type = null)
	{
		return $this;
	}

	public function render()
	{
		$id         = 'ac-search-range-'  . App::uid();
		$between    = __('range', 'between');
		$exactly    = __('range', 'exactly');
		$higherThan = __('range', 'higher');
		$lowerThan  = __('range', 'lower');
		return "
<div id=\"$id\">
	<select class=\"ac-script ac-search-range-type\">
		<option value=\"1\">$between</option>
		<option value=\"2\">$exactly</option>
		<option value=\"3\">$higherThan</option>
		<option value=\"4\">$lowerThan</option>
	</select>
	{$this->min->render()}
	{$this->max->render()}
	<div style=\"clear: both\"></div>
</div>
<script type=\"text/javascript\">
(function() {
	\"use strict\";
	var rangeDiv     = document.getElementById(\"$id\"),
		rangeType    = rangeDiv.getElementsByClassName(\"ac-search-range-type\")[0],
		rangeMin     = rangeDiv.getElementsByClassName(\"ac-search-range-min\")[0],
		rangeMax     = rangeDiv.getElementsByClassName(\"ac-search-range-max\")[0],
		setRagneType = function(value) {
			switch(value) {
				case \"1\":
					rangeMin.style.display = \"\";
					rangeMax.style.display = \"\";
					break;
				case \"2\":
					rangeMin.style.display = \"\";
					rangeMax.style.display = \"none\";
					break;
				case \"3\":
					rangeMin.style.display = \"\";
					rangeMax.style.display = \"none\";
					break;
				case \"4\":
					rangeMin.style.display = \"none\";
					rangeMax.style.display = \"\";
					break;
			}
			rangeMin.className = rangeMin.className.replace(/(?!^|\s+)ac-search-range-\d(?=$|\s+)/, \"ac-search-range-\" + value);
			rangeMax.className = rangeMax.className.replace(/(?!^|\s+)ac-search-range-\d(?=$|\s+)/, \"ac-search-range-\" + value);
		};
	rangeMin.onchange = function() {
		if(rangeType.options[rangeType.selectedIndex].value === \"2\") {
			rangeMax.value = this.value;
		}
	};
	rangeType.onchange = function(e) {
		setRagneType(this.options[this.selectedIndex].value);
		rangeMin.value = \"\";
		rangeMax.value = \"\";
	};
	setRagneType(rangeType.options[rangeType.selectedIndex].value);
})();
</script>
";
	}

	public function validate(AbstractForm $form, &$errorMessage = null)
	{
		if(!($range = $form->getArray($this->name, null)) ||
		   !isset($range[0]) || !isset($range[1]) ||
		   !is_string($range[0]) || is_string($range[1])) {
			return Form::VALIDATION_INCOMPLETE;
		}
		$form->set("{$this->name}[0]", $range[0])->set("{$this->name}[1]", $range[1]);
		if(($status = $this->min->validate($form, $errorMessage)) !== Form::VALIDATION_SUCCESS ||
		   ($status = $this->max->validate($form, $errorMessage)) !== Form::VALIDATION_SUCCESS) {
			$this->setWarning($this->min->getWarning() ?: $this->max->getWarning());
		}
		$form->delete("{$this->name}[0]")->delete("{$this->name}[1]");
		return $status;
	}
}
