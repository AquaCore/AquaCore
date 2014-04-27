<?php
namespace Aqua\BBCode;

use Aqua\BBCode\Filter\ClickableFilter;
use Aqua\BBCode\Filter\SmileyFilter;
use Aqua\BBCode\Rule\ColorRule;
use Aqua\BBCode\Rule\FontRule;
use Aqua\BBCode\Rule\IndentRule;
use Aqua\BBCode\Rule\JustifyRule;
use Aqua\BBCode\Rule\ListRule;
use Aqua\BBCode\Rule\QuoteRule;
use Aqua\BBCode\Rule\SpoilerRule;
use Aqua\BBCode\Rule\TextRule;
use Aqua\BBCode\Rule\UrlRule;
use Aqua\BBCode\Rule\VideoRule;
use Aqua\UI\Template;

class BBCode
{
	/**
	 * @var \Aqua\BBCode\AbstractRule[]
	 */
	public $rules = array();

	/**
	 * @var \Aqua\BBCode\AbstractFilter[]
	 */
	public $filters = array();

	/**
	 * @var array
	 */
	public $disabled = array();

	/**
	 * @var bool
	 */
	public $autoClose = true;

	/**
	 * @var bool
	 */
	public $removeInvalidTags = false;

	public function __construct(array $options = array())
	{
		$defaults = array(
			'autoClose' => true,
			'removeInvalidTags' => false
		);
		$options = array_intersect_key($options + $defaults, $defaults);
		foreach($options as $key => $value) {
			$this->$key = $value;
		}
	}

	public function defaults()
	{
		$this->addRule('text', new TextRule());
		$this->addRule('font', new FontRule());
		$this->addRule('color', new ColorRule());
		$this->addRule('url', new UrlRule());
		$this->addRule('justify', new JustifyRule());
		$this->addRule('indent', new IndentRule());
		$this->addRule('list', new ListRule());
		$this->addRule('video', new VideoRule());
		$this->addRule('quote', new QuoteRule());
		$this->addRule('spoiler', new SpoilerRule());
		$this->addFilter('smiley', new SmileyFilter(), 0);
		$this->addFilter('clickable', new ClickableFilter(), 1);
		return $this;
	}

	public function addRule($name, AbstractRule $rule)
	{
		$this->rules[$name] = $rule;
		return $this;
	}

	public function deleteRule($name)
	{
		unset($this->rules[$name]);
		return $this;
	}

	public function getRule($tag_name)
	{
		if(isset($this->disabled[$tag_name]) && $this->disabled[$tag_name]) {
			return null;
		}
		foreach($this->rules as $rule) {
			if(isset($rule->tags[$tag_name])) {
				return $rule;
			}
		}
		return null;
	}

	public function addFilter($name, AbstractFilter $filter, $priority = null)
	{
		if(!is_int($priority) || $priority >= count($this->filters)) {
			$this->filters[$name] = $filter;
		} else {
			$this->filters = array_splice($this->filters, 0, $priority, true) + array( $name => $filter ) + array_splice($this->filters, $priority, null, true);
		}
		return $this;
	}

	public function deleteFilter($name)
	{
		unset($this->filters[$name]);
		return $this;
	}

	public function applyFilters($method, Node $node, &$content, &$parse)
	{
		if(empty($this->filters)) {
			return null;
		}
		$ret = null;
		$filter = end($this->filters);
		do {
			$filter->{$method}($this, $node, $content, $parse);
			if($ret === false) {
				break;
			}
		} while($filter = prev($this->filters));
		reset($this->filters);
		return $ret;
	}

	public function enable($tags)
	{
		if(is_array($tags)) {
			foreach($tags as $name) {
				unset($this->disabled[$name]);
			}
		} else {
			unset($this->disabled[$tags]);
		}
		return $this;
	}

	public function disable($tags)
	{
		if(is_array($tags)) {
			foreach($tags as $name) {
				$this->disabled[$name] = true;
			}
		} else {
			$this->disabled[$tags] = true;
		}
		return $this;
	}

	public function parse($content)
	{
		$nodes = Node::parse($content);
		return $this->parseNode($nodes);
	}

	public function parseNode(Node $node, $parse = true)
	{
		$open = $content = $close = '';
		if($node->type === Node::NODE_TEXT) {
			$content = $node->value;
			if($this->applyFilters('beforeParse', $node, $content, $parse) === false) {
				return '';
			}
			$content = htmlspecialchars($content);
			if($parse) {
				$content = preg_replace('/(\r\n|[\r\n])/', '<br>', $content);
			}
			if($this->applyFilters('afterParse', $node, $content, $parse) === false) {
				return '';
			}
			return $content;
		} else if(!$parse || $node->type !== Node::NODE_ELEMENT ||
				  (!$node->close && !$this->autoClose) ||
				  !($tag = $this->getRule($node->name)) ||
				  !$tag->context($node) ||
				  !($html = $tag->parse($node))) {
			if(!$this->removeInvalidTags) {
				$open = $node->open;
				$close = $node->close;
			}
			foreach($node->children as $_node) {
				$content.= $this->parseNode($_node, $parse);
			}
		} else {
			$parse = $html['parseContent'];
			if($html['stripContent'] !== true) {
				foreach($node->children as $_node) {
					if($html['stripContent'] !== false && $html['stripContent'] & $_node->type) {
						continue;
					}
					$content.= $this->parseNode($_node, $parse);
				}
				if($html['trimLineBreaks']) {
					if($html['trimLineBreaks'] === true) {
						$content = preg_replace('/(^(?:<br>)+)|((?:<br>)+$)/i', '', $content);
					} else {
						for($i = 0; $i < $html['trimLineBreaks']; ++$i) {
							$content = preg_replace('/(^<br>)|(<br>$)/i', '', $content);
						}
					}
				}
			}
			if($html['template']) {
				$tpl = new Template;
				$tpl->set('html', $html)
			        ->set('content', $content);
				$content = $tpl->render('bbcode/' . $html['template']);
			} else {
				$open = '<' . $html['name'];
				foreach($html['attributes'] as $attr => $value) {
					$open.= " $attr=\"$value\"";
				}
				$open.= '>';
				$close = "</{$html['name']}>";
				if($html['optionalClose'] && empty($node->children)) {
					$close = '';
				}
			}
		}
		return $open . $content . $close;
	}
}
