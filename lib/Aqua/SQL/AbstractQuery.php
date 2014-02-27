<?php
namespace Aqua\SQL;

abstract class AbstractQuery
{
	/**
	 * @var array
	 */
	public $columnTypes = array();
	/**
	 * @var array
	 */
	public $dataTypes = array();
	/**
	 * @var \PDO
	 */
	public $dbh;

	/**
	 * @var int
	 */
	public static $count = 0;
	/**
	 * Data type to PDO parameter type map
	 *
	 * @var array
	 */
	protected static $dataTypeMap = array(
		'string'    => \PDO::PARAM_STR,
		'float'     => \PDO::PARAM_STR,
		'blob'      => \PDO::PARAM_LOB,
		'null'      => \PDO::PARAM_NULL,
		'integer'   => \PDO::PARAM_INT,
		'timestamp' => \PDO::PARAM_STR,
		'set'       => \PDO::PARAM_STR
	);

	/**
	 * @param \PDO $dbh
	 */
	public function __construct(\PDO $dbh)
	{
		$this->dbh = $dbh;
	}

	abstract public function query($values = array());

	abstract public function buildQuery(&$values);

	/**
	 * @param string|array $value
	 * @param string|null  $type
	 * @return static
	 */
	public function setDataType($value, $type = null)
	{
		if(!is_array($value)) {
			$value = array( $value => $type );
		}
		$this->dataTypes = array_merge($this->dataTypes, $value);

		return $this;
	}

	/**
	 * @param array $column
	 * @param bool  $merge
	 * @return static
	 */
	public function setColumnType(array $column, $merge = false)
	{
		if($merge) {
			$this->columnTypes = array_merge($this->columnTypes, $column);
		} else {
			$this->columnTypes = $column;
		}

		return $this;
	}

	/**
	 * @param string $value
	 * @param bool   $pdo
	 * @return string|int|null
	 */
	public function columnType($value, $pdo = false)
	{
		if(!isset($this->columnTypes[$value])) {
			return null;
		} else {
			if($pdo) {
				return static::$dataTypeMap[$this->columnTypes[$value]];
			} else {
				return $this->columnTypes[$value];
			}
		}
	}

	/**
	 * @param string $value
	 * @param bool   $pdo
	 * @return string|int|null
	 */
	public function dataType($value, $pdo = false)
	{
		if(!isset($this->dataTypes[$value])) {
			return null;
		} else {
			if($pdo) {
				return static::$dataTypeMap[$this->dataTypes[$value]];
			} else {
				return $this->dataTypes[$value];
			}
		}
	}

	/**
	 * @param bool  $extended
	 * @param array $values
	 * @return array
	 */
	public function explain($extended = false, $values = array())
	{
		$query = "EXPLAIN ";
		if($extended) {
			$query .= "EXTENDED ";
		}
		$query .= "(\r\n" . $this->buildQuery($values) . "\r\n)";
		$sth = $this->dbh->prepare($query);
		foreach($values as $key => $value) $sth->bindValue($key, $value);
		$sth->execute();
		$result = $sth->fetchAll(\PDO::FETCH_ASSOC);
		$sth->closeCursor();

		return $result;
	}
}
