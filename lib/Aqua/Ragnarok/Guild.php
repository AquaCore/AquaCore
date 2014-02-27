<?php
namespace Aqua\Ragnarok;

class Guild
{
	/**
	 * @var int
	 */
	public $id;

	/**
	 * @var \Aqua\Ragnarok\Server\CharMap
	 */
	public $charmap;

	/**
	 * @var string
	 */
	public $name;

	/**
	 * @var int
	 */
	public $level;

	/**
	 * @var int
	 */
	public $experience;

	/**
	 * @var int
	 */
	public $averageLevel;

	/**
	 * @var int
	 */
	public $leaderId;

	/**
	 * @var string
	 */
	public $leaderName;

	/**
	 * @var int
	 */
	public $memberLimit;

	/**
	 * @var int
	 */
	public $memberCount;

	/**
	 * @var int
	 */
	public $castleCount;

	/**
	 * @var \Aqua\Ragnarok\Character[]
	 */
	protected $_members = array();

	public function emblem()
	{
		return ac_guild_emblem($this->charmap->server->key, $this->charmap->key, $this->id);
	}

	public function members($start = null, $limit = null)
	{

	}

	public function positions()
	{

	}

	public function castles()
	{

	}

	public function alliance()
	{

	}

	public function skills()
	{

	}
}
