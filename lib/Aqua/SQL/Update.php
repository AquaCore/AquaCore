<?php
namespace Aqua\SQL;

class Update
extends AbstractSearch
{
	/**
	 * @var array
	 */
	public $set = array();
	/**
	 * @var array
	 */
	public $where = array();
	/**
	 * @var int
	 */
	public $limit = 0;
	/**
	 * @var array
	 */
	public $tables = array();
	/**
	 * @var
	 */
	public $lowPriority;
	/**
	 * @var
	 */
	public $ignore;
	/**
	 * @var int
	 */
	public $rowCount = 0;

	/**
	 * @param int $max
	 * @return \Aqua\SQL\Update
	 */
	public function limit($max)
	{
		$this->limit = $max;
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
	 * @param array $set
	 * @param bool $merge
	 * @return static
	 */
	public function set(array $set, $merge = true)
	{
		if($merge) {
			$this->set = array_merge($this->set, $set);
		} else {
			$this->set = $set;
		}
		return $this;
	}

	/**
	 * @param array $tables
	 * @param bool  $merge
	 * @return static
	 */
	public function tables(array $tables, $merge = true)
	{
		if($merge) {
			$this->tables = array_merge($this->tables, $tables);
		} else {
			$this->tables = $tables;
		}
		return $this;
	}

	/**
	 * @param array $values
	 * @return static
	 */
	public function query($values = array())
	{
		$sth = $this->dbh->prepare($this->buildQuery($values));
		foreach($values as $key => $value) $sth->bindValue($key, $value, $this->columnType($key, true));
		$sth->execute();
		$this->rowCount = $sth->rowCount();
		return $this;
	}

	/**
	 * @param $values
	 * @return string
	 */
	public function buildQuery(&$values)
	{
		$query = 'UPDATE';
		if($this->ignore) $query.= ' IGNORE';
		if($this->lowPriority) $query.= ' LOW_PRIORITY';
		foreach($this->tables as $alias => $name) {
			if(ctype_digit($alias)) {
				$query.= " $name, ";
			} else {
				$query.= " $name AS $alias, ";
			}
		}
		$query = substr($query, 0, -2);
		$query.= "\r\nSET " . $this->parseSet($values);
		if($where = $this->parseSearch($this->where, $values)) {
			$query.= "\r\nWHERE $where";
		}
		return $query;
	}

	/**
	 * @param $values
	 * @return string
	 */
	public function parseSet(&$values)
	{
		$set = array();
		foreach($this->set as $column => $value) {
			if(ctype_digit($column)) {
				$set[] = $value;
			} else {
				$key = ':c_' . self::$count;
				$set[] = "$column = $key";
				$values[$key] = $value;
				$this->setDataType($key, $this->columnType($column));
				++self::$count;
			}
		}
		return implode(",\r\n", $set);
	}
}
