<?php
use Aqua\Core\App;
use Aqua\SQL\Search;
use Aqua\Log\ErrorLog;
use Aqua\Schedule\TaskManager;

define('Aqua\ROOT',        str_replace('\\', '/', rtrim(__DIR__, DIRECTORY_SEPARATOR)));
define('Aqua\SCRIPT_NAME', basename(__FILE__));
define('Aqua\ENVIRONMENT', 'MINIMAL');
define('Aqua\PROFILE',     'CRON');

require_once 'lib/bootstrap.php';

try {
	if(App::settings()->get('cron_key', null) !== App::request()->uri->getString('2')) {
		die;
	}
	$tasks = App::request()->uri->getString('1');
	if(!$tasks) {
		die;
	}
	$search = TaskManager::search();
	if($tasks === 'all') {
		$search->where(array(
           'running' => 'n',
           'next_run' => array(
               Search::SEARCH_DIFFERENT | Search::SEARCH_HIGHER,
               date('Y-m-d H:i:s', time())
           )
		));
	} else {
		$tasks = preg_split('/\s*,\s*/', $tasks);
		if(count($tasks) > 1) {
			array_unshift($tasks, Search::SEARCH_IN);
		} else {
			$tasks = $tasks[0];
		}
		$search->where(array( 'name' => $tasks ));
	}
	$search->query();
	if(!$search->count()) {
		die;
	}
	ignore_user_abort(true);
	set_time_limit(0);

	\Aqua\Plugin\Plugin::init();

	foreach($search as $taskData) {
		if($task = TaskManager::task($taskData->id)) {
			$task->beginTask();
		}
	}
} catch(\Exception $exception) {
	ErrorLog::logSql($exception);
}
