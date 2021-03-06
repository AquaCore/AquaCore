<?php
namespace Aqua\Schedule;

use Aqua\Core\App;
use Aqua\Plugin\Plugin;
use Aqua\SQL\Query;
use Aqua\SQL\Search;

class TaskManager
{
	/**
	 * @var \Aqua\Schedule\TaskData[]
	 */
	public static $tasks = array();

	/**
	 * @param int $id
	 * @return null|\Aqua\Schedule\AbstractTask
	 */
	public static function task($id)
	{
		if(!($taskData = self::get($id)) || !$taskData->isEnabled) {
			return null;
		}
		if(($taskData->pluginId && !Plugin::isEnabled($taskData->pluginId))) {
			$taskData->setError(__('task-error', TaskData::ERR_PLUGIN_DISABLED));
			return null;
		}
		$className = "Aqua\\Schedule\\Task\\$taskData->name";
		if(!class_exists($className)) {
			$taskData->setError(__('task-error', TaskData::ERR_CLASS_NOT_FOUND, htmlspecialchars($className)));
			return null;
		}
		if(!is_subclass_of($className, "Aqua\\Schedule\\AbstractTask")) {
			$taskData->setError(__('task-error', TaskData::ERR_INVALID_CLASS, htmlspecialchars($className)));
			return null;
		}
		$task = new $className;
		foreach(get_class_vars(get_class($taskData)) as $prop => $value) {
			$task->{$prop} = &$taskData->{$prop};
		}
		return $task;
	}

	/**
	 * @return \Aqua\SQL\Search
	 */
	public static function search()
	{
		return Query::search(App::connection())
			->columns(array(
				'id'            => 'id',
			    'name'          => '_name',
			    'title'         => '_title',
			    'description'   => '_description',
			    'expression'    => '_expression',
			    'last_run'      => 'UNIX_TIMESTAMP(_last_run)',
			    'next_run'      => 'UNIX_TIMESTAMP(_next_run)',
			    'running'       => '_running',
			    'enabled'       => '_enabled',
			    'protected'     => '_protected',
			    'logging'       => '_logging',
			    'error_message' => '_error_message',
			    'plugin_id'     => '_plugin_id',
			))
			->whereOptions(array(
				'id'            => 'id',
				'name'          => '_name',
				'title'         => '_title',
				'description'   => '_description',
				'expression'    => '_expression',
				'last_run'      => '_last_run',
				'next_run'      => '_next_run',
				'running'       => '_running',
				'enabled'       => '_enabled',
				'protected'     => '_protected',
				'logging'       => '_logging',
				'error_message' => '_error_message',
				'plugin_id'     => '_plugin_id',
			))
			->from(ac_table('tasks'))
			->groupBy('id')
			->parser(array( __CLASS__, 'parseTaskSql' ));
	}

	/**
	 * @param string|int $id
	 * @param string     $type
	 * @return \Aqua\Schedule\TaskData|null
	 */
	public static function get($id, $type = 'id')
	{
		if(!$id) {
			return null;
		}
		if($type === 'id' && array_key_exists($id, self::$tasks)) {
			return self::$tasks[$id];
		} else if($type === 'name') {
			foreach(self::$tasks as $task) {
				if(strcasecmp($task->name, $id) === 0) {
					return $task;
				}
			}
		}
		$select = Query::select(App::connection())
			->columns(array(
				'id'            => 'id',
				'name'          => '_name',
				'title'         => '_title',
				'description'   => '_description',
				'expression'    => '_expression',
				'last_run'      => 'UNIX_TIMESTAMP(_last_run)',
				'next_run'      => 'UNIX_TIMESTAMP(_next_run)',
				'running'       => '_running',
				'enabled'       => '_enabled',
				'protected'     => '_protected',
				'logging'       => '_logging',
				'error_message' => '_error_message',
				'plugin_id'     => '_plugin_id',
			))
			->from(ac_table('tasks'))
			->parser(array( __CLASS__, 'parseTaskSql' ));
		if($type === 'id') {
			$select->where(array( 'id' => $id ));
		} else {
			$select->where(array( '_name' => $id ));
		}
		$select->query();

		return ($select->valid() ? $select->current() : null);
	}

