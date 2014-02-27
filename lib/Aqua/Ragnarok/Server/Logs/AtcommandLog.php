<?php
namespace Aqua\Ragnarok\Server\Logs;

class AtcommandLog
{
	public $id;
	public $date;
	public $account_id;
	public $char_id;
	public $map;
	public $command;

	public function command()
	{
		return substr(strstr($this->command, ' ', true), 1);
	}

	public function parameters()
	{
		return substr(strstr($this->command, ' '), 1);
	}
}
