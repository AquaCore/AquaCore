<?php
namespace Aqua\Ragnarok\Server\Logs;

class ZenyLog
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
	 * Zeny transfer type
	 *
	 * @var int
	 * @see \Aqua\Ragnarok\Server\Log\ZenyLog::TYPE_MONSTER
	 * @see \Aqua\Ragnarok\Server\Log\ZenyLog::TYPE_TRADE
	 * @see \Aqua\Ragnarok\Server\Log\ZenyLog::TYPE_VENDING
	 * @see \Aqua\Ragnarok\Server\Log\ZenyLog::TYPE_SHOP
	 * @see \Aqua\Ragnarok\Server\Log\ZenyLog::TYPE_NPC
	 * @see \Aqua\Ragnarok\Server\Log\ZenyLog::TYPE_ADMIN
	 * @see \Aqua\Ragnarok\Server\Log\ZenyLog::TYPE_EMAIL
	 * @see \Aqua\Ragnarok\Server\Log\ZenyLog::TYPE_BUYING_STORE
	 */
	public $type;
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

	const TYPE_MONSTER      = 1;
	const TYPE_TRADE        = 2;
	const TYPE_VENDING      = 3;
	const TYPE_SHOP         = 4;
	const TYPE_NPC          = 5;
	const TYPE_ADMIN        = 6;
	const TYPE_EMAIL        = 7;
	const TYPE_BUYING_STORE = 8;

	public function date($format)
	{
		return strftime($format, $this->date);
	}

	public function type()
	{
		return __('ragnarok-zeny-log-type', $this->type);
	}

	public function target()
	{
		return $this->charmap->character($this->charId);
	}

	public function source()
	{
		return $this->charmap->character($this->srcId);
	}
}
