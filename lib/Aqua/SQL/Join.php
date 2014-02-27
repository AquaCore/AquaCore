<?php
namespace Aqua\SQL;

class Join
{
	/**
	 * @var string
	 */
	public $type;
	/**
	 * @var string
	 */
	public $on;
	/**
	 * @var array
	 */
	public $tables = array();

	/**
	 * @param array $tables
	 * @return \Aqua\SQL\Join
	 */
	public function tables(array $tables)
	{
		$this->tables = $tables;

		return $this;
	}

	/**
	 * @param string $type
	 * @return \Aqua\SQL\Join
	 */
	public function type($type)
	{
		$this->type = $type;

		return $this;
	}

	/**
	 * @param string $on
	 * @return static
	 */
	public function on($on)
	{
		$this->on = $on;

		return $this;
	}

	/**
	 * @param $values
	 * @return string
	 */
	public function buildQuery(&$values)
	{
		$query = '';
		if($this->type) $query .= $this->type . ' ';
		$query .= 'JOIN ';
		if(count($this->tables) === 1) {
			$name  = current($this->tables);
			$alias = key($this->tables);
			if($name instanceof Select) {
				$query .= "(\r\n" . $name->buildQuery($values) . "\r\n) AS `$alias`";
			} else if(is_int($alias)) {
				$query .= $name;
			} else {
				$query .= "$name AS `$alias`";
			}
		} else {
			$query .= '(';
			$i = 0;
			foreach($this->tables as $alias => $name) {
				++$i;
				if($i % 2) {
					if(is_int($alias) && preg_match('/^((INNER|CROSS)|(NATURAL )?(LEFT|RIGHT)( OUTER)?)( JOIN)?$/i', $name)) {
						$query .= ' ' . strtoupper($name) . ' ';
						continue;
					} else {
						$query .= ', ';
					}
				}
				if($name instanceof Select) {
					$query .= "(\r\n" . $name->buildQuery($values) . "\r\n) AS `$alias`";
				} else if(is_int($alias)) {
					$query .= $name;
				} else {
					$query .= "$name AS `$alias`";
				}
			}
			$query .= ')';
		}
		if($this->on) $query .= "\r\nON ({$this->on})";

		return $query;
	}
}
