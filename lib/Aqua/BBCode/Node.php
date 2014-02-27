<?php
namespace Aqua\BBCode;

class Node
{
	/**
	 * @var string
	 */
	public $name;
	/**
	 * @var string
	 */
	public $open;
	/**
	 * @var string
	 */
	public $close;
	/**
	 * @var int
	 */
	public $type;
	/**
	 * @var string
	 */
	public $value = '';
	/**
	 * @var array
	 */
	public $attributes = array();
	/**
	 * @var \Aqua\BBCode\Node[]
	 */
	public $children = array();
	/**
	 * @var \Aqua\BBCode\Node
	 */
	public $parent;
	/**
	 * @var \Aqua\BBCode\Node
	 */
	public $previous;
	/**
	 * @var \Aqua\BBCode\Node
	 */
	public $next;

	const TAG_REGEX = '/(?:\[([^\/\]"\'= ]+)(?:=("[^"]*"|\'[^\']*\'|[^\]"\' ]*)?|((?: [^\/\]="\' ]+=(?:"[^"]*"|\'[^\']*\'|[^\]"\' ]*))*))?\]|\[\/([^\/\]"\'= ]+)\])/uS';
	const ATTRIBUTE_REGEX = '/(?:([^= ]+)+=(?:(\'[^\']*\'|"[^"]*")|([^ ]*)))/uS';

	const NODE_DOCUMENT = 1;
	const NODE_ELEMENT  = 2;
	const NODE_TEXT     = 4;

	public function __construct($type, $tag = null, $name = null, $value = null, array $attributes = array())
	{
		$this->type = $type;
		if($this->type === self::NODE_TEXT) {
			$this->value = $tag;
		} else {
			$this->open = $tag;
			$this->name = $name;
			$this->value = $value;
			$this->attributes = $attributes;
			if($value) {
				$this->attributes['$'] = &$this->value;
			}
		}
	}

	public function append(self $node)
	{
		return $this->add($node, count($this->children));
	}

	public function prepend(self $node)
	{
		return $this->add($node, 0);
	}

	public function add(self $node, $index)
	{
		$node->parent = $this;
		$count = count($this->children);
		if($count === 0) {
			$this->children[0] = $node;
		} else if($index >= $count) {
			$_node = $this->children[$count - 1];
			$node->previous = $_node;
			$node->next = null;
			if($_node) {
				$_node->next = $node;
			}
			$this->children[$count] = $node;
		} else {
			$_node = $this[$index];
			if($_node) {
				$node->next = $_node;
				$node->previous = $_node->previous;
				$_node->previous = $node;
				if($node->previous) {
					$node->previous->next = $node;
				}
			}
			array_splice($this->children, $index, 0, $node);
		}
		return $this;
	}

	public function remove(self $node)
	{
		$count = count($this->children);
		$hash  = spl_object_hash($node);
		for($i = 0; $i < $count; ++$i) {
			if($hash === spl_object_hash($node)) {
				array_splice($this->children, $i, 1);
				break;
			}
		}
		return $this;
	}

	public function clear()
	{
		$this->children = array();
		return $this;
	}

	public function close($name, $tag)
	{
		if($this->type === self::NODE_ELEMENT) {
			if($name === $this->name) {
				$this->close = $tag;
				return $this->parent;
			} else if($this->parent) {
				$node = $this->parent;
				do {
					if($node->name === $name) {
						return $node->close($name, $tag);
					}
				} while($node = $node->parent);
			}
		}
		$this->append(new self(self::NODE_TEXT, $tag));
		return $this;
	}

	public function content()
	{
		if($this->type === self::NODE_TEXT) {
			return $this->value;
		}
		$content = '';
		foreach($this->children as $node) {
			$content.= $node->open;
			$content.= $node->content();
			$content.= $node->close;
		}
		return $content;
	}

	public static function parse($content)
	{
		$currentNode = $node = new self(self::NODE_DOCUMENT);
		$lastIndex = 0;
		$count = preg_match_all(self::TAG_REGEX, $content, $matches, PREG_PATTERN_ORDER | PREG_OFFSET_CAPTURE);
		for($i = 0; $i < $count; ++$i) {
			$index = $matches[0][$i][1];
			if($index > $lastIndex) {
				$currentNode->append(new self(self::NODE_TEXT, substr($content, $lastIndex, $index - $lastIndex)));
			}
			if($matches[4][$i]) {
				$currentNode = $currentNode->close($matches[4][$i][0], $matches[0][$i][0]);
			} else {
				if($matches[2][$i][0]) {
					$firstChar = $matches[2][$i][0][0];
					$lastChar  = substr($matches[2][$i][0], -1);
					if($firstChar === $lastChar && ($firstChar === "'" || $firstChar === '"')) {
						$matches[2][$i][0] = substr($matches[2][$i][0], 1, -1);
					}
				}
				$_node = new self(
					self::NODE_ELEMENT,
					$matches[0][$i][0],
					$matches[1][$i][0],
					$matches[2][$i][0],
					($matches[3][$i] ? self::parseAttributes($matches[3][$i][0]) : array())
				);
				$currentNode->append($_node);
				$currentNode = $_node;
			}
			$lastIndex = $index + strlen($matches[0][$i][0]);
		}
		if(strlen($content) > $lastIndex) {
			$node->append(new self(self::NODE_TEXT, substr($content, $lastIndex)));
		}
		return $node;
	}

	public static function parseAttributes($str)
	{
		$attributes = array();
		$count = preg_match_all(self::ATTRIBUTE_REGEX, $str, $matches, PREG_PATTERN_ORDER);
		for($i = 0; $i < $count; ++$i) {
			if(empty($matches[2][$i])) {
				$attributes[$matches[1][$i]] = $matches[3][$i];
			} else {
				$attributes[$matches[1][$i]] = substr($matches[2][$i], 1, -1);
			}
		}
		return $attributes;
	}
}
