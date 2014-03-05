<?php
namespace Aqua\SQL;

class Select
extends AbstractSearch
implements \Iterator, \Countable
{
	/**
	 * @var array
	 */
	public $columns = array();
	/**
	 * @var array
	 */
	public $group = array();
	/**
	 * @var \Aqua\SQL\Join[]
	 */
	public $joins = array();
	/**
	 * @var array
	 */
	public $unions = array();
	/**
	 * @var array
	 */
	public $where = array();
	/**
	 * @var array
	 */
	public $having = array();
	/**
	 * @var array
	 */
	public $order = array();
	/**
	 * @var array
	 */
	public $limit = array( 0 => null, 1 => null );
	/**
	 * @var string
	 */
	public $tableName;
	/**
	 * @var string
	 */
	public $tableAlias;
	/**
	 * @var callable
	 */
	public $parser;
	/**
	 * @var int
	 */
	public $rowsFound = 0;
	/**
	 * @var array
	 */
	public $results = array();
	/**
	 * @var int
	 */
	public $resultsCount = 0;
	/**
	 * @var int
	 */
	public $key = 0;
	/**
	 * @var bool
	 */
	public $calcRows = false;
	/**
	 * @var bool
	 */
	public $rollup = false;
	/**
	 * @var bool
	 */
	public $highPriority = false;
	/**
	 * @var bool
	 */
	public $straightJoin = false;
	/**
	 * @var bool
	 */
	public $forUpdate = false;
	/**
	 * @var bool
	 */
	public $bufferResult = null;
	/**
	 * @var bool
	 */
	public $cache = null;
	/**
	 * @var string|null
	 */
	public $resultHint = null;
	/**
	 * @var string|null
	 */
	public $columnModifier = null;
	/**
	 * @var string
	 */
	public $errorCode;
	/**
	 * @var string
	 */
	public $errorInfo;

	public function current()
	{
		return $this->results[$this->key];
	}

	public function next()
	{
		++$this->key;
	}

	public function key()
	{
		return $this->key;
	}

	public function valid()
	{
		return ($this->resultsCount > $this->key);
	}

	public function rewind()
	{
		$this->key = 0;
	}

	public function count()
	{
		return $this->resultsCount;
	}

	/**
	 * @param string $column
	 * @param mixed  $default
	 * @return string|int|float
	 */
	public function get($column, $default = null)
	{
		if($this->valid()) {
			return $this->results[$this->key][$column];
		} else {
			return $default;
		}
	}

	public function getColumn($column, $key = null)
	{
		if(empty($this->results) || !array_key_exists($column, $this->current())) {
			return array();
		}
		return array_column($this->results, $column, $key);
	}

	/**
	 * @param array $columns
	 * @param bool  $merge
	 * @return static
	 */
	public function columns(array $columns, $merge = true)
	{
		if($merge) {
			$this->columns = array_merge($this->columns, $columns);
		} else {
			$this->columns = $columns;
		}

		return $this;
	}

	/**
	 * @param array $where
	 * @param bool  $merge
	 * @return static
	 */
	public function where(array $where, $merge = true)
	{
		if($merge) {
			$this->where = array_merge($this->where, $where);
		} else {
			$this->where = $where;
		}

		return $this;
	}

	/**
	 * @param array $having
	 * @param bool  $merge
	 * @return static
	 */
	public function having(array $having, $merge = true)
	{
		if($merge) {
			$this->having = array_merge($this->having, $having);
		} else {
			$this->having = $having;
		}

		return $this;
	}

	/**
	 * @param int|null $min
	 * @param int|null $max
	 * @return static
	 */
	public function limit($min = null, $max = null)
	{
		$this->limit[0] = intval($min);
		$this->limit[1] = intval($max);

		return $this;
	}

	/**
	 * @param string|array $order
	 * @return static
	 */
	public function order($order)
	{
		if(!is_array($order)) {
			$this->order = array( $order );
		} else {
			$this->order = $order;
		}

		return $this;
	}

	/**
	 * @param string|array $column
	 * @return static
	 */
	public function groupBy($column)
	{
		if(!is_array($column)) {
			$column = array( $column );
		}
		$this->group = $column;

		return $this;
	}

	/**
	 * @param \Aqua\SQL\Select|string $table
	 * @param string|null             $alias
	 * @return static
	 */
	public function from($table, $alias = null)
	{
		$this->tableName  = $table;
		$this->tableAlias = $alias;

		return $this;
	}

	/**
	 * @param \Aqua\SQL\Select $select
	 * @param bool             $all
	 * @return static
	 */
	public function union(self $select, $all = false)
	{
		$this->unions[] = array( $select, (bool)$all );

		return $this;
	}

	/**
	 * @param string       $type
	 * @param \Aqua\SQL\Select|string|array $tables
	 * @param string       $on
	 * @param string|null  $alias
	 * @return static
	 */
	public function join($type, $tables, $on, $alias = null)
	{
		$join = new Join;
		if(!is_array($tables)) {
			if($alias) $tables = array( $alias => $tables );
			else $tables = array( $tables );
		}
		$join->tables($tables)->on($on)->type($type);
		$this->joins[] = $join;

		return $this;
	}

	/**
	 * @param \Aqua\SQL\Select|string|array      $table
	 * @param string      $on
	 * @param string|null $alias
	 * @return static
	 */
	public function leftJoin($table, $on, $alias = null)
	{
		return $this->join('LEFT', $table, $on, $alias);
	}

	/**
	 * @param \Aqua\SQL\Select|string|array $table
	 * @param string       $on
	 * @param string|null  $alias
	 * @return static
	 */
	public function rightJoin($table, $on, $alias = null)
	{
		return $this->join('RIGHT', $table, $on, $alias);
	}

	/**
	 * @param \Aqua\SQL\Select|string|array $table
	 * @param string       $on
	 * @param string|null  $alias
	 * @return static
	 */
	public function innerJoin($table, $on, $alias = null)
	{
		return $this->join('INNER', $table, $on, $alias);
	}

	/**
	 * @param bool $val
	 * @return static
	 */
	public function calcRows($val = true)
	{
		$this->calcRows = (bool)$val;

		return $this;
	}

	/**
	 * @param bool $val
	 * @return static
	 */
	public function withRollup($val = true)
	{
		$this->rollup = (bool)$val;

		return $this;
	}

	/**
	 * @param bool $val
	 * @return static
	 */
	public function highPriority($val = true)
	{
		$this->highPriority = (bool)$val;

		return $this;
	}

	/**
	 * @param bool $val
	 * @return static
	 */
	public function straightJoin($val = true)
	{
		$this->straightJoin = (bool)$val;

		return $this;
	}

	/**
	 * @param bool $val
	 * @return static
	 */
	public function bufferResult($val = true)
	{
		$this->bufferResult = (bool)$val;

		return $this;
	}

	/**
	 * @param bool $val
	 * @return static
	 */
	public function cacheHint($val = true)
	{
		$this->cache = (bool)$val;

		return $this;
	}

	/**
	 * @param bool $val
	 * @return static
	 */
	public function forUpdate($val = true)
	{
		$this->forUpdate = (bool)$val;

		return $this;
	}

	/**
	 * @param string|null $val
	 * @return static
	 */
	public function resultHint($val = null)
	{
		switch(strtolower($val)) {
			case 'small':
			case 'big':
				$this->resultHint = strtoupper($val);
				break;
			default:
				$this->resultHint = null;
				break;
		}
		return $this;
	}

	/**
	 * @param string|null $val
	 * @return static
	 */
	public function columnModifier($val = null)
	{
		switch(strtolower($val)) {
			case 'all':
			case 'distinct':
			case 'distinctrow':
				$this->columnModifier = strtoupper($val);
				break;
			default:
				$this->columnModifier = null;
				break;
		}
		return $this;
	}

	/**
	 * @param callable $func
	 * @return static
	 */
	public function parser($func = null)
	{
		$this->parser = $func;

		return $this;
	}

	/**
	 * @param array $values Additional values for a prepared statement
	 * @return static
	 * @throws \Exception|\PDOException
	 */
	public function query($values = array())
	{
		if(!is_array($values)) $values = array();
		$sth = $this->dbh->prepare($this->buildQuery($values));
		foreach($values as $key => $value) $sth->bindValue($key, $value, $this->dataType($key, true));
		try {
			$sth->execute();
		} catch(\PDOException $exception) {
			$this->errorCode = $sth->errorCode();
			$this->errorInfo = $sth->errorInfo();
			throw $exception;
		}
		$results = array();
		while($data = $sth->fetch(\PDO::FETCH_ASSOC)) {
			foreach($this->columnTypes as $column => $type) {
				if(array_key_exists($column, $data)) switch($type) {
					case 'integer':
					case 'timestamp':
						$data[$column] = intval($data[$column]);
						break;
					case 'float':
						$data[$column] = floatval($data[$column]);
						break;
					case 'set':
						$data[$column] = explode(',', $data[$column]);
						break;
				}
			}
			if($this->parser) {
				if($result = call_user_func($this->parser, $data)) {
					$results[] = $result;
				}
			} else {
				$results[] = $data;
			}
		}
		$this->results      = $results;
		$this->resultsCount = count($results);
		$this->key          = 0;
		if($this->calcRows) {
			$this->rowsFound = (int)$this->dbh->query('SELECT FOUND_ROWS()')->fetch(\PDO::FETCH_COLUMN, 0);
		}

		return $this;
	}

	/**
	 * @param $values
	 * @return string
	 */
	public function buildQuery(&$values)
	{
		if(!is_array($values)) {
			$values = array();
		}
		$query  = 'SELECT' . $this->parseOptions();
		foreach($this->columns as $alias => $column) {
			if(is_int($alias)) {
				$query .= "\r\n$column, ";
			} else {
				$query .= "\r\n$column AS `$alias`, ";
			}
		}
		$query = substr($query, 0, -2);
		if($this->tableName instanceof self) {
			$query .= "\r\nFROM (\r\n" . $this->tableName->buildQuery($values) . "\r\n) AS {$this->tableAlias}";
		} else {
			$query .= "\r\nFROM {$this->tableName} {$this->tableAlias} ";
		}
		foreach($this->joins as $join) {
			$query .= "\r\n" . $join->buildQuery($values) . "\r\n";
		}
		if($where = $this->parseSearch($this->where, $values, 'where')) {
			$query .= "\r\nWHERE $where";
		}
		if($groupBy = $this->parseGroupBy()) {
			$query .= "\r\n$groupBy";
		}
		if($this->forUpdate) {
			$query .= "\r\nFOR UPDATE";
		}
		if($having = $this->parseSearch($this->having, $values, 'having')) {
			$query .= "\r\nHAVING $having";
		}
		if($order = $this->parseOrder()) {
			$query .= "\r\n$order";
		}
		if($limit = $this->parseLimit()) {
			$query .= "\r\n$limit";
		}
		if(!empty($this->unions)) {
			foreach($this->unions as $union) {
				/**
				 * @var \Aqua\SQL\Select $select
				 * @var bool             $all
				 */
				list($select, $all) = $union;
				$query .= "\r\nUNION";
				if($all) {
					$query .= " ALL";
				};
				$query .= "\r\n" . $select->buildQuery($values);
			}
		}

		return $query;
	}

	/**
	 * @return string
	 */
	public function parseOrder()
	{
		if(empty($this->order)) return '';
		$order = array();
		foreach($this->order as $column => $ord) {
			if($ord === 'DESC' || $ord === 'ASC') {
				$order[] = "$column $ord";
			} else {
				$order[] = $ord;
			}
		}
		if(empty($order)) return '';
		else return 'ORDER BY ' . implode(', ', $order);
	}

	/**
	 * @return string
	 */
	public function parseLimit()
	{
		$limit = '';
		if($this->limit[0] >= 1 || $this->limit[1] >= 1) {
			$limit .= "LIMIT {$this->limit[0]}";
			if($this->limit[1] >= 1) $limit .= ", {$this->limit[1]}";
		}

		return $limit;
	}

	/**
	 * @return string
	 */
	public function parseGroupBy()
	{
		$groupBy = '';
		foreach($this->group as $column => $ord) {
			if($ord === 'DESC' || $ord === 'ASC') {
				$groupBy[] = "$column $ord";
			} else if($ord === null) {
				$groupBy[] = $column;
			} else {
				$groupBy[] = $ord;
			}
		}
		if(empty($groupBy)) return '';
		else return 'GROUP BY ' . implode(', ', $groupBy) . ($this->rollup ? ' WITH ROLLUP' : '');
	}

	/**
	 * @return string
	 */
	public function parseOptions()
	{
		$options = '';
		if($this->columnModifier) {
			$options .= " {$this->columnModifier}";
		}
		if($this->highPriority) {
			$options .= ' HIGH_PRIORITY';
		}
		if($this->straightJoin) {
			$options .= ' STRAIGHT_JOIN';
		}
		if($this->resultHint) {
			$options .= " SQL_{$this->resultHint}_RESULT";
		}
		if($this->bufferResult) {
			$options .= ' SQL_BUFFER_RESULT';
		}
		if($this->calcRows) {
			$options .= ' SQL_CALC_FOUND_ROWS';
		}
		if($this->cache === true) {
			$options .= ' SQL_CACHE';
		} else if($this->cache === false) {
			$options .= ' SQL_NO_CACHE';
		}

		return $options;
	}
}
