<?php
namespace Aqua\SQL;

class CreateTable
extends AbstractQuery
{
	/**
	 * @var array
	 */
	public $columns = array();
	/**
	 * @var array
	 */
	public $indexes = array();
	/**
	 * @var \Aqua\SQL\Select|null
	 */
	public $select;
	/**
	 * @var \Aqua\SQL\Select|null
	 */
	public $insert;
	/**
	 * @var string|null
	 */
	public $insertMethod;
	/**
	 * @var string|null
	 */
	public $like;
	/**
	 * @var string
	 */
	public $tableName;
	/**
	 * @var bool
	 */
	public $temporary = false;
	/**
	 * @var bool
	 */
	public $dropIfExists = false;
	/**
	 * @var bool
	 */
	public $ifNotExists = false;
	/**
	 * @var string|null
	 */
	public $engine;
	/**
	 * @var int|null
	 */
	public $autoIncrement;
	/**
	 * @var int|null
	 */
	public $averageRowLength;
	/**
	 * @var string|null
	 */
	public $charset;
	/**
	 * @var string|null
	 */
	public $collation;
	/**
	 * @var string|null
	 */
	public $rowFormat;
	/**
	 * @var bool|null
	 */
	public $checksum;
	/**
	 * @var bool|null
	 */
	public $delayKeyWrite;

	/**
	 * @param \PDO   $dbh
	 * @param string $name
	 */
	public function __construct(\PDO $dbh, $name)
	{
		$this->dbh       = $dbh;
		$this->tableName = $name;
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
	 * @param array $indexes
	 * @param bool  $merge
	 * @return static
	 */
	public function indexes(array $indexes, $merge = true)
	{
		if($merge) {
			$this->indexes = array_merge($this->indexes, $indexes);
		} else {
			$this->indexes = $indexes;
		}

		return $this;
	}

	/**
	 * @param Select      $query
	 * @param string|null $method
	 * @return static
	 */
	public function insert(Select $query, $method = null)
	{
		$this->insert       = $query;
		$this->insertMethod = $method;

		return $this;
	}

	/**
	 * @param bool $value
	 * @return static
	 */
	public function temporary($value = true)
	{
		$this->temporary = (bool)$value;

		return $this;
	}

	/**
	 * @param bool $value
	 * @return static
	 */
	public function dropIfExists($value = true)
	{
		$this->dropIfExists = (bool)$value;

		return $this;
	}

	/**
	 * @param bool $value
	 * @return static
	 */
	public function ifNotExists($value = true)
	{
		$this->ifNotExists = (bool)$value;

		return $this;
	}

	/**
	 * @param $engine
	 * @return static
	 */
	public function engine($engine)
	{
		$this->engine = strtoupper($engine);

		return $this;
	}

	/**
	 * @param int $i
	 * @return static
	 */
	public function autoIncrement($i)
	{
		$this->autoIncrement = ($i === null ? null : intval($i));

		return $this;
	}

	/**
	 * @param int $i
	 * @return static
	 */
	public function averageRowLength($i)
	{
		$this->averageRowLength = ($i === null ? null : intval($i));

		return $this;
	}

	/**
	 * @param string $charset
	 * @return static
	 */
	public function charset($charset)
	{
		$this->charset = $charset;

		return $this;
	}

	/**
	 * @param string $collation
	 * @return static
	 */
	public function collation($collation)
	{
		$this->collation = $collation;

		return $this;
	}

	/**
	 * @param string $format
	 * @return static
	 */
	public function rowFormat($format)
	{
		$this->rowFormat = $format;

		return $this;
	}

	/**
	 * @param int $val
	 * @return static
	 */
	public function checksum($val = 1)
	{
		$this->checksum = ($val === null ? null : (bool)$val);

		return $this;
	}

	/**
	 * @param int $val
	 * @return static
	 */
	public function delayKeyWrite($val = 1)
	{
		$this->delayKeyWrite = ($val === null ? null : (bool)$val);

		return $this;
	}

	/**
	 * @param array $values
	 * @return static
	 */
	public function query($values = array())
	{
		$sth = $this->dbh->prepare($this->buildQuery($values));
		foreach($values as $key => $value) $sth->bindValue($key, $value, $this->dataType($key));
		$sth->execute();

		return $this;
	}

	/**
	 * @param $values
	 * @return string
	 */
	public function buildQuery(&$values)
	{
		$query = '';
		if($this->dropIfExists) {
			$query .= 'DROP ';
			if($this->temporary) {
				$query .= ' TEMPORARY';
			}
			$query .= " TABLE IF EXISTS {$this->tableName};\r\n";
		}
		$query .= 'CREATE';
		if($this->temporary) $query .= ' TEMPORARY';
		$query .= ' TABLE';
		if($this->ifNotExists) $query .= ' IF NOT EXISTS';
		$query .= " {$this->tableName}";
		if($this->like) {
			$query .= "( LIKE `{$this->like}` )\r\n";
		} else if(!empty($this->columns) || !empty($this->indexes)) {
			$query .= "(\r\n";
			$columns = array();
			foreach($this->columns as $name => $def) {
				$columns[] = $this->parseColumn($name, $def);
			}
			foreach($this->indexes as $name => $def) {
				if($index = $this->parseIndex($name, $def)) {
					$columns[] = $index;
				}
			}
			$query .= implode(",\r\n", $columns) . "\r\n";
			$query .= ")\r\n";
		}
		if($this->engine) $query .= "ENGINE = {$this->engine}\r\n";
		if($this->autoIncrement !== null) $query .= "AUTO_INCREMENT = {$this->autoIncrement}\r\n";
		if($this->averageRowLength !== null) $query .= "AVG_ROW_LENGTH = {$this->autoIncrement}\r\n";
		if($this->rowFormat) $query .= "ROW_FORMAT = {$this->rowFormat}\r\n";
		if($this->charset) $query .= "DEFAULT CHARACTER SET = {$this->charset}\r\n";
		if($this->collation) $query .= "COLLATE = {$this->collation}\r\n";
		if($this->checksum !== null) $query .= 'CHECKSUM = ' . intval($this->checksum) . "\r\n";
		if($this->delayKeyWrite !== null) $query .= 'DELAY_KEY_WRITE = ' . intval($this->delayKeyWrite) . "\r\n";
		if($this->insert) {
			if($this->insertMethod) $query .= $this->insertMethod . ' ';
			$query .= $this->insert->buildQuery($values);
		}
		$query .= ';';

		return $query;
	}

	/**
	 * @param string $name
	 * @param array  $def
	 * @return bool|string
	 */
	public function parseColumn($name, array $def)
	{
		$def += array(
			'type'          => 'INT',
			'length'        => null,
			'precision'     => null,
			'unsigned'      => false,
			'zeroFill'      => false,
			'charset'       => null,
			'collation'     => null,
			'null'          => true,
			'autoIncrement' => false,
			'primary'       => false,
			'unique'        => false,
			'format'        => null,
			'storage'       => null,
			'reference'     => null
		);
		$def['type'] = strtoupper($def['type']);
		$column      = "`$name` {$def['type']}";
		switch($def['type']) {
			case 'VARCHAR':
			case 'CHAR':
			case 'VARBINARY':
			case 'BINARY':
				if(!$def['length']) return false;
				$column .= "({$def['length']})";
				break;
			case 'DECIMAL':
			case 'NUMBER':
			case 'FLOAT':
			case 'DOUBLE':
				if(is_null($def['length']) || is_null($def['precision'])) return false;
				if($def['type'] === 'DOUBLE') $column .= ' PRECISION';
				$column .= "({$def['length']}, {$def['precision']})";
				break;
			case 'ENUM':
			case 'SET':
				if(empty($def['values'])) return false;
				$column .= '(\'' . implode('\', \'', $def['values']) . '\')';
				break;
		}
		if($def['unsigned']) $column .= ' UNSIGNED';
		if($def['zeroFill']) $column .= ' ZEROFILL';
		if($def['charset']) $column .= ' CHARACTER SET ' . $def['charset'];
		if($def['collation']) $column .= ' COLLATE ' . $def['collate'];
		if(!$def['null']) $column .= ' NOT NULL';
		if(array_key_exists('default', $def)) {
			switch($def['default']) {
				case 'CURRENT_TIMESTAMP':
					$column .= " DEFAULT {$def['default']}";
					break;
				case null:
					$column .= ' DEFAULT NULL';
					break;
				default:
					$column .= " DEFAULT '{$def['default']}'";
					break;
			}
		}
		if($def['autoIncrement']) $column .= ' AUTO_INCREMENT';
		if($def['primary']) $column .= ' PRIMARY KEY';
		else if($def['unique']) $column .= ' UNIQUE KEY';
		if($def['format']) $column .= " COLUMN_FORMAT = {$def['format']}";
		if($def['storage']) $column .= " STORAGE = {$def['storage']}";
		if(is_array($def['reference']) && ($fk = $this->parseForeignKey($def['reference']))) {
			$column .= " $fk";
		}

		return $column;
	}

	/**
	 * @param string $name
	 * @param array  $def
	 * @return bool|string
	 */
	public function parseIndex($name, array $def)
	{
		$def += array(
			'type'         => null,
			'using'        => null,
			'keyBlockSize' => null,
			'columns'      => array()
		);
		if(empty($def['columns'])) return false;
		switch(strtolower($def['type'])) {
			case 'primary':
			case 'primary key':
				$index = "CONSTRAINT `$name` PRIMARY KEY";
				break;
			case 'foreign':
			case 'foreign key':
				if($fk = $this->parseForeignKey($def)) {
					return "CONSTRAINT `$name` FOREIGN KEY `$name` $fk";
				} else {
					return false;
				}
			case 'unique':
				$index = "CONSTRAINT `$name` UNIQUE";
				break;
			case 'spatial':
				$index = 'SPATIAL';
				break;
			case 'fulltext':
				$index = 'FULLTEXT';
				break;
			case null:
				$index = 'INDEX';
				break;
			default:
				return false;
		}
		$index .= " `$name` ( `" . implode('`, `', $def['columns']) . '` )';
		if($def['keyBlockSize']) $index .= " KEY_BLOCK_SIZE = {$def['keyBlockSize']}";
		if($def['using']) $index .= " USING {$def['using']}";

		return $index;
	}

	/**
	 * @param array $def
	 * @return bool|string
	 */
	public function parseForeignKey(array $def)
	{
		if(!isset($def['columns']) || !isset($def['table'])) return false;
		$fk = "REFERENCES {$def['table']} ";
		$fk .= '( `' . implode('`, `', $def['columns']) . '` )';
		if(isset($def['match'])) $fk .= "MATCH {$def['match']}\r\n";
		if(isset($def['onDelete'])) $fk .= "ON DELETE {$def['onDelete']}\r\n";
		if(isset($def['onUpdate'])) $fk .= "ON UPDATE {$def['onUpdate']}\r\n";

		return $fk;
	}
}
