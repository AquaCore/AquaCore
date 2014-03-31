<?php
namespace Aqua\Ragnarok\Server\Logs;

class AtcommandLog
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
	 * Execution timestamp
	 *
	 * @var int
	 */
	public $date;
	/**
	 * Source account ID
	 *
	 * @var int
	 */
	public $accountId;
	/**
	 * Source character ID
	 *
	 * @var int
	 */
	public $charId;
	/**
	 * Source character name
	 *
	 * @var int
	 */
	public $charName;
	/**
	 * Map where the command was executed
	 *
	 * @var string
	 */
	public $map;
	/**
	 * Full command used
	 *
	 * @var string
	 */
	public $command;

	/**
	 * Format the execution date.
	 *
	 * @param string $format
	 * @return string
	 */
	public function date($format)
	{
		return strftime($format, $this->date);
	}

	/**
	 * Command name
	 *
	 * @return string
	 */
	public function command()
	{
		return substr(strstr($this->command, ' ', true), 1);
	}

	/**
	 * Parameters passed
	 *
	 * @return string
	 */
	public function parameters()
	{
		return substr(strstr($this->command, ' '), 1);
	}

	/**
	 * @return \Aqua\Ragnarok\Account
	 */
	public function account()
	{
		return $this->charmap->server->login->get($this->accountId);
	}

	/**
	 * @return \Aqua\Ragnarok\Character|null
	 */
	public function character()
	{
		return $this->charmap->character($this->charId);
	}
}
