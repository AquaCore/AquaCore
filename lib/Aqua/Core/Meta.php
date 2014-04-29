<?php
namespace Aqua\Core;

class Meta
implements \ArrayAccess,
           \Iterator
{
	protected $_table;
	protected $_id;
	public $metaLoaded = false;
	public $meta = array();

	public function __construct($tbl, $id)
	{
		$this->_table = $tbl;
		$this->_id    = $id;
	}

	public function offsetExists($key)
	{
		return $this->exists($key);
	}

	public function offsetGet($key)
	{
		$this->metaLoaded or $this->loadMeta();
		return $this->meta[$key];
	}

	public function offsetSet($key, $value)
	{
		$this->set($key, $value);
	}

	public function offsetUnset($key)
	{
		$this->delete($key);
	}

	public function current()
	{
		$this->metaLoaded or $this->loadMeta();
		return current($this->meta);
	}

	public function next()
	{
		$this->metaLoaded or $this->loadMeta();
		next($this->meta);
	}

	public function key()
	{
		$this->metaLoaded or $this->loadMeta();
		return key($this->meta);
	}

	public function valid()
	{
		$this->metaLoaded or $this->loadMeta();
		return $this->exists(key($this->meta));
	}

	public function rewind()
	{
		$this->metaLoaded or $this->loadMeta();
		return reset($this->meta);
	}

	public function exists($key)
	{
		$this->metaLoaded or $this->loadMeta();
		return ($key && array_key_exists($key, $this->meta));
	}

	public function get($key, $default = null)
	{
		$this->metaLoaded or $this->loadMeta();
		return ($this->exists($key) ? $this->meta[$key] : $default);
	}

	public function getArray($key)
	{
		$this->metaLoaded or $this->loadMeta();
		$default = null;
		if(is_array($key)) {
			$keys = $key;
			if(func_num_args() > 1) {
				$default = func_get_arg(1);
			}
		} else {
			$keys = func_get_args();
		}
		$root = &$this->meta;
		foreach($keys as $key) {
			if(!is_array($root) || !array_key_exists($key, $root)) {
				return $default;
			} else {
				$root = &$root[$key];
			}
		}
		return $root;
	}

	public function set($keys, $value = null)
	{
		if(!is_array($keys)) {
			$keys = array( $keys => $value );
		}
		if($this->_id === null) {
			$this->meta = array_merge($this->meta, $keys);
		} else {
			$sth = App::connection()->prepare(sprintf('
			REPLACE INTO `%s` (_id, _key, _val, _type)
			VALUES (:id, :key, :val, :type)
			', $this->_table));
			foreach($keys as $key => $val) {
				$type = $this->getType($value);
				$sth->bindValue(':id', $this->_id, \PDO::PARAM_INT);
				$sth->bindValue(':key', $key, \PDO::PARAM_STR);
				$sth->bindValue(':type', $type, \PDO::PARAM_STR);
				if($type === 'X') {
					$sth->bindValue(':val', serialize($val), \PDO::PARAM_STR);
				} else if($type === 'B') {
					$sth->bindValue(':val', $val ? '1' : '0', \PDO::PARAM_STR);
				} else {
					$sth->bindValue(':val', $val, \PDO::PARAM_STR);
				}
				$sth->execute();
				if($this->metaLoaded && $sth->rowCount()) {
					$this->meta[$key] = $val;
				}
				$sth->closeCursor();
			}
		}
		return $this;
	}

	public function delete($keys)
	{
		if(!is_array($keys)) {
			$keys = array( $keys );
		}
		if($this->_id === null) {
			foreach($keys as $key) {
				unset($this->meta[$key]);
			}
		} else {
			$sth = App::connection()->prepare(sprintf('
			DELETE FROM `%s`
			WHERE _id = ?
			AND   _key = ?
			', $this->_table));
			foreach($keys as $key) {
				$sth->bindValue(1, $this->_id, \PDO::PARAM_INT);
				$sth->bindValue(2, $key, \PDO::PARAM_STR);
				$sth->execute();
				if($this->metaLoaded && $sth->rowCount()) {
					unset($this->meta[$key]);
				}
				$sth->closeCursor();
			}
		}

		return $this;
	}

	public function loadMeta()
	{
		if($this->_id !== null) {
			$sth = App::connection()->prepare(sprintf('
			SELECT _key, _val, _type
			FROM `%s`
			WHERE _id = ?
			', $this->_table));
			$sth->bindValue(1, $this->_id, \PDO::PARAM_INT);
			$sth->execute();
			foreach($sth->fetchAll(\PDO::FETCH_NUM) as $data) {
				switch($data[2]) {
					case 'I': $data[1] = intval($data[1]); break;
					case 'F': $data[1] = floatval($data[1]); break;
					case 'B': $data[1] = boolval($data[1]); break;
					case 'X': $data[1] = unserialize($data[1]); break;
				}
				$this->meta[$data[0]] = $data[1];
			}
		}
		$this->metaLoaded = true;
	}

	public function getType($data)
	{
		if(is_string($data)) {
			return 'S';
		} else if(is_float($data)) {
			return 'F';
		} else if(is_int($data)) {
			return 'I';
		} else if(is_bool($data)) {
			return 'B';
		} else {
			return 'X';
		}
	}
}
