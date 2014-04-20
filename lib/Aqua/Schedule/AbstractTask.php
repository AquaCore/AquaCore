<?php
namespace Aqua\Schedule;

use Aqua\Core\App;
use Aqua\Log\TaskLog;

abstract class AbstractTask
extends TaskData
{
	public final function beginTask()
	{
		$this->isRunning = true;
		$this->lastRun = microtime(true);
		$tbl = ac_table('tasks');
		$sth = App::connection()->prepare("
		UPDATE `$tbl`
		SET _running = 'y',
			_last_run = ?
		WHERE id = ?
		LIMIT 1
		");
		$sth->bindValue(1, date('Y-m-d H:i:s', $this->lastRun), \PDO::PARAM_INT);
		$sth->bindValue(2, $this->id, \PDO::PARAM_INT);
		$sth->execute();
		$this->run();
	}

	public final function endTask($outputShort, $outputFull)
	{
		$this->isRunning = false;
		$endTime = microtime(true);
		$this->abort();
		TaskLog::logSql($this, $this->lastRun, $endTime, $outputShort, $outputFull);
	}

	public final function abort()
	{
		$tbl = ac_table('tasks');
		$sth = App::connection()->prepare("
		UPDATE `$tbl`
		SET _running = 'n',
			_next_run = ?
		WHERE id = ?
		LIMIT 1
		");
		$this->isRunning = false;
		$this->nextRun   = $this->cron()
			->getNextRunDate('now')
			->getTimestamp();
		$sth->bindValue(1, date('Y-m-d', $this->nextRun), \PDO::PARAM_INT);
		$sth->bindValue(2, $this->id, \PDO::PARAM_INT);
		$sth->execute();
	}

	abstract protected function run();
}
