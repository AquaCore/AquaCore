<?php
namespace Aqua\Log;

use Aqua\Core\App;
use Aqua\Schedule\TaskData;
use Aqua\Schedule\TaskManager;
use Aqua\SQL\Query;

class TaskLog
{
	public $id;
	public $taskId;
	public $start;
	public $end;
	public $runTime;
	public $ipAddress;
	public $outputShort;
	public $outputFull;

	public function startDate($format)
	{
		return strftime($format, $this->start);
	}

	public function endDate($format)
	{
		return strftime($format, $this->end);
	}

	public function task()
	{
		return TaskManager::get($this->taskId);
	}

	public static function logSql(TaskData $task, $startTime, $endTime, $outputShort, $outputFull)
	{
		$sth = App::connection()->prepare(sprintf('
		INSERT INTO %s (_task_id, _ip_address, _start, _end, _run_time, _output_short, _output_full)
		VALUES (:id, :ip, :start, :end, :time, :sout, :fout)
		', ac_table('task_log')));
		$time         = $endTime - $startTime;
		$hours        = floor($time/ 3600);
		$minutes      = floor(($time - $hours * 3600) / 60);
		$seconds      = floor($time % 60);
		$microseconds = floor(($time - floor($time)) * 1000000);
		$time         = str_pad($hours, 3, '0', STR_PAD_LEFT) . ':' .
		                str_pad($minutes, 2, '0', STR_PAD_LEFT) . ':' .
		                str_pad($seconds, 2, '0', STR_PAD_LEFT) . '.' .
		                $microseconds;
		$sth->bindValue(':id', $task->id, \PDO::PARAM_INT);
		$sth->bindValue(':ip', App::request()->ipString, \PDO::PARAM_STR);
		$sth->bindValue(':start', date('Y-m-d H:i:s', $startTime), \PDO::PARAM_STR);
		$sth->bindValue(':end', date('Y-m-d H:i:s', $endTime), \PDO::PARAM_STR);
		$sth->bindValue(':time', $time, \PDO::PARAM_STR);
		$sth->bindValue(':sout', $outputShort, \PDO::PARAM_STR);
		$sth->bindValue(':fout', $outputFull, \PDO::PARAM_STR);
		if(!$sth->execute() || !$sth->rowCount()) {
			return false;
		}
		$log = new self;
		$log->id          = (int)App::connection()->lastInsertId();
		$log->taskId      = $task->id;
		$log->start       = $startTime;
		$log->end         = $endTime;
		$log->runTime     = $endTime - $startTime;
		$log->ipAddress   = App::request()->ipString;
		$log->outputShort = $outputShort;
		$log->outputFull  = $outputFull;
		return $log;
	}

	/**
	 * @return \Aqua\SQL\Search
	 */
	public static function search()
	{
		return Query::search(App::connection())
			->columns(array(
				'id'           => 'tl.id',
			    'task_id'      => 'tl._task_id',
			    'start_time'   => 'UNIX_TIMESTAMP(tl._start)',
			    'end_time'     => 'UNIX_TIMESTAMP(tl._end)',
			    'run_time'     => '(TIME_TO_SEC(tl._run_time) * 1000000 + MICROSECOND(tl._run_time)) / 1000000.0',
			    'ip_address'   => 'tl._ip_address',
			    'output_short' => 'tl._output_short',
			    'output_full'  => 'tl._output_full',
			))
			->whereOptions(array(
				'id'           => 'tl.id',
				'task_id'      => 'tl._task_id',
				'start_time'   => 'tl._start',
				'end_time'     => 'tl._end',
				'run_time'     => 'tl._run_time',
				'ip_address'   => 'tl._ip_address',
				'output_short' => 'tl._output_short',
				'output_full'  => 'tl._output_full',
			))
			->from(ac_table('task_log'), 'tl')
			->groupBy('id')
			->parser(array( __CLASS__, 'parseTaskLogSql' ));
	}

	public static function get($id)
	{
		$search = self::search()->where(array( 'id' => $id ))->query();
		return ($search->valid() ? $search->current() : null);
	}

	public static function parseTaskLogSql(array $data)
	{
		$log = new self;
		$log->id          = (int)$data['id'];
		$log->taskId      = (int)$data['task_id'];
		$log->start       = (int)$data['start_time'];
		$log->end         = (int)$data['end_time'];
		$log->runTime     = (float)$data['run_time'];
		$log->ipAddress   = $data['ip_address'];
		$log->outputShort = $data['output_short'];
		$log->outputFull  = $data['output_full'];
		return $log;
	}
}
 