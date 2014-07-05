<?php
namespace Aqua\UI;

use Aqua\Core\L10n;
use Aqua\Core\Settings;
use Aqua\Http\Request;
use Aqua\UI\Form\FieldInterface;

class FormXML
extends Form
{
	/**
	 * @var \SimpleXMLElement
	 */
	public $xml;
	/**
	 * @var int
	 */
	public $fields = 0;
	/**
	 * @var array
	 */
	public $fieldsValidateFunctions = array();
	/**
	 * @var string
	 */
	public $validateFunction;

	/**
	 * @param \Aqua\Http\Request  $request
	 * @param \SimpleXMLElement   $xml
	 * @param \Aqua\Core\Settings $defaults
	 */
	public function __construct(Request $request, \SimpleXMLElement $xml, Settings $defaults = null)
	{
		$this->xml = $xml;
		$this->request = $request;
		if(!$defaults instanceof Settings) {
			$defaults = new Settings;
		}
		$this->validateFunction = (string)$xml->validate;
		foreach($xml->settings as $settings) {
			$key         = (string)$settings->key;
			$type        = strtolower((string)$settings->type);
			if($key === '' || $type === '') {
				continue;
			}
			$label       = '';
			$description = '';
			foreach(array( array( $settings->label, &$label ),
			               array( $settings->description, &$description ) ) as $x) {
				$nodes = $x[0];
				$var   = &$x[1];
				foreach($nodes as $str) {
					$language = (string)$str->attributes()->language;
					if($language === '') {
						$var = (string)$str;
					} else if(strcasecmp($language, L10n::$code) === 0) {
						$var = (string)$str;
						break;
					}
				}
			}
			$settingsArray = (array)$settings;
			$value         = $defaults->get($key, '');
			if($value instanceof Settings) {
				$value = $value->toArray();
			}
			$this->_escapeField($value);
			switch($type) {
				case 'text':
				case 'range':
				case 'number':
				case 'email':
				case 'date':
				case 'color':
				case 'url':
				case 'input':
					$input = $this->input($key);
					$input->type($type);
					$input->value((string)$value);
					break;
				case 'checkbox':
				case 'radio':
					$options = array();
					if(isset($settingsArray['option'])) {
						foreach($settings->option as $opt) {
							$options[(string)$opt->attributes()->key] = (string)$opt;
						}
					} else {
						$options = array( '1' => '' );
					}
					if($type === 'checkbox') {
						$input = $this->checkbox($key);
					} else {
						$input = $this->radio($key);
					}
					$input->value($options);
					$input->checked($value);
					break;
				case 'select':
					if(isset($settingsArray['option'])) {
						$options = $settingsArray['option'];
					} else {
						continue;
					}
					$input = $this->select($key);
					$input->value($options);
					$input->selected($value);
					break;
				case 'textarea':
					$input = $this->textarea($key);
					$input->append((string)$value);
					break;
				default:
					continue;
			}
			$input->setLabel($label);
			if($description) {
				$input->setDescription($description);
			}
			if($input instanceof Tag && ($attributes = $settings->attr[0])) {
				foreach($attributes->children() as $attr) {
					$name  = htmlspecialchars((string)$attr->getName());
					$value = (string)$attr;
					if($value === '') {
						$input->bool($name);
					} else {
						$input->attr($name, htmlentities($value, ENT_QUOTES, 'UTF-8'));
					}
				}
			}
			if($settings->validate) {
				$this->fieldsValidateFunctions[$key] = (string)$settings->validate;
			}
			++$this->fields;
		}
	}

	protected function _escapeField(&$value)
	{
		if(is_array($value)) {
			foreach($value as &$v) {
				$this->_escapeField($v);
			}

			return $value;
		} else {
			if(is_string($value)) {
				return htmlspecialchars($value);
			} else {
				return (string)$value;
			}
		}
	}

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
			$key = key($this->content);
			if(isset($this->fieldsValidateFunctions[$key])) {
				$fn = create_function('$form,$value,&$message', $this->fieldsValidateFunctions[$key]);
				$res = $fn($this, $this->request->data($key));
				if($res === false || $res === self::VALIDATION_FAIL) {
					$this->status = self::VALIDATION_FAIL;
				} else if($res === self::VALIDATION_INCOMPLETE) {
					$this->status = self::VALIDATION_INCOMPLETE;
				} else {
					continue;
				}
				reset($this->content);
				return $this->status;
			}
		} while(next($this->content));
		reset($this->content);
		$validators = array( $validator );
		if($this->validateFunction) {
			$fn = create_function('$form,&$message', $this->validateFunction);
			$validators[] = $fn;
		}
		foreach($validators as $validator) {
			if(is_callable($validator)) {
				$res = $validator($this, $this->message);
				if($res === false || $res === self::VALIDATION_FAIL) {
					$this->status = self::VALIDATION_FAIL;
				} else if($res === self::VALIDATION_INCOMPLETE) {
					$this->status = self::VALIDATION_INCOMPLETE;
				}
				return $this->status;
			}
		}

		return $this->status;
	}
}
