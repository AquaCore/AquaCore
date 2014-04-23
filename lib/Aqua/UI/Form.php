<?php
namespace Aqua\UI;

use Aqua\UI\Form\Captcha;
use Aqua\UI\Form\Checkbox;
use Aqua\UI\Form\FieldInterface;
use Aqua\UI\Form\File;
use Aqua\UI\Form\Input;
use Aqua\UI\Form\Radio;
use Aqua\UI\Form\ReCaptcha;
use Aqua\UI\Form\Select;
use Aqua\UI\Form\Textarea;
use Aqua\UI\Form\Token;
use Aqua\UI\Tag;

class Form
extends AbstractForm
{
	/**
	 * @var string $name
	 * @var bool   $autofill
	 * @return \Aqua\UI\Form\Input
	 */
	public function input($name, $autofill = false)
	{
		if(isset($this->content[$name]) && $this->content[$name] instanceof Input) {
			return $this->content[$name];
		}
		$input                = new Input($name);
		$this->content[$name] = $input;
		if($autofill && ($val = $this->getString($name, false))) {
			$input->value($val);
		}

		return $input;
	}

	/**
	 * @var string $name
	 * @var bool   $autofill
	 * @return \Aqua\UI\Form\Checkbox
	 */
	public function checkbox($name, $autofill = false)
	{
		if(isset($this->content[$name]) && $this->content[$name] instanceof Checkbox) {
			return $this->content[$name];
		}
		$checkbox             = new Checkbox($name);
		$this->content[$name] = $checkbox;
		try {
			if($autofill && ($val = $this->get($name))) {
				$checkbox->checked($val);
			}
		} catch(\Exception $e) {
		}

		return $checkbox;
	}

	/**
	 * @var string $name
	 * @var bool   $autofill
	 * @return \Aqua\UI\Form\Radio
	 */
	public function radio($name, $autofill = false)
	{
		if(isset($this->content[$name]) && $this->content[$name] instanceof Radio) {
			return $this->content[$name];
		}
		$radio                = new Radio($name);
		$this->content[$name] = $radio;
		try {
			if($autofill && ($val = $this->get($name))) {
				$radio->checked($val);
			}
		} catch(\Exception $e) {
		}

		return $radio;
	}

	/**
	 * @param string $name
	 * @return \Aqua\UI\Form\File
	 */
	public function file($name)
	{
		if(isset($this->content[$name]) && $this->content[$name] instanceof Form) {
			return $this->content[$name];
		}
		$file                 = new File($name);
		$this->content[$name] = $file;

		return $file;
	}

	/**
	 * @param string $name
	 * @param bool   $autofill
	 * @return \Aqua\UI\Form\Textarea
	 */
	public function textarea($name, $autofill = false)
	{
		if(isset($this->content[$name]) && $this->content[$name] instanceof Textarea) {
			return $this->content[$name];
		}
		$textarea             = new Textarea($name);
		$this->content[$name] = $textarea;
		if($autofill) {
			$textarea->append($this->getString($name));
		}

		return $textarea;
	}

	/**
	 * @var string $name
	 * @var bool   $autofill
	 * @return \Aqua\UI\Form\Select
	 */
	public function select($name, $autofill = false)
	{
		if(isset($this->content[$name]) && $this->content[$name] instanceof Select) {
			return $this->content[$name];
		}
		$select               = new Select($name);
		$this->content[$name] = $select;
		try {
			if($autofill && ($val = $this->get($name))) {
				$select->selected($val);
			}
		} catch(\Exception $e) {
		}

		return $select;
	}

	/**
	 * @var string $name
	 * @return \Aqua\UI\Form\reCaptcha
	 */
	public function reCaptcha($name = 'recaptcha')
	{
		if(isset($this->content[$name]) && $this->content[$name] instanceof ReCaptcha) {
			return $this->content[$name];
		}
		$reCaptcha            = new ReCaptcha;
		$this->content[$name] = $reCaptcha;

		return $reCaptcha;
	}

	/**
	 * @var string $name
	 * @return \Aqua\UI\Form\Captcha
	 */
	public function captcha($name = 'ac_captcha')
	{
		if(isset($this->content[$name]) && $this->content[$name] instanceof Captcha) {
			return $this->content[$name];
		}
		$captcha              = new Captcha($name);
		$this->content[$name] = $captcha;

		return $captcha;
	}

	/**
	 * @param string $name
	 * @param int    $length
	 * @return \Aqua\UI\Form\Token
	 */
	public function token($name, $length = 32)
	{
		if(isset($this->content[$name]) && $this->content[$name] instanceof Token) {
			return $this->content[$name];
		}
		$token                = new Token($name, $length);
		$this->content[$name] = $token;

		return $token;
	}

	/**
	 * @param string $value
	 * @return \Aqua\UI\Form\Input
	 */
	public function submit($value = null)
	{
		if($value === null) {
			$value = __('application', 'Submit');
		}

		return $this->input('submit')->type('submit')->value($value);
	}
	/**
	 * @param mixed $tag
	 * @return string
	 */
	public function renderTag($tag)
	{
		if($tag instanceof FieldInterface) {
			if($warning = $tag->getWarning()) {
				$html = "<tr class=\"ac-field-warning\">";
				$html .= "<td colspan=\"3\">$warning</td>";
				$html .= "</tr>";
				$html .= "<tr class=\"ac-form-field\">";
				$html .= "<td class=\"ac-field-status ac-field-error\"></td>";
			} else {
				$html = "<tr class=\"ac-form-field\">";
				$html .= "<td class=\"ac-field-status\"></td>";
			}
			if($label = $tag->getLabel()) {
				if($tag instanceof Checkbox &&
				   count($tag->values) === 1 &&
				   ($labelTag = current($tag->labels)) &&
				   $labelTag->content[0] === '') {
					$label = "<label for=\"{$labelTag->getAttr('for')}\">$label</label>";
				}
				$html .= "<td class=\"ac-form-label\">$label</td>" .
				         "<td class=\"ac-form-tag\">{$tag->render()}</td>";
			} else {
				$html .= "<td class=\"ac-form-tag\" colspan=\"2\">{$tag->render()}</td>";
			}
			$html .= "</tr>";
			if($desc = $tag->getDescription()) {
				$html .= "<tr class=\"ac-form-description\"><td colspan=\"3\">$desc</td></tr>";
			}
		} else {
			if($tag instanceof Tag) {
				$html = "<tr><td colspan=\"3\">{$tag->render()}</td></tr>";
			} else {
				$html = "<tr><td colspan=\"3\">$tag</td></tr>";
			}
		}

		return $html;
	}
}
