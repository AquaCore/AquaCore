<?php
namespace Aqua\Ragnarok\Server\Logs;

use Aqua\Ragnarok\Item;

class PickLog
{
	/**
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
	 * Amount of items picked
	 *
	 * @var int
	 */
	public $amount;
	/**
	 * Transfer type
	 *
	 * @var int
	 * @see \Aqua\Ragnarok\Server\Log\PickLog::TYPE_MONSTER
	 * @see \Aqua\Ragnarok\Server\Log\PickLog::TYPE_PLAYER
	 * @see \Aqua\Ragnarok\Server\Log\PickLog::TYPE_LOOT
	 * @see \Aqua\Ragnarok\Server\Log\PickLog::TYPE_TRADE
	 * @see \Aqua\Ragnarok\Server\Log\PickLog::TYPE_VENDING
	 * @see \Aqua\Ragnarok\Server\Log\PickLog::TYPE_SHOP
	 * @see \Aqua\Ragnarok\Server\Log\PickLog::TYPE_STORAGE
	 * @see \Aqua\Ragnarok\Server\Log\PickLog::TYPE_GSTORAGE
	 * @see \Aqua\Ragnarok\Server\Log\PickLog::TYPE_MAIL
	 * @see \Aqua\Ragnarok\Server\Log\PickLog::TYPE_BUYING_STORE
	 * @see \Aqua\Ragnarok\Server\Log\PickLog::TYPE_PROCUDE
	 * @see \Aqua\Ragnarok\Server\Log\PickLog::TYPE_AUCTION
	 * @see \Aqua\Ragnarok\Server\Log\PickLog::TYPE_OTHER
	 * @see \Aqua\Ragnarok\Server\Log\PickLog::TYPE_STEAL
	 * @see \Aqua\Ragnarok\Server\Log\PickLog::TYPE_PRIZE
	 */
	public $type;
	/**
	 * Item's unique ID, if any
	 *
	 * @var int
	 */
	public $uniqueId;
	/**
	 * Item ID
	 *
	 * @var int
	 */
	public $itemId;
	/**
	 * Cards in the item, in case it's an equipment
	 *
	 * @var array
	 */
	public $cards = array();
	/**
	 * Equipment's refine level
	 *
	 * @var int
	 */
	public $refine;
	/**
	 * Map where the transfer took place
	 *
	 * @var string
	 */
	public $map;
	protected $_item;

	const TYPE_MONSTER      = 1;
	const TYPE_PLAYER       = 2;
	const TYPE_LOOT         = 3;
	const TYPE_TRADE        = 4;
	const TYPE_VENDING      = 5;
	const TYPE_SHOP         = 6;
	const TYPE_STORAGE      = 7;
	const TYPE_GSTORAGE     = 8;
	const TYPE_MAIL         = 9;
	const TYPE_BUYING_STORE = 10;
	const TYPE_PRODUCE      = 11;
	const TYPE_AUCTION      = 12;
	const TYPE_OTHER        = 13;
	const TYPE_STEAL        = 14;
	const TYPE_PRIZE        = 15;

	/**
	 * Format the transfer date.
	 *
	 * @param string $format
	 * @return string
	 */
	public function date($format)
	{
		return strftime($format, $this->date);
	}

	/**
	 * Transfer type string. ("Monster", "Player", "Loot", ...)
	 *
	 * @return string
	 */
	public function type()
	{
		return __('ragnarok-pick-log-type', $this->type);
	}

	public function item()
	{
		if(!$this->_item) {
			$item = $this->charmap->item($this->itemId);
			$this->_item = new Item;
			$this->_item->id        = $this->itemId;
			$this->_item->charmap   = &$this->charmap;
			$this->_item->character = $this->charId;
			$this->_item->amount    = $this->amount;
			$this->_item->cards     = $this->cards;
			$this->_item->refine    = $this->refine;
			$this->_item->uniqueId  = $this->uniqueId;
			if(!$item) {
				$this->_item->name = __('application', 'unknown');
			} else {
				$this->_item->name     = $item->jpName;
				$this->_item->itemType = $item->type;
			}
		}
		return $this->_item;
	}

	public function character()
	{
		return $this->charmap->character($this->charId);
	}
}
