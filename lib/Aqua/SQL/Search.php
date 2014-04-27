<?php
namespace Aqua\SQL;

class Search
extends Select
{
	/**
	 * @var array
	 */
	public $whereOptions = array();
	/**
	 * @var array
	 */
	public $havingOptions = array();

	/**
	 * @param array $where
	 * @param bool  $merge
	 * @return static
	 */
	public function whereOptions(array $where, $merge = true)
	{
		if($merge) {
			$this->whereOptions = array_merge($this->whereOptions, $where);
		} else {
			$this->whereOptions = $where;
		}

		return $this;
	}

	/**
	 * @param array $having
	 * @param bool  $merge
	 * @return static
	 */
	public function havingOptions(array $having, $merge = true)
	{
		if($merge) {
			$this->havingOptions = array_merge($this->havingOptions, $having);
		} else {
			$this->havingOptions = $having;
		}

		return $this;
	}

	/**
	 * @param mixed  $options
	 * @param        $values
	 * @param string $type
	 * @return string|null
	 */
	public function parseSearch(&$options, &$values, $type = null)
	{
		if($type === 'where') {
			$columns = & $this->whereOptions;
		} else {
			$columns = & $this->havingOptions;
		}
		$query = '';
		$i     = 0;
		if(!is_array($options)) {
			return null;
		} else {
			foreach($options as $alias => &$value) {
				if($i % 2) {
					++$i;
					if(is_string($value)) {
						$v = strtoupper($value);
						if($v === 'AND' || $v === 'OR') {
							$query .= "$v ";
							continue;
						}
					}
					$query .= 'AND ';
				}
				if(is_int($alias)) {
					if(is_string($value)) {
						$query .= "$value ";
						++$i;
					} else if($q = $this->parseSearch($value, $values, $type)) {
						$query .=  "$q ";
						++$i;
					}
				} else if(array_key_exists($alias, $columns)) {
					if(!is_array($value)) {
						$value = array( self::SEARCH_NATURAL, $value );
					}
					if($this->parseSearchFlags($value, $w, $values, $columns[$alias], $alias)) {
						$query .= "$w ";
						++$i;
					}
				}
			}
		}
		if($i === 0) {
			return null;
		}
		$query = preg_replace('/(^\s*(AND|OR)\s*)|(\s*(AND|OR)\s*$)/i', '', $query);
		if($i === 1) {
			return $query;
		} else {
			return "($query)";
		}
	}

	public function parseOrder()
	{
		if(empty($this->order)) return '';
		$order = array();
		foreach($this->order as $column => $ord) {
			if($ord === 'DESC' || $ord === 'ASC') {
				if(array_key_exists($column, $this->columns)) {
					$column = $this->columns[$column];
				}
				$order[] = "$column $ord";
			} else {
				if(array_key_exists($ord, $this->columns)) {
					$ord = $this->columns[$ord];
				}
				$order[] = $ord;
			}
		}
		if(empty($order)) return '';
		else return 'ORDER BY ' . implode(', ', $order);
	}
}
