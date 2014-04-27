<?php
namespace Aqua\SQL;

abstract class AbstractSearch
extends AbstractQuery
{
	const SEARCH_NATURAL   = 0;
	const SEARCH_EXACT     = 1;
	const SEARCH_LIKE      = 2;
	const SEARCH_IN        = 3;
	const SEARCH_HIGHER    = 4;
	const SEARCH_LOWER     = 5;
	const SEARCH_BETWEEN   = 6;
	const SEARCH_NOT       = 7;
	const SEARCH_AND       = 8;
	const SEARCH_OR        = 9;
	const SEARCH_XOR       = 10;
	const SEARCH_DIFFERENT = 32;

	/**
	 * @param mixed $options
	 * @param       $values
	 * @return string|null
	 */
	public function parseSearch(&$options, &$values)
	{
		$query = '';
		$i     = 0;
		if(!is_array($options)) {
			return null;
		} else {
			foreach($options as $column => &$value) {
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
				if(is_int($column)) {
					if(is_string($value)) {
						$query .= "$value ";
						++$i;
					} else if($q = $this->parseSearch($value, $values)) {
						$query .=  "$q ";
						++$i;
					}
				} else {
					if(!is_array($value)) {
						$value = array( self::SEARCH_NATURAL, $value );
					}
					if($this->parseSearchFlags($value, $w, $values, $column)) {
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

	/**
	 * @param array       $options
	 * @param             $where
	 * @param             $values
	 * @param string      $column
	 * @param string|null $alias
	 * @return bool
	 */
	public function parseSearchFlags(array $options, &$where, &$values, $column, $alias = null)
	{
		$key = ':c_' . self::$count;
		$not = $options[0] & self::SEARCH_DIFFERENT;
		$options[0] ^= $not;
		$dataType = $this->columnType($alias ? : $column);
		switch($options[0]) {
			case self::SEARCH_NATURAL:
				if($options[1] === null) {
					$where = ($not ? "$column IS NOT NULL" : "$column IS NULL");
				} else {
					$where        = ($not ? "$column != $key" : "$column = $key");
					$values[$key] = $options[1];
				}
				$this->setDataType($key, $dataType);
				break;
			case self::SEARCH_EXACT:
				$where        = ($not ? "BINARY $column != $key" : "BINARY $column = $key");
				$values[$key] = $options[1];
				$this->setDataType($key, $dataType);
				break;
			case self::SEARCH_LIKE:
				$where = ($not ? "$column NOT LIKE $key" : "$column LIKE $key");
				if(isset($options[2])) {
					$where .= " ESCAPE '{$options[2]}'";
				}
				$values[$key] = $options[1];
				$this->setDataType($key, $dataType);
				break;
			case self::SEARCH_IN:
				unset($options[0]);
				if($not) {
					$where = "$column NOT IN ";
				} else {
					$where = "$column IN ";
				}
				$where .= '(';
				$i = 0;
				foreach($options as &$value) {
					if($value === null) {
						$where .= 'NULL, ';
					} else {
						$k = "{$key}_$i";
						$where .= "$k, ";
						$values[$k] = $value;
						$this->setDataType($k, $dataType);
						++$i;
					}
				}
				$where = substr($where, 0, -2) . ')';
				break;
			case self::SEARCH_HIGHER:
				$where        = ($not ? "$column <= $key" : "$column > $key");
				$values[$key] = $options[1];
				$this->setDataType($key, $dataType);
				break;
			case self::SEARCH_LOWER:
				$where        = ($not ? "$column >= $key" : "$column < $key");
				$values[$key] = $options[1];
				$this->setDataType($key, $dataType);
				break;
			case self::SEARCH_BETWEEN:
				$where               = ($not ? "$column NOT BETWEEN {$key}_0 AND {$key}_1" : "$column BETWEEN {$key}_0 AND {$key}_1");
				$values[$key . '_0'] = $options[1];
				$values[$key . '_1'] = $options[2];
				$this->setDataType($key . '_0', $dataType);
				$this->setDataType($key . '_1', $dataType);
				break;
			case self::SEARCH_NOT:
				if($not) {
					$where = "$column = 0";
				} else {
					$where        = "$column ~ $key";
					$values[$key] = $options[1];
					if(isset($options[2])) {
						$where = "($where) = {$key}_x";
						$values["{$key}_x"] = $options[2];
					}
					$this->setDataType($key, $dataType);
				}
				break;
			case self::SEARCH_AND:
				$where        = ($not ? "NOT ($column & $key)" : "$column & $key");
				$values[$key] = $options[1];
				if(isset($options[2])) {
					$where = "($where) = {$key}_x";
					$values["{$key}_x"] = $options[2];
				}
				$this->setDataType($key, $dataType);
				break;
			case self::SEARCH_OR:
				$where        = ($not ? "NOT ($column | $key)" : "$column | $key");
				$values[$key] = $options[1];
				if(isset($options[2])) {
					$where = "($where) = {$key}_x";
					$values["{$key}_x"] = $options[2];
				}
				$this->setDataType($key, $dataType);
				break;
			case self::SEARCH_XOR:
				$where        = ($not ? "NOT ($column ^ $key)" : "$column ^ $key");
				$values[$key] = $options[1];
				if(isset($options[2])) {
					$where = "($where) = {$key}_x";
					$values["{$key}_x"] = $options[2];
				}
				$this->setDataType($key, $dataType);
				break;
			default:
				return false;
		}
		++self::$count;

		return true;
	}
}
