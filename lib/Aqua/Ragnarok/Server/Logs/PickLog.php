<?php
namespace Aqua\Ragnarok\Server\Logs;

use Aqua\Ragnarok\Item;

class PickLog
extends AbstractPickLog
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
		return __('ragnarok-pick-type', $this->type);
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
		if($this->type === 'M' || $this->type === 'L') {
			return $this->charmap->mob($this->charId);
		} else {
			return $this->charmap->character($this->charId);
		}
	}
}
