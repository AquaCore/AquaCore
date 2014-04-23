<?php
namespace Aqua\UI;

use Aqua\Http\Request;
use Aqua\UI\Form\FieldInterface;

class AbstractForm
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
	 * @param string                $content
	 * @param \Aqua\UI\AbstractForm $form
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
		$html .= "$content</table>";
		if($this->method === 'GET') {
			$html.= ac_form_path();
		}
		$html.= '</form>';

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
		if($this->method === 'GET') {
			$tag->append(ac_form_path());
		}
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
			$this->status = $tag->validate($this, $this->message);
			if($this->status === self::VALIDATION_INCOMPLETE ||
			   ($stop_on_error && $this->status !== self::VALIDATION_SUCCESS)) {
				reset($this->content);
				return $this->status;
			}
		} while(next($this->content));
		reset($this->content);
		if(is_callable($validator)) {
			$res = $validator($this, $this->message);
			if($res === false || $res === self::VALIDATION_FAIL) {
				$this->status = self::VALIDATION_FAIL;
			} else if($res === self::VALIDATION_INCOMPLETE) {
				$this->status = self::VALIDATION_INCOMPLETE;
			}
			return $this->status;
		}

		return $this->status;
	}

	public function get()
	{
		if($this->method === 'GET') {
			return call_user_func_array(array( $this->request->uri, 'get' ), func_get_args());
		} else {
			return call_user_func_array(array( $this->request, 'data' ), func_get_args());
		}
	}

	public function set($key, $value)
	{
		if($this->method === 'GET') {
			$this->request->uri->parameters[$key] = $value;
		} else {
			$this->request->data[$key] = $value;
		}
		return $this;
	}

	public function delete($key)
	{
		if($this->method === 'GET') {
			unset($this->request->uri->parameters[$key]);
		} else {
			unset($this->request->data[$key]);
		}
		return $this;
	}

	public function getString()
	{
		if($this->method === 'GET') {
			return call_user_func_array(array( $this->request->uri, 'getString' ), func_get_args());
		} else {
			return call_user_func_array(array( $this->request, 'getString' ), func_get_args());
		}
	}

	public function getInt()
	{
		if($this->method === 'GET') {
			return call_user_func_array(array( $this->request->uri, 'getInt' ), func_get_args());
		} else {
			return call_user_func_array(array( $this->request, 'getInt' ), func_get_args());
		}
	}

	public function getFloat()
	{
		if($this->method === 'GET') {
			return call_user_func_array(array( $this->request->uri, 'getFloat' ), func_get_args());
		} else {
			return call_user_func_array(array( $this->request, 'getFloat' ), func_get_args());
		}
	}

	public function getArray()
	{
		if($this->method === 'GET') {
			return call_user_func_array(array( $this->request->uri, 'getArray' ), func_get_args());
		} else {
			return call_user_func_array(array( $this->request, 'getArray' ), func_get_args());
		}
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
 