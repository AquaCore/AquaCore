<?php
namespace Aqua\UI;

use Aqua\Core\Settings;
use Aqua\Http\Request;

class FormXML
extends Form
{
	/**
	 * @var int
	 */
	public $fields = 0;

	/**
	 * @param \Aqua\Http\Request  $request
	 * @param \SimpleXMLElement   $xml
	 * @param \Aqua\Core\Settings $defaults
	 */
	public function __construct(Request $request, \SimpleXMLElement $xml, Settings $defaults = null)
	{
		$this->request = $request;
		if(!$defaults instanceof Settings) {
			$defaults = new Settings;
		}
		foreach($xml->settings as $settings) {
			$key         = (string)$settings->key;
			$type        = strtolower((string)$settings->type);
			$label       = (string)$settings->label;
			$description = (string)$settings->description;
			if($key === '' || $type === '') {
				continue;
			}
			$_settings = (array)$settings;
			$value     = $defaults->get($key, '');
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
					$type  = 'text';
					$input = $this->input($key);
					$input->type($type);
					$input->value((string)$value);
					break;
				case 'checkbox':
				case 'radio':
					$options = array();
					if(isset($_settings['option'])) {
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
					if(isset($_settings['option'])) {
						$options = $_settings['option'];
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
}
