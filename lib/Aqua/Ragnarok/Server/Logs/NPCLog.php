<?php
namespace Aqua\Ragnarok\Server\Logs;

class NPCLog
{
	/**
	 * @var \Aqua\Ragnarok\Server\CharMap
	 */
	public $charmap;
	/**
	 * @var int
	 */
	public $id;
	/**
	 * @var int
	 */
	public $date;
	/**
	 * @var int
	 */
	public $accountId;
	/**
	 * @var int
	 */
	public $charId;
	/**
	 * @var string
	 */
	public $charName;
	/**
	 * @var string
	 */
	public $map;
	/**
	 * @var string
	 */
	public $message;

	public function date($format)
	{
		return strftime($format, $this->date);
	}
}
