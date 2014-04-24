<?php
namespace Aqua\Ragnarok\Server\Logs;

class AbstractPickLog
{
	/**
	 * Pick type
	 *
	 * @var string
	 */
	public $type;

	const SOURCE_PC      = 0;
	const SOURCE_MOB     = 1;
	const SOURCE_NPC     = 2;
	const SOURCE_STORAGE = 3;
	const SOURCE_ITEM    = 4;
	const SOURCE_OTHER   = 5;

	public function sourceType()
	{
		switch($this->type) {
			case 'I':
			case 'O':
				return self::SOURCE_ITEM;
			case 'S':
			case 'N':
				return self::SOURCE_NPC;
			case 'R':
			case 'G':
				return self::SOURCE_STORAGE;
			case 'M':
			case 'L':
			case 'D':
				return self::SOURCE_MOB;
			default:
				return self::SOURCE_PC;
		}
	}
}
