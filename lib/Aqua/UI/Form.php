<?php
namespace Aqua\UI;

use Aqua\Http\Request;
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
{
	/**
	 * @var array
	 */
	public $content = array();
	/**
	 * @var string
	 */
	public $method = 'POST';
	/**
	 * @var string
	 */
	public $action = null;
	/**
	 * @var bool
	 */
	public $autocomplete = false;
	/**
	 * @var bool
	 */
	public $novalidate = false;
	/**
	 * @var string
	 */
	public $enctype = '';
	/**
	 * @var string
	 */
	public $name = null;
	/**
	 * @var string
	 */
	public $target = '_self';
	/**
	 * @var \Aqua\Http\Request
	 */
	public $request;
	/**
	 * @var string
	 */
	public $message = '';
	/**
	 * @var int
	 */
	public $status = 2;

	const VALIDATION_FAIL       = 0;
	const VALIDATION_SUCCESS    = 1;
	const VALIDATION_INCOMPLETE = 2;

	/**
	 * @param \Aqua\Http\Request $request
	 */
	public function __construct(Request $request)
	{
		$this->request = $request;
	}

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
		if($autofill && ($val = $this->request->getString($name, false))) {
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
			if($autofill && isset($this->request->data[$name])) {
				$checkbox->checked($this->request->data[$name]);
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
			if($autofill && isset($this->request->data[$name])) {
				$radio->checked($this->request->data[$name]);
			}
		} catch(\Exception $e) {
		}

		return $radio;
	}

	/**
	 * @var string $name
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
			$textarea->append($this->request->getString($name));
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
			if($autofill && isset($this->request->data[$name])) {
				$select->selected($this->request->data[$name]);
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
	 * @param string $key
	 * @return \Aqua\UI\Form\FieldInterface|null
	 */
	public function field($key)
	{
		return (isset($this->content[$key]) ? $this->content[$key] : null);
	}

	/**
	 * @param mixed $content
	 * @return \Aqua\UI\Form
	 */
	public function append($content)
	{
		$this->content[] = $content;

		return $this;
	}

	/**
	 * @param mixed $content
	 * @return \Aqua\UI\Form
	 */
	public function prepend($content)
	{
		array_unshift($this->content, $content);

		return $this;
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

	/**
	 * @param string        $content
	 * @param \Aqua\UI\Form $form
	 * @return string
	 */
	public function renderBody($content, self $form)
	{
		$html = "<form method=\"$this->method\"";
		if($form->name) {
			$html .= " name=\"$form->name\"";
		}
		if($form->action) {
			$html .= " action=\"$form->action\"";
		}
		if($form->target) {
			$html .= " target=\"$form->target\"";
		}
		if($form->enctype) {
			$html .= " enctype=\"$form->enctype\"";
		}
		if($form->autocomplete !== null) {
			$html .= ' autocomplete="' . ($form->autocomplete ? 'on' : 'off') . '"';
		}
		if($form->novalidate) {
			$html .= ' novalidate';
		}
		$html .= "><table class=\"ac-form-table\">";
		if(!empty($form->message)) {
			$html .= "<tr class=\"ac-form-error\"><td colspan=\"3\"><div>{$form->message}</div></td></tr>";
		}
		$html .= "$content</table></form>";

		return $html;
	}

	/**
	 * @param bool|callable $tagRenderFunction
	 * @param bool|callable $bodyRenderFunction
	 * @param array         $fields
	 * @return string
	 */
	public function render($tagRenderFunction = null, $bodyRenderFunction = null, array $fields = null)
	{
		$content = '';
		if($tagRenderFunction !== false && !is_callable($tagRenderFunction)) {
			$tagRenderFunction = array( $this, 'renderTag' );
		}
		if($bodyRenderFunction !== false && !is_callable($bodyRenderFunction)) {
			$bodyRenderFunction = array( $this, 'renderBody' );
		}
		if($tagRenderFunction) {
			if($fields) foreach($fields as $key) {
				if(isset($this->content[$key])) {
					$content .= call_user_func($tagRenderFunction, $this->content[$key]);
				} else if($key instanceof Tag) {
					call_user_func($tagRenderFunction, $key);
				}
			} else foreach($this->content as $tag) {
				$content .= call_user_func($tagRenderFunction, $tag);
			}
		}
		reset($this->content);
		if($bodyRenderFunction) {
			return call_user_func($bodyRenderFunction, $content, $this);
		} else {
			return $content;
		}
	}

	/**
	 * @return \Aqua\UI\Tag
	 */
	public function buildTag()
	{
		$tag = new Tag('form');
		$tag->attr('method', $this->method);
		if($this->name) $tag->attr('name', $this->name);
		if($this->action) $tag->attr('action', $this->action);
		if($this->target) $tag->attr('target', $this->target);
		if($this->enctype) $tag->attr('enctype', $this->enctype);
		if($this->novalidate) $tag->bool('novalidate');
		if($this->autocomplete !== null) $tag->attr('autocomplete', ($this->autocomplete ? 'on' : 'off'));

		return $tag;
	}

	/**
	 * @param callable $validator
	 * @param bool     $stop_on_error
	 * @return int
	 */
	public function validate($validator = null, $stop_on_error = true)
	{
		if(strcasecmp($this->method, $this->request->method) !== 0) {
			$this->status = self::VALIDATION_INCOMPLETE;

			return $this->status;
		}
		do {
			$tag = current($this->content);
			if(!$tag instanceof FieldInterface) {
				continue;
			}
			$this->status = $tag->validate($this->request, $this->message);
			if($this->status === self::VALIDATION_INCOMPLETE ||
			   ($stop_on_error && $this->status !== self::VALIDATION_SUCCESS)) {
				reset($this->content);
				return $this->status;
			}
		} while(next($this->content));
		reset($this->content);
		if(is_callable($validator) && $validator($this, $this->message) === false) {
			$this->status = self::VALIDATION_FAIL;
			return $this->status;
		}

		return $this->status;
	}

	public function __toString()
	{
		return $this->render();
	}

	public function __clone()
	{
		foreach($this->content as $key => $field) {
			if(is_object($field)) {
				$this->content[$key] = clone $field;
			}
		}
	}
}
