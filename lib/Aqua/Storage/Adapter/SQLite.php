<?php
namespace Aqua\Storage\Adapter;

use Aqua\Storage\Exception\StorageException;
use Aqua\Storage\FlushPrefixStorageInterface;
use Aqua\Storage\FlushableStorageInterface;
use Aqua\Storage\GCStorageInterface;
use Aqua\Storage\OptimizableStorageInterface;
use Aqua\Storage\StorageInterface;

class SQLite
implements StorageInterface,
           FlushableStorageInterface,
           FlushPrefixStorageInterface,
           OptimizableStorageInterface,
           GCStorageInterface
{
	/**
	 * @var \PDO
	 */
	public $dbh;
	/**
	 * @var string
	 */
	public $file = ':memory:';
	/**
	 * @var string
	 */
	public $table = 'ac_storage';
	/**
	 * @var int
	 */
	public $gcProbability = 0;
	/**
	 * @var int
	 */
	public $serializer = self::SERIALIZER_PHP;
	/**
	 * @var string
	 */
	public $encoding = 'UTF-8';
	/**
	 * @var int
	 */
	public $autoVacuum;
	/**
	 * @var array
	 */
	public $options = array(
		\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
	);

	const SERIALIZER_NONE     = 0;
	const SERIALIZER_PHP      = 1;
	const SERIALIZER_JSON     = 2;
	const SERIALIZER_IGBINARY = 3;

	/**
	 * @param array $options
	 * @throws \Aqua\Storage\Exception\StorageException
	 */
	public function __construct(array $options = array())
	{
		if(!extension_loaded('pdo_sqlite')) {
			throw new StorageException(
				__('exception', 'missing-extension', __CLASS__, 'pdo_sqlite'),
				StorageException::MISSING_EXTENSION
			);
		}
		foreach($options as $opt => $value) {
			$this->setOption($opt, $value);
		}
		$this->dbh = new \PDO("sqlite:{$this->file}", null, null, $this->options);
		$this->dbh->exec("
		CREATE TABLE IF NOT EXISTS {$this->table} (
			id    TEXT PRIMARY KEY,
			value BLOB,
			ttl   INTEGER,
			type  INTEGER
		);
		");
		if($this->autoVacuum !== null) $this->dbh->exec("PRAGMA auto_vacuum = {$this->autoVacuum}");
		if($this->encoding) $this->dbh->exec("PRAGMA encoding = '{$this->encoding}'");
		if(ac_probability($this->gcProbability)) {
			$this->gc();
		}
	}

	/**
	 * @param string  $option
	 * @param mixed   $value
	 * @return bool
	 * @throws \Aqua\Storage\Exception\StorageException
	 */
	public function setOption($option, $value = true)
	{
		switch($option) {
			case 'file':
				$this->file = $value;
				break;
			case 'table':
				$this->table = $value;
				break;
			case 'persistent':
				$this->options[\PDO::ATTR_PERSISTENT] = (bool)$value;
				break;
			case 'gc_probability':
				$this->gcProbability = $value;
				break;
			case 'encoding':
				$value = strtoupper($value);
				switch($value) {
					case 'UTF-8':
					case 'UTF-16':
						break;
					case 'UTF-16LE':
						$value = 'UTF-16le';
						break;
					case 'UTF-16BE':
						$value = 'UTF-16be';
						break;
					default:
						return false;
				}
				$this->encoding = $value;
				break;
			case 'auto_vacuum':
				$value = strtoupper((string)$value);
				switch($value) {
					case 'NONE':
					case '0':
						$value = 0;
						break;
					case 'FULL':
					case '1':
						$value = 1;
						break;
					case 'INCREMENTAL':
					case '2':
						$value = 2;
						break;
					default:
						return false;
				}
				$this->autoVacuum = $value;
				break;
			case 'serializer':
				switch($value) {
					default:
						$value = self::SERIALIZER_NONE;
					case self::SERIALIZER_NONE:
					case self::SERIALIZER_PHP:
					case self::SERIALIZER_JSON:
						break;
					case self::SERIALIZER_IGBINARY:
						if($value === self::SERIALIZER_IGBINARY && !extension_loaded('igbinary')) {
							throw new StorageException(
								__('exception', 'missing-extension', __CLASS__, 'igbinary'),
								StorageException::MISSING_EXTENSION
							);
						}
						break;
				}
				$this->serializer = $value;
				break;
		}
		return true;
	}

	/**
	 * @param string $opt
	 * @return mixed
	 */
	public function getOption($opt)
	{
		switch($opt) {
			case 'file':
				return $this->file;
			case 'table':
				return $this->table;
			case 'persistent':
				return (isset($this->options[\PDO::ATTR_PERSISTENT]) &&
				        $this->options[\PDO::ATTR_PERSISTENT]);
			case 'gc_probability':
				return $this->gcProbability;
			case 'encoding':
				return $this->encoding;
			case 'auto_vacuum':
				return $this->autoVacuum;
			case 'serializer':
				return $this->serializer;
			default:
				return null;
		}
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function exists($key)
	{
		$sth = $this->dbh->prepare("
		SELECT COUNT(1)
		FROM `{$this->table}`
		WHERE id = ?
		AND ttl < ?
		");
		$sth->bindValue(1, $key, \PDO::PARAM_STR);
		$sth->bindValue(2, time(), \PDO::PARAM_INT);
		$sth->execute();

		return (bool)$sth->fetchColumn(0);
	}

	/**
	 * @param string  $key
	 * @param mixed   $default
	 * @return mixed
	 */
	public function fetch($key, $default = null)
	{
		$sth = $this->dbh->prepare("
		SELECT value, type
		FROM `{$this->table}`
		WHERE id = ?
		AND (ttl = 0 OR ttl < ?)
		LIMIT 1
		");
		$sth->bindValue(1, $key, \PDO::PARAM_STR);
		$sth->bindValue(2, time(), \PDO::PARAM_INT);
		$sth->execute();
		if($data = $sth->fetch(\PDO::FETCH_NUM)) {
			switch($data[1]) {
				case 'i':
					$value = intval($data[0]);
					break;
				case 'f':
					$value = floatval($data[0]);
					break;
				case 'b':
					$value = boolval($data[0]);
					break;
				case 'x':
					$value = $this->_unserialize($data[0]);
					break;
				default:
					$value = $data[0];
			}
			return $value;
		}
		else {
			return $default;
		}
	}

	public function add($key, $value, $ttl = 0)
	{
		$type = $this->_getType($value);
		if($type === 'x') $value = $this->_serialize($value);
		$sth = $this->dbh->prepare("
		INSERT OR IGNORE INTO {$this->table} ( id, value, ttl, type )
		VALUES ( ?, ?, ?, ? )
		");
		$sth->bindValue(1, $key, \PDO::PARAM_STR);
		$sth->bindValue(2, $value, \PDO::PARAM_LOB);
		$sth->bindValue(3, abs($ttl), \PDO::PARAM_INT);
		$sth->bindValue(4, $type, \PDO::PARAM_STR);
		$sth->execute();

		return (bool)$sth->rowCount();
	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 * @param int    $ttl
	 * @return bool
	 */
	public function store($key, $value, $ttl = 0)
	{
		$type = $this->_getType($value);
		if($type === 'x') {
			$value = $this->_serialize($value);
		}
		$sth = $this->dbh->prepare("
		INSERT OR REPLACE INTO `{$this->table}` ( id, value, ttl, type )
		VALUES ( ?, ?, ?, ? )
		");
		$sth->bindValue(1, $key, \PDO::PARAM_STR);
		$sth->bindValue(2, $value, \PDO::PARAM_LOB);
		$sth->bindValue(3, abs($ttl), \PDO::PARAM_INT);
		$sth->bindValue(4, $type, \PDO::PARAM_STR);
		$sth->execute();

		return (bool)$sth->rowCount();
	}

	/**
	 * @param string    $key
	 * @param int|float $step
	 * @param int|float $default
	 * @param int       $ttl
	 * @return bool|int|float
	 */
	public function increment($key, $step = 1, $default = 0, $ttl = 0)
	{
		if(!$this->exists($key)) {
			$value = $default + $step;

			return ($this->store($key, $value, $ttl) ? $value : false);
		}
		$query = "
		UPDATE `{$this->table}`
		SET value = value + ?
		";
		if(is_float($step)) {
			$query .= ", type = 'f'";
		}
		$query .= "
		WHERE id = ?
		AND (type = 'i' OR type = 'f')
		AND ttl < ?
		";
		$sth = $this->dbh->prepare($query);
		$sth->bindValue(1, $step);
		$sth->bindValue(2, $key, \PDO::PARAM_STR);
		$sth->bindValue(3, time(), \PDO::PARAM_INT);
		$sth->execute();
		if($sth->rowCount()) {
			return $this->fetch($key);
		}
		else {
			return false;
		}
	}

	/**
	 * @param string    $key
	 * @param int|float $step
	 * @param int|float $default
	 * @param int       $ttl
	 * @return bool|int|float
	 */
	public function decrement($key, $step = 1, $default = 0, $ttl = 0)
	{
		if(!$this->exists($key)) {
			$value = $default - $step;

			return ($this->store($key, $value, $ttl) ? $value : false);
		}
		$query = "
		UPDATE `{$this->table}`
		SET value = value - ?
		";
		if(is_float($step)) {
			$query .= ", type = 'f'";
		}
		$query .= "
		WHERE id = ?
		AND (type = 'i' OR type = 'f')
		AND ttl < ?
		";
		$sth = $this->dbh->prepare($query);
		$sth->bindValue(1, $step);
		$sth->bindValue(2, $key, \PDO::PARAM_STR);
		$sth->bindValue(3, time(), \PDO::PARAM_INT);
		$sth->execute();
		if($sth->rowCount()) {
			return $this->fetch($key);
		}
		else {
			return false;
		}
	}

	/**
	 * @param string $key
	 * @return array|bool
	 */
	public function delete($key)
	{
		if(!is_array($key)) $key = array( $key );
		$sth     = $this->dbh->prepare("
		DELETE FROM `{$this->table}`
		WHERE id = ?
		");
		$deleted = array();
		$count   = 0;
		foreach($key as $k) {
			$sth->bindValue(1, $k, \PDO::PARAM_STR);
			$sth->execute();
			if((bool)$sth->rowCount()) {
				$deleted[] = $k;
				++$count;
			}
		}

		return ($count === 0 ? false : $count === 1 ? true : $deleted);
	}

	/**
	 * @return bool
	 */
	public function flush()
	{
		return (bool)$this->dbh->exec("DELETE FROM `{$this->table}`");
	}

	/**
	 * @param string $prefix
	 * @return bool
	 */
	public function flushPrefix($prefix)
	{
		$prefix = addcslashes($prefix, '\\%_') . '%';
		$sth    = $this->dbh->prepare("DELETE FROM `{$this->table}` WHERE id LIKE ? ESCAPE '\\'");
		$sth->bindValue(1, $prefix, SQLITE3_TEXT);
		$sth->execute();

		return (bool)$sth->rowCount();
	}

	public function optimize()
	{
		$this->dbh->exec("VACUUM `{$this->table}`");
	}

	public function gc()
	{
		$sth = $this->dbh->prepare("
		DELETE FROM `{$this->table}`
		WHERE ttl <= ?
		");
		$sth->bindValue(1, time(), \PDO::PARAM_INT);
		$sth->execute();
	}

	/**
	 * @param string $data
	 * @return string
	 */
	protected function _serialize($data)
	{
		switch($this->serializer) {
			default:
			case self::SERIALIZER_NONE:
				return (string)$data;
			case self::SERIALIZER_PHP:
				return serialize($data);
			case self::SERIALIZER_JSON:
				return json_encode($data);
			case self::SERIALIZER_IGBINARY:
				return igbinary_serialize($data);
		}
	}

	/**
	 * @param string $data
	 * @return mixed
	 */
	protected function _unserialize($data)
	{
		switch($this->serializer) {
			default:
			case self::SERIALIZER_NONE:
				return $data;
			case self::SERIALIZER_PHP:
				return unserialize($data);
			case self::SERIALIZER_JSON:
				return json_decode($data, true);
			case self::SERIALIZER_IGBINARY:
				return igbinary_unserialize($data);
		}
	}

	/**
	 * @param mixed $value
	 * @return string
	 */
	protected function _getType($value)
	{
		if(is_int($value)) return 'i';
		if(is_float($value)) return 'f';
		if(is_bool($value)) return 'b';
		if(is_string($value)) return 's';

		return 'x';
	}
}