	/**
	 * @param \SimpleXMLElement $xml
	 * @param int|null          $pluginId
	 * @param bool              $override
	 */
	public static function import(\SimpleXMLElement $xml, $pluginId = null, $override = false)
	{
		$sth = App::connection()->prepare(sprintf('
		INSERT INTO %s (_title, _name, _description, _expression, _enabled, _protected, _logging, _next_run, _error_message, _plugin_id)
		VALUES (:title, :name, :desc, :expr, :enabled, :protected, :logging, :nextrun, :error, :pluginid)
		ON DUPLICATE KEY UPDATE
		_title = VALUES(_title),
		_description = VALUES(_description),
		_expression = VALUES(_expression),
		_error_message = VALUES(_error_message),
		_plugin_id = VALUES(_plugin_id)
		', ac_table('tasks')));
		foreach($xml->task as $task) {
			$name        = (string)$task->name;
			$title       = (string)$task->title;
			$expression  = (string)$task->expression;
			if(!$name || !$title || !$expression) {
				continue;
			}
			if(($existingTask = self::get($name, 'name')) && !$override) {
				continue;
			}
			$taskData = new TaskData;
			$taskData->isEnabled   = filter_var((string)$task->enabled ?: 'yes', FILTER_VALIDATE_BOOLEAN);
			$taskData->isRunning   = false;
			$taskData->isProtected = false;
			$taskData->logging     = true;
			$taskData->name        = $name;
			$taskData->title       = $title;
			$taskData->description = (string)$task->description;
			$taskData->expression  = $expression;
			$taskData->pluginId    = $pluginId;
			try {
				$taskData->nextRun = $taskData->cron()->getNextRunDate()->getTimestamp();
			} catch(\Exception $exception) {
				$taskData->isEnabled = false;
				$taskData->nextRun = time();
				$taskData->errorMessage = __('task-error', TaskData::ERR_INVALID_EXPRESSION);
			}
			if($existingTask) {
				$taskData->id          = $existingTask->id;
				$taskData->isRunning   = $existingTask->isRunning;
				$taskData->isEnabled   = $existingTask->isEnabled;
				$taskData->isProtected = $existingTask->isProtected;
				$taskData->logging     = $existingTask->logging;
				$taskData->nextRun     = $existingTask->nextRun;
			}
			if($taskData->pluginId) {
				$sth->bindValue(':pluginid', $taskData->pluginId, \PDO::PARAM_INT);
			} else {
				$sth->bindValue(':pluginid', null, \PDO::PARAM_NULL);
			}
			if($taskData->description) {
				$sth->bindValue(':desc', $taskData->description, \PDO::PARAM_INT);
			} else {
				$sth->bindValue(':desc', null, \PDO::PARAM_NULL);
			}
			$sth->bindValue(':title', $taskData->title, \PDO::PARAM_STR);
			$sth->bindValue(':name', $taskData->name, \PDO::PARAM_STR);
			$sth->bindValue(':expr', $taskData->expression, \PDO::PARAM_STR);
			$sth->bindValue(':enabled', $taskData->isEnabled ? 'y' : 'n', \PDO::PARAM_STR);
			$sth->bindValue(':protected', $taskData->isProtected ? 'y' : 'n', \PDO::PARAM_STR);
			$sth->bindValue(':logging', $taskData->logging ? 'y' : 'n', \PDO::PARAM_STR);
			$sth->bindValue(':nextrun', date('Y-m-d H:i:s', $taskData->nextRun), \PDO::PARAM_STR);
			$sth->bindValue(':error', $taskData->errorMessage, \PDO::PARAM_STR);
			$sth->execute();
			if(!$taskData->id) {
				$taskData->id = (int)App::connection()->lastInsertId();
			}
			self::$tasks[$taskData->id] = $taskData;
		}
	}

	public static function parseTaskSql(array $data)
	{
		if(array_key_exists($data['id'], self::$tasks)) {
			$task = self::$tasks[$data['id']];
		} else {
			$task = new TaskData;
		}
		$task->id           = (int)$data['id'];
		$task->title        = $data['title'];
		$task->description  = $data['description'];
		$task->name         = $data['name'];
		$task->expression   = $data['expression'];
		$task->lastRun      = (int)$data['last_run'];
		$task->nextRun      = (int)$data['next_run'];
		$task->pluginId     = $data['plugin_id'];
		$task->isEnabled    = ($data['enabled'] === 'y');
		$task->isRunning    = ($data['running'] === 'y');
		$task->isProtected  = ($data['protected'] === 'y');
		$task->logging      = ($data['logging'] === 'y');
		$task->errorMessage = (bool)$data['error_message'];
		self::$tasks[$task->id] = $task;
		return $task;
	}

	public static function runTasks(array $tasks = null)
	{
		if($tasks === null) {
			$tasks = self::search()
				->where(array(
					'enabled' => 'y',
				    'running' => 'n',
				    'next_run' => array(
					    Search::SEARCH_DIFFERENT | Search::SEARCH_HIGHER,
				        date('Y-m-d H:i:s', time())
				    )
				))
				->query();
		} else {
			$taskIds = $tasks;
			array_unshift($taskIds, Search::SEARCH_IN);
			$tasks = self::search()
				->where(array( 'id' => $taskIds ))
				->query();
		}
		if(!$tasks->count()) {
			return;
		}
		ignore_user_abort(true);
		set_time_limit(0);
		foreach($tasks as $taskData) {
			if(!$taskData->isEnabled ||
			   $taskData->isRunning ||
			   $taskData->nextRun > time() ||
			   !($task = self::task($taskData->id))) {
				continue;
			}
			$task->beginTask();
		}
	}
}
