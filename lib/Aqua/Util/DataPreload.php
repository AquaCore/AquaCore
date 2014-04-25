<?php
namespace Aqua\Util;

use Aqua\Core\Exception\InvalidArgumentException;
use Aqua\SQL\Select;

class DataPreload
{
	public $search;
	public $db;
	public $id;
	public $from = array();

	public function __construct($search, array &$db = null, $id = 'id')
	{
		if($search instanceof Select || is_callable($search) ||
		   (is_string($search) && is_subclass_of($search, 'Aqua\\SQL\\Select'))) {
			$this->search = $search;
		} else {
			throw new InvalidArgumentException(1, array( 'Aqua\\SQL\\Select', 'callable'), $search);
		}
		if($db !== null) {
			$this->db = &$db;
		}
		$this->id = $id;
	}

	public function add(Select $search, array $columns)
	{
		$this->from[spl_object_hash($search)] = array( $search, $columns );
		return $this;
	}

	public function run(array $data = array())
	{
		foreach($this->from as $from) {
			foreach($from[1] as $column) {
				$data = array_merge($data, $from[0]->getColumn($column));
			}
		}
		$data = array_filter(array_unique($data));
		if(empty($data)) {
			return;
		}
		if($this->db !== null) foreach($data as $id) {
			if(!array_key_exists($id, $this->db)) {
				$this->db[$id] = null;
			}
		}
		array_unshift($data, Select::SEARCH_IN);
		if(is_object($this->search)) {
			$search = $this->search;
		} else if(is_callable($this->search)) {
			$search = call_user_func($this->search);
		} else {
			$search = new $this->search;
		}
		$search->where(array( $this->id => $data ))->query();
	}
}
