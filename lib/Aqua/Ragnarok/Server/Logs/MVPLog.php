<?php
namespace Aqua\Ragnarok\Server\Logs;

class MVPLog
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
	public $charId;
	/**
	 * @var int
	 */
	public $mobId;
	/**
	 * @var int
	 */
	public $prize;
	/**
	 * @var int
	 */
	public $experience;
	/**
	 * @var string
	 */
	public $map;

	public function date($format)
	{
		return strftime($format, $this->date);
	}
}
 