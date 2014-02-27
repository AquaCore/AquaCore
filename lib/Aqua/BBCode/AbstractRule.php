<?php
namespace Aqua\BBCode;

use Aqua\Event\EventDispatcher;
use Aqua\Event\SubjectInterface;

abstract class AbstractRule
implements SubjectInterface
{
	/**
	 * @var array
	 */
	public $settings = array();
	/**
	 * @var array
	 */
	public $tags = array();
	/**
	 * @var \Aqua\Event\EventDispatcher
	 */
	protected $_dispatcher;

	/**
	 * @param array $settings
	 */
	public function __construct(array $settings = array())
	{
		$this->settings    = $settings + $this->settings;
		$this->_dispatcher = new EventDispatcher;
		foreach($this->tags as $tag) {
			$this->normalizeTag($tag);
		}
	}

	/**
	 * Check whether a BBCode tag has been used in a valid context
	 * e.g.: [*] inside [list]
	 *
	 * @param \Aqua\BBCode\Node $node
	 * @return bool
	 */
	public function context(Node $node)
	{
		$ret = true;
		if(!isset($this->tags[$node->name])) {
			return false;
		}
		$context  = $this->tags[$node->name]['context'];
		$children = $this->tags[$node->name]['children'];
		if(!empty($context)) {
			$ret = false;
			foreach($context as $name => $max) {
				$_node = $node->parent;
				$level = 1;
				do {
					if(!$_node) {
						$ret = ($max !== 0 && $max <= $level || $max === 0);
						continue 2;
					} else if($_node->name === $name) {
						if($max === null) {
							$ret = true;
							continue 2;
						} else {
							if($max === 0) {
								$ret = false;
								continue 2;
							} else {
								if($max >= $level) {
									$ret = true;
									continue 2;
								}
							}
						}
					}
					++$level;
				} while(($_node = $_node->parent) && $level <= $max);
			}
		}
		if(!empty($children)) {
			$ret = false;
			foreach($children as $name) {
				foreach($node->children as $_node) {
					if($_node->name === $name) {
						$ret = true;
					} else {
						$ret = false;
						break;
					}
				}
			}
		}
		$feedback = array( $node, $this->tags[$node->name], $ret );
		$_ret     = $this->notify("context", $feedback);

		return (is_bool($_ret) ? $_ret : $ret);
	}

	/**
	 * @param \Aqua\BBCode\Node $node
	 * @return array|bool
	 */
	public function parse(Node $node)
	{
		if(!isset($this->tags[$node->name])) {
			return false;
		}
		$tag  = $this->tags[$node->name];
		$html = array(
			'name'           => $tag['htmlTag'],
			'template'       => $tag['template'],
			'parseContent'   => $tag['parseContent'],
			'stripContent'   => $tag['stripContent'],
			'optionalClose'  => $tag['optionalClose'],
			'trimLineBreaks' => $tag['trimLineBreaks'],
			'attributes'     => array(),
		);
		foreach($tag['attributes'] as $name => $attribute) {
			if($attribute['value']) {
				$value = $attribute['value'];
			} else {
				if(!empty($node->attributes[$name])) {
					if($attribute['pattern']) {
						if(!preg_match($attribute['pattern'], $node->attributes[$name], $match)) {
							return false;
						} else {
							if(is_callable($attribute['format'])) {
								$value = call_user_func_array($attribute['format'], $match);
								if($value === false) {
									if($attribute['optional'] === false) {
										return false;
									} else {
										continue;
									}
								}
							} else {
								if($attribute['format']) {
									$match[0] = $attribute['format'];
									$value    = call_user_func_array('sprintf', $match);
								} else {
									$value = $node->attributes[$name];
								}
							}
						}
					} else {
						if(is_callable($attribute['format'])) {
							$value = call_user_func($attribute['format'], $node->attributes[$name]);
							if($value === false) {
								if($attribute['optional'] === false) {
									return false;
								} else {
									continue;
								}
							}
						} else {
							if($attribute['format']) {
								$value = call_user_func('sprintf', $attribute['format'], $node->attributes[$name]);
							} else {
								$value = $node->attributes[$name];
							}
						}
					}
					if($attribute['encode'] === true) {
						$value = htmlentities($value, ENT_QUOTES, 'UTF-8');
					} else {
						if(is_callable($attribute['encode'])) {
							$value = call_user_func($attribute['encode'], $value);
						}
					}
				} else {
					if($attribute['optional'] === false) {
						return false;
					} else {
						continue;
					}
				}
			}
			if($attribute['map']) {
				if(!is_array($attribute['map'])) {
					$attribute['map'] = array( $attribute['map'] );
				}
				foreach($attribute['map'] as $attr) {
					if(isset($html['attributes'][$attr])) {
						$html['attributes'][$attr] .= ' ' . $value;
					} else {
						$html['attributes'][$attr] = $value;
					}
				}
			}
		}
		$feedback = array( $node, $tag, &$html );
		if($this->notify('parse', $feedback) === false) {
			return false;
		} else {
			return $html;
		}
	}

	/**
	 * @param string $name
	 * @param array  $options
	 * @return \Aqua\BBCode\AbstractRule
	 */
	public function addTag($name, array $options = array())
	{
		$this->normalizeTag($options);
		$this->tags[strtolower($name)] = $options;

		return $this;
	}

	/**
	 * @param array $tag
	 */
	public function normalizeTag(array &$tag)
	{
		$tag += array(
			'htmlTag'        => 'div',
			'template'       => null,
			'optionalClose'  => false,
			'parseContent'   => true,
			'stripContent'   => false,
			'trimLineBreaks' => false,
			'context'        => array(),
			'children'       => array(),
			'attributes'     => array()
		);
		foreach($tag['attributes'] as &$attr) {
			$attr += array(
				'map'      => null,
				'encode'   => true,
				'optional' => false,
				'pattern'  => null,
				'format'   => null,
				'value'    => null
			);
		}
	}

	public function attach($event, \Closure $callback)
	{
		$this->_dispatcher->attach("bbcode-rule.$event", $callback);

		return $this;
	}

	public function detach($event, \Closure $callback)
	{
		$this->_dispatcher->detach("bbcode-rule.$event", $callback);

		return $this;
	}

	public function notify($event, &$feedback = array())
	{
		return $this->_dispatcher->notify("bbcode-rule.$event", $feedback);
	}
}
