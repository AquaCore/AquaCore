<?php
namespace Aqua\UI\Form;

use Aqua\Core\App;
use Aqua\UI\Tag;

class Radio
extends Checkbox
{
	/**
	 * @param string|null $name
	 */
	public function __construct($name = null)
	{
		parent::__construct($name);
		$this->attributes['type'] = 'radio';
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
			$opt->attr('type', 'radio')
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
	 * @return string
	 */
	public function render()
	{
		$html = '<div class="ac-radio-group ' . $this->class . '">';
		foreach($this->values as $key => $opt) {
			$html .= '<div class="ac-radio">';
			$html .= $opt->render();
			$html .= $this->labels[$key]->render();
			$html .= '</div>';
		}
		$html .= '</div>';

		return $html;
	}
}
