<?php
namespace Aqua\UI;

use Aqua\Http\Request;
use Aqua\SQL;
use Aqua\UI\Search\Input;
use Aqua\UI\Search\Range;
use Aqua\UI\Search\SearchFieldInterface;
use Aqua\UI\Search\Select;

class Search
extends AbstractForm
{
	public $method = 'GET';
	public $order = array();
	public $defaultOrder = null;
	public $defaultSorting = self::SORT_ASC;
	public $orderKey = 'order';
	public $sortingKey = 'sort';
	protected $baseUri = '';

	const SORT_ASC  = 0;
	const SORT_DESC = 1;

	public function __construct(Request $request, $orderKey = 'order', $sortingKey = 'sort')
	{
		$this->orderKey = $orderKey;
		$this->sortingKey = $sortingKey;
		parent::__construct($request);
	}

	public function input($name, $autofill = true)
	{
		if(isset($this->content[$name]) && $this->content[$name] instanceof Input) {
			return $this->content[$name];
		}
		$input = new Input($name);
		if($autofill && ($val = $this->getString($name))) {
			$input->value($val);
		}
		$this->content[$name] = $input;
		return $input;
	}

	public function select($name, $autofill = true)
	{
		if(isset($this->content[$name]) && $this->content[$name] instanceof Select) {
			return $this->content[$name];
		}
		$select = new Select($name);
		try {
			if($autofill && (($val = $this->getString($name)) || ($val = $this->getArray($name)))) {
				$select->selected($val);
			}
		} catch(\Exception $e) { }
		$this->content[$name] = $select;
		return $select;
	}

	public function range($name, $autofill = true)
	{
		if(isset($this->content[$name]) && $this->content[$name] instanceof Range) {
			return $this->content[$name];
		}
		$range = new Range($name);
		if($autofill && ($val = $this->getArray($name)) &&
		   array_key_exists('min', $val) && array_key_exists('max', $val)) {
			$range->min->value($val['min']);
			$range->max->value($val['max']);
		}
		$this->content[$name] = $range;
		return $range;
	}

	public function order(array $orders)
	{
		$this->order = $orders;
		return $this;
	}

	public function defaultOrder($order)
	{
		$this->defaultOrder = $order;
		return $this;
	}

	public function defaultSorting($sort)
	{
		$this->defaultSorting = $sort;
		return $this;
	}

	public function apply(SQL\Search $search, $validator = null)
	{
		$this->validate($validator, false);
		if($order = $this->getOrder()) {
			$search->order(array( $this->order[$order] => $this->getSorting() ? 'DESC' : 'ASC' ));
		}
		foreach($this->content as $field) {
			if($field instanceof SearchFieldInterface && !$field->getWarning() &&
			   ($column = $field->getColumn()) && ($where = $field->parse($this))) {
				if(is_array($column)) {
					$x = array();
					foreach($column as $col) {
						$x[] = array( $col => $where );
						$x[] = 'OR';
					}
					array_pop($x);
					$search->where($x);
				} else {
					$search->where(array( $column => $where ));
				}
			}
		}
		return $this;
	}

	public function getOrder()
	{
		if(($order = $this->request->uri->getString($this->orderKey)) && array_key_exists($order, $this->order)) {
			return $order;
		} else if($this->defaultOrder) {
			return $this->defaultOrder;
		} else {
			return null;
		}
	}

	public function getSorting()
	{
		if($sorting = array_search(strtolower($this->request->uri->getString($this->sortingKey)), array('asc', 'desc'))) {
			return $sorting;
		} else {
			return $this->defaultSorting;
		}
	}

	public function buildUrl($order, $sorting)
	{
		$query = $this->request->uri->parameters;
		if($this->orderKey) {
			if($order) {
				$query[$this->orderKey] = $order;
			} else {
				unset($query[$this->orderKey]);
			}
		}
		if($this->sortingKey) {
			if($order) {
				$query[$this->sortingKey] = $sorting;
			} else {
				unset($query[$this->sortingKey]);
			}
		}
		return $this->request->uri->url(array( 'query' => $query ));
	}

	public function renderHeader(array $columns, $renderColumn = null)
	{
		$currentOrder   = $this->getOrder();
		$currentSorting = $this->getSorting();
		$renderColumn   = $renderColumn ?: array( $this, 'renderHeaderColumn' );
		$html = '';
		foreach($columns as $key => $name) {
			if(is_array($name)) {
				$colspan = max(1, (int)$name[1]);
				$name    = $name[0];
			} else {
				$colspan = 1;
			}
			$url = $class = null;
			if(isset($this->order[$key])) {
				if($currentOrder === $key) {
					$url = $this->buildUrl($key, $currentSorting ? 'asc' : 'desc');
					$class = 'ac-table-order-active ac-table-sorting-' . ($currentSorting ? 'desc' : 'asc');
				} else {
					$url = $this->buildUrl($key, 'asc');
					$class = 'ac-table-sorting-asc';
				}
			}
			$html.= call_user_func($renderColumn, $key, $name, $url, $class, $colspan);
		}
		return $html;
	}

	public function renderHeaderColumn($key, $name, $url, $class, $colspan)
	{
		$html = '<td';
		if($colspan > 1) {
			$html.= " colspan=\"$colspan\"";
		}
		if($url) {
			$html.= " class=\"ac-table-order $class\"><a href=\"$url\">$name<div class=\"ac-table-bullet\"></div></a></td>";
		} else {
			$html.= ">$name</td>";
		}
		return $html;
	}
}
