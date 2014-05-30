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
		$sth = App::connection()->prepare(sprintf('
		UPDATE `%s`
		SET _running = \'y\',
			_last_run = ?
		WHERE id = ?
		LIMIT 1
		', ac_table('tasks')));
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
		$sth = App::connection()->prepare(sprintf('
		UPDATE `%s`
		SET _running = \'n\',
			_next_run = ?
		WHERE id = ?
		LIMIT 1
		', ac_table('tasks')));
		$this->isRunning = false;
		$this->nextRun   = $this->cron()
			->getNextRunDate()
			->getTimestamp();
		$sth->bindValue(1, date('Y-m-d H:i:s', $this->nextRun), \PDO::PARAM_INT);
		$sth->bindValue(2, $this->id, \PDO::PARAM_INT);
		$sth->execute();
	}

	abstract protected function run();
}
