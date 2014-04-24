<?php
namespace Aqua\Ragnarok\Server\Logs;

class ZenyLog
extends AbstractPickLog
{
	/**
	 * Char/Map server
	 *
	 * @var \Aqua\Ragnarok\Server\CharMap
	 */
	public $charmap;
	/**
	 * Log ID
	 *
	 * @var int
	 */
	public $id;
	/**
	 * Transfer timestamp
	 *
	 * @var int
	 */
	public $date;
	/**
	 * Target character ID
	 *
	 * @var int
	 */
	public $charId;
	/**
	 * Source character or mob ID
	 *
	 * @var int
	 */
	public $srcId;
	/**
	 * Amount of zeny transferred
	 *
	 * @var int
	 */
	public $amount;
	/**
	 * Map where the transfer took place
	 *
	 * @var int
	 */
	public $map;

	public function date($format)
	{
		return strftime($format, $this->date);
	}

	public function type()
	{
		return __('ragnarok-pick-type', $this->type);
	}

	public function target()
	{
		return $this->charmap->character($this->charId);
	}

	public function source()
	{
		switch($this->sourceType()) {
			case self::SOURCE_MOB:
				return $this->charmap->mob($this->srcId);
			case self::SOURCE_ITEM:
				return $this->charmap->item($this->srcId);
			case self::SOURCE_PC:
				return $this->charmap->character($this->srcId);
			default:
				return null;
		}
	}
}
