<?php
namespace Aqua\UI;

use Aqua\Core\App;
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
	public $clause = array();
	public $order = array();
	public $limit = array();
	public $joins = array();
	public $currentPage = null;
	public $defaultOrder = null;
	public $defaultSorting = self::SORT_ASC;
	public $defaulLimit = null;
	public $persistKey = null;
	public $orderKey = 'order';
	public $sortingKey = 'sort';
	public $limitKey = 'limit';
	protected $baseUri = '';
	protected $_meta;

	const META_KEY = 'search';

	const SORT_ASC  = 0;
	const SORT_DESC = 1;

	const CLAUSE_WHERE  = 0;
	const CLAUSE_HAVING = 1;

	public function __construct(Request $request,
	                            $currentPage = null,
	                            $orderKey    = 'order',
	                            $sortingKey  = 'sort',
	                            $limitKey    = 'limit')
	{
		$this->currentPage = $currentPage;
		$this->orderKey = $orderKey;
		$this->sortingKey = $sortingKey;
		$this->limitKey = $limitKey;
		parent::__construct($request);
	}

	/**
	 * @param string $name
	 * @param int    $clause
	 * @param bool   $autofill
	 * @return \Aqua\UI\Search\Input
	 */
	public function input($name, $clause = self::CLAUSE_WHERE, $autofill = true)
	{
		if(isset($this->content[$name]) && $this->content[$name] instanceof Input) {
			return $this->content[$name];
		}
		$input = new Input($name);
		if($autofill && ($val = $this->getString($name)) !== null) {
			$input->value($val);
		}
		$this->content[$name] = $input;
		$this->clause[$name] = $clause;
		return $input;
	}

	/**
	 * @param string $name
	 * @param int    $clause
	 * @param bool   $autofill
	 * @return \Aqua\UI\Search\Select
	 */
	public function select($name, $clause = self::CLAUSE_WHERE, $autofill = true)
	{
		if(isset($this->content[$name]) && $this->content[$name] instanceof Select) {
			return $this->content[$name];
		}
		$select = new Select($name);
		try {
			if($autofill && (($val = $this->getString($name)) !== '' || ($val = $this->getArray($name)))) {
				$select->selected($val);
			}
		} catch(\Exception $e) { }
		$this->content[$name] = $select;
		$this->clause[$name] = $clause;
		return $select;
	}

	/**
	 * @param string $name
	 * @param int    $clause
	 * @param bool   $autofill
	 * @return \Aqua\UI\Search\Range
	 */
	public function range($name, $clause = self::CLAUSE_WHERE, $autofill = true)
	{
		if(isset($this->content[$name]) && $this->content[$name] instanceof Range) {
			return $this->content[$name];
		}
		$range = new Range($name);
		if($autofill && ($val = $this->getArray($name)) &&
		   array_key_exists(0, $val) && array_key_exists(1, $val)) {
			$range->min->value($val[0]);
			$range->max->value($val[1]);
		}
		$this->content[$name] = $range;
		$this->clause[$name] = $clause;
		return $range;
	}

	/**
	 * @param array $orders
	 * @return \Aqua\UI\Search
	 */
	public function order(array $orders)
	{
		$this->order = $orders;
		return $this;
	}

	/**
	 * @param string $order
	 * @param int    $sorting
	 * @return \Aqua\UI\Search
	 * @see \Aqua\UI\Search\Search::SORT_ASC
	 * @see \Aqua\UI\Search\Search::SORT_DESC
	 */
	public function defaultOrder($order, $sorting = null)
	{
		$this->defaultOrder = $order;
		$this->defaultSorting = $sorting;
		return $this;
	}

	/**
	 * @param int|array $range
	 * @param int       $count
	 * @param int       $offset
	 * @param int       $step
	 * @return \Aqua\UI\Search|\Aqua\UI\Form\Select
	 */
	public function limit($range = null, $count = 5, $offset = 10, $step = 10)
	{
		if(func_num_args() === 0) {
			$limit = new Form\Select($this->limitKey);
			$limit
				->attr('class', 'ac-search-limit')
				->value($this->limit)
				->selected($this->getLimit());
			return $limit;
		} else if(!is_array($range)) {
			$range = range($range, $count);
			array_walk($range, function(&$value, $key, $data) {
				$value = $data[0] + ($value * $data[1]);
			}, array( $offset, $step ));
		}
		$this->limit = array_combine($range, $range);

		return $this;
	}

	/**
	 * @param int $limit
	 * @return \Aqua\UI\Search
	 */
	public function defaultLimit($limit)
	{
		$this->defaulLimit = (int)$limit;

		return $this;
	}

	/**
	 * @param string $key
	 * @return \Aqua\UI\Search
	 */
	public function persist($key)
	{
		$this->persistKey = $key;
		if(App::user()->loggedIn()) {
			$meta = App::user()->account->meta->get(self::META_KEY, array());
			if(array_key_exists($this->persistKey, $meta)) {
				$data = $meta[$this->persistKey];
			} else {
				$data = array( null, null, null );
			}
			$update = array();
			if($this->limitKey && ($limit = $this->getInt($this->limitKey, null)) &&
			   array_key_exists($limit, $this->limit) && $limit !== $data[0]) {
				$update[0] = $limit;
			}
			if($this->orderKey && ($order = strtolower($this->getString($this->orderKey))) &&
			   array_key_exists($order, $this->order) && $order !== $data[1]) {
				$update[1] = $order;
			}
			if($this->sortingKey && ($sort = strtolower($this->getString($this->sortingKey))) && ($sort === 'asc' || $sort === 'desc')) {
				$sort = ($sort === 'asc' ? self::SORT_ASC : self::SORT_DESC);
				if($sort !== $data[1]) {
					$update[2] = $sort;
				}
			}
			if(count($update)) {
				$update+= $data;
				ksort($update);
				$meta[$this->persistKey] = $update;
				App::user()->account->meta->set(self::META_KEY, $meta);
			}
		}

		return $this;
	}

	/**
	 * @param string         $alias
	 * @param string         $column
	 * @return \Aqua\SQL\Join
	 */
	public function join($alias, $column = null)
	{
		if(isset($this->joins[$alias])) {
			$join = $this->joins[$alias];
		} else {
			$join = new SQL\Join();
		}
		$this->joins[$alias] = array( $join, $column );

		return $join;
	}

	/**
	 * @param \Aqua\SQL\Search $search
	 * @param callable        $validator
	 * @return \Aqua\UI\Search
	 */
	public function apply(SQL\Search $search, $validator = null)
	{
		$this->validate($validator, false);
		$joins = $this->joins;
		if($order = $this->getOrder()) {
			$column = $this->order[$order];
			if(isset($joins[$column])) {
				$key = $column;
				if($joins[$key][1]) {
					$search->whereOptions(array( $column => $joins[$key][1] ));
					$column = $joins[$key][1];
				}
				$search->join($joins[$key][0]);
				unset($joins[$key], $key);
			}
			$search->order(array( $column => $this->getSorting() ? 'DESC' : 'ASC' ));
		}
		if($limit = $this->getLimit()) {
			$search->limit(($this->currentPage - 1) * $limit, $limit);
		}
		foreach($this->content as $key => $field) {
			if($field instanceof SearchFieldInterface && !$field->getWarning() &&
			   ($column = $field->getColumn()) && ($where = $field->parse($this))) {
				if(is_array($column)) {
					$x = array();
					foreach($column as $col) {
						if(isset($joins[$col])) {
							if($joins[$col][1]) {
								$search->whereOptions(array( $col => $joins[$col][1] ));
							}
							$search->join($joins[$col][0]);
							unset($joins[$col]);
						}
						$x[] = array( $col => $where );
						$x[] = 'OR';
					}
					array_pop($x);
				} else {
					if(isset($joins[$column])) {
						if($joins[$column][1]) {
							$search->whereOptions(array( $column => $joins[$column][1] ));
						}
						$search->join($joins[$column][0]);
						unset($joins[$column]);
					}
					$x = array( $column => $where );
				}
				if($this->clause[$key] === self::CLAUSE_WHERE) {
					$search->where($x);
				} else {
					$search->having($x);
				}
			}
		}
		return $this;
	}

	/**
	 * @return int|null
	 */
	public function getLimit()
	{
		if($this->persistKey && App::user()->loggedIn() &&
		   ($limit = App::user()->account->meta->getArray(self::META_KEY, $this->persistKey, 0)) &&
		   array_key_exists($limit, $this->limit)) {
			return $limit;
		} else if($this->limitKey && ($limit = $this->getInt($this->limitKey, null)) && array_key_exists($limit, $this->limit)) {
			return $limit;
		} else {
			return $this->defaulLimit;
		}
	}

	/**
	 * @return string|null
	 */
	public function getOrder()
	{
		if($this->persistKey && App::user()->loggedIn() &&
		   ($order = App::user()->account->meta->getArray(self::META_KEY, $this->persistKey, 1)) &&
		   array_key_exists($order, $this->order)) {
			return $order;
		} else if($this->orderKey && ($order = $this->getString($this->orderKey)) && array_key_exists($order, $this->order)) {
			return $order;
		} else {
			return $this->defaultOrder;
		}
	}

	/**
	 * @return int|null
	 */
	public function getSorting()
	{
		if($this->persistKey && App::user()->loggedIn() &&
		   ($sort = App::user()->account->meta->getArray(self::META_KEY, $this->persistKey, 2)) &&
		   ($sort === self::SORT_DESC || $sort === self::SORT_ASC)) {
			return $sort;
		} else if($this->sortingKey && ($sorting = array_search(strtolower($this->getString($this->sortingKey)), array('asc', 'desc'))) !== false) {
			return $sorting;
		} else {
			return $this->defaultSorting;
		}
	}

	/**
	 * @param string $order
	 * @param int    $sorting
	 * @return string
	 */
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

	/**
	 * @param array    $columns
	 * @param callable $renderColumn
	 * @return string
	 */
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
					$url = $this->buildUrl($key, $currentSorting === self::SORT_ASC ? 'desc' : 'asc');
					$class = 'ac-table-order-active ac-table-sorting-' . ($currentSorting === self::SORT_ASC ? 'asc' : 'desc');
				} else {
					$url = $this->buildUrl($key, 'asc');
					$class = 'ac-table-sorting-asc';
				}
			}
			$html.= call_user_func($renderColumn, $key, $name, $url, $class, $colspan);
		}
		return $html;
	}

	/**
	 * @param string $key
	 * @param string $name
	 * @param string $url
	 * @param string $class
	 * @param string $colspan
	 * @return string
	 */
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
