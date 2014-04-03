<?php
namespace Aqua\Ragnarok\Server\Logs;

class ChatLog
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
	public $type;
	/**
	 * @var int
	 */
	public $typeId;
	/**
	 * @var int
	 */
	public $srcCharId;
	/**
	 * @var int
	 */
	public $srcAccountId;
	/**
	 * @var string
	 */
	public $dstName;
	/**
	 * @var string
	 */
	public $message;
	/**
	 * @var string
	 */
	public $map;
	/**
	 * @var int
	 */
	public $x;
	/**
	 * @var int
	 */
	public $y;

	const TYPE_GLOBAL  = 1;
	const TYPE_WHISPER = 2;
	const TYPE_PARTY   = 3;
	const TYPE_GUILD   = 4;
	const TYPE_MAIN    = 5;

	public function date($format)
	{
		return strftime($format, $this->date);
	}

	public function type()
	{
		return __('ragnarok-char-log-type', $this->type);
	}

	public function account()
	{
		return $this->charmap->server->login->get($this->srcAccountId, 'id');
	}

	public function source()
	{
		return $this->charmap->character($this->srcCharId);
	}
}
