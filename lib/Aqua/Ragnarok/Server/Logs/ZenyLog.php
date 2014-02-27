<?php
namespace Aqua\Ragnarok\Server\Logs;

class ZenyLog
{
	public $id;
	public $date;
	public $charId;
	public $srcId;
	public $type;
	public $amount;
	public $map;

	const TYPE_MONSTER      = 1;
	const TYPE_TRADE        = 2;
	const TYPE_VENDING      = 3;
	const TYPE_SHOP         = 4;
	const TYPE_NPC          = 5;
	const TYPE_ADMIN        = 6;
	const TYPE_EMAIL        = 7;
	const TYPE_BUYING_STORE = 8;

}