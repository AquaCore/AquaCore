<?php
namespace Page\Admin;

use Aqua\Core\App;
use Aqua\Log\ErrorLog;
use Aqua\Log\TaskLog;
use Aqua\Schedule\TaskManager;
use Aqua\Site\Page;
use Aqua\SQL\Query;
use Aqua\UI\Form;
use Aqua\UI\Pagination;
use Aqua\UI\Search;
use Aqua\UI\Template;
use Aqua\UI\Theme;
use Cron\CronExpression;

class Task
extends Page
{
	public static $tasksPerPage = 15;

	public function index_action()
	{
		try {
			if($this->request->method === 'POST' &&
			   ($taskId = $this->request->getInt('x-run')) &&
			   ($task = TaskManager::task($taskId)) &&
			   $this->request->getString('runtaskid') === App::user()->getToken('runtask')) {
				$message = $messageType = '';
				try {
					if(!$task->errorMessage) {
						$task->beginTask();
					}
					if($task->errorMessage) {
						$message = $task->errorMessage;
						$messageType = 'error';
					} else {
						$message = __('task', 'task-run', htmlspecialchars($task->title));
						$messageType = 'success';
					}
				} catch(\Exception $exception) {
					ErrorLog::logSql($exception);
					$message = __('application', 'unexpected-error');
					$messageType = 'error';
				}
				if($this->request->ajax) {
					$this->theme = new Theme;
					$this->response->setHeader('Content-Type', 'application/json');
					echo json_encode(array(
						'message' => $message,
					    'type'    => $messageType,
					    'key'     => App::user()->setToken('runtask')
					));
				} else {
					$this->response->status(302)->redirect(App::request()->uri->url());
					App::user()->addFlash($messageType, null, $message);
				}
				return;
			}
			$currentPage = $this->request->uri->getInt('page', 1, 1);
			$this->title = $this->theme->head->section = __('task', 'tasks');
			$search = TaskManager::search()
				->calcRows(true)
				->limit(($currentPage - 1) * self::$tasksPerPage, self::$tasksPerPage)
				->query();
			$pgn = new Pagination(App::request()->uri,
			                      ceil($search->rowsFound / self::$tasksPerPage),
			                      $currentPage);
			$tpl = new Template;
			$tpl->set('tasks', $search->results)
				->set('taskCount', $search->rowsFound)
				->set('paginator', $pgn)
				->set('tokenKey', App::user()->setToken('runtask'))
				->set('page', $this);
			echo $tpl->render('admin/task/main');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function log_action()
	{
		$this->title = $this->theme->head->section = __('task', 'task-log');
		try {
			$currentPage = $this->request->uri->getInt('page', 1, 1);
			$frm = new Search(App::request(), $currentPage);
			$frm->order(array(
					'id'     => 'id',
				    'taskid' => 'task_id',
				    'task'   => 'task_title',
				    'start'  => 'start_time',
				    'end'    => 'end_time',
				    'run'    => 'run_time',
				    'ip'     => 'ip_address',
				))
				->limit(0, 6, 20, 5)
				->defaultLimit(20)
				->defaultOrder('id', Search::SORT_DESC)
				->persist('admin.tasklog');
			$frm->join('task_title', 't._title')
				->type('INNER')
				->tables(array( 't' => ac_table('tasks') ))
				->on('t.id = tl._task_id');
			$frm->input('task')
				->setColumn('task_title')
				->setLabel(__('task', 'task'));
			$frm->input('ip')
				->setColumn('ip_address')
				->searchType(Search\Input::SEARCH_LIKE_RIGHT)
				->setLabel(__('task', 'ip-address'));
			$frm->range('start')
				->setColumn('start_time')
				->setLabel(__('task', 'start-time'))
				->type('datetime')
				->attr('placeholder', 'YYYY-MM-DD HH:MM:SS');
			$search = TaskLog::search()->calcRows(true);
			$frm->apply($search);
			$search->query();
			$pgn = new Pagination(App::request()->uri,
			                      ceil($search->rowsFound / $frm->getLimit()),
			                      $currentPage);
			$tpl = new Template;
			$tpl->set('tasks', $search->results)
				->set('taskCount', $search->rowsFound)
				->set('search', $frm)
				->set('paginator', $pgn)
				->set('page', $this);
			echo $tpl->render('admin/task/log');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function viewlog_action($id = null)
	{
		$this->title = $this->theme->head->section = __('task', 'view-task-log');
		try {
			if(!$id || !($log = TaskLog::get($id))) {
				$this->error(404);
				return;
			}
			$this->theme->set('return', ac_build_url(array(
				'path' => array( 'task' ),
			    'action' => 'log'
			)));
			$tpl = new Template;
			$tpl->set('log', $log)
				->set('page', $this);
			echo $tpl->render('admin/task/view-log');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function edit_action($id = null)
	{
		try {
			if(!$id || !($task = TaskManager::get($id)) || $task->isProtected) {
				$this->error(404);
				return;
			}
			$frm = new Form($this->request);
			$frm->input('title', true)
				->type('text')
				->setLabel(__('task', 'title'))
				->value(htmlspecialchars($task->title), false);
			$frm->input('description', true)
				->type('text')
				->setLabel(__('task', 'description'))
				->value(htmlspecialchars($task->description), false);
			$frm->input('expression', true)
				->type('text')
				->setLabel(__('task', 'expression'))
				->value(htmlspecialchars($task->expression), false);
			$frm->checkbox('logging')
				->value(array( '1' => '' ))
				->setLabel(__('task', 'logging'))
				->checked($task->logging ? '1': '');
			$frm->checkbox('enable')
				->value(array( '1' => '' ))
				->setLabel(__('task', 'enable'))
				->checked($task->isEnabled ? '1': '');
			$frm->submit();
			$frm->validate(function(Form $frm) {
				$expression = trim($frm->request->getString('expression'));
				try {
					$cron = CronExpression::factory($expression);
					$cron->getNextRunDate()->getTimestamp();
				} catch(\Exception $exception) {
					$frm->field('expression')->setWarning(__('task', 'invalid-expression'));
					return false;
				}
				return true;
			});
			if($frm->status !== Form::VALIDATION_SUCCESS) {
				$this->title = $this->theme->head->section = __('task', 'edit-task', htmlspecialchars($task->title));
				$this->theme->set('return', ac_build_url(array( 'path' => array( 'task' ) )));
				$tpl = new Template;
				$tpl->set('task', $task)
					->set('form', $frm)
					->set('page', $this);
				echo $tpl->render('admin/task/edit');
				return;
			}
			$this->response->status(302)->redirect(App::request()->uri->url());
			try {
				if(((bool)$this->request->getInt('enable') ? $task->enable() : $task->disable()) ||
				   $task->edit(array(
					'title'       => trim($this->request->getString('title')),
					'description' => trim($this->request->getString('description')),
					'expression'  => trim($this->request->getString('expression')),
					'logging'     => (bool)$this->request->getInt('logging')))) {
					App::user()->addFlash('success', null, __('task', 'task-updated', htmlspecialchars($task->title)));
				}
			} catch(\Exception $exception) {
				ErrorLog::logSql($exception);
				App::user()->addFlash('error', null, __('application', 'unexpected-error'));
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function cron_action()
	{
		try {
			$this->title = $this->theme->head->section = __('task', 'cron');
			$this->theme->set('return', ac_build_url(array( 'path' => array( 'task' ) )));
			$tpl = new Template;
			$tpl->set('page', $this);
			echo $tpl->render('admin/task/cron');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}
}