<?php
namespace Aqua\Ragnarok\Server\Logs;

class PickLog
{
	public $id;
	public $date;
	public $charId;
	public $amount;
	public $type;
	public $uniqueId;
	public $itemId;
	public $cards = array();
	public $refine;
	public $map;

	const TYPE_MONSTER      = 0;
	const TYPE_PLAYER       = 1;
	const TYPE_LOOT         = 2;
	const TYPE_TRADE        = 3;
	const TYPE_VENDING      = 4;
	const TYPE_SHOP         = 5;
	const TYPE_STORAGE      = 6;
	const TYPE_GSTORAGE     = 7;
	const TYPE_MAIL         = 8;
	const TYPE_BUYING_STORE = 9;
	const TYPE_PRODUCE      = 10;
	const TYPE_AUCTION      = 11;
	const TYPE_OTHER        = 12;
	const TYPE_STEAL        = 13;
	const TYPE_PRIZE        = 14;

	public function type()
	{
		return __('ragnarok-pick-log-type', $this->type);
	}
}
