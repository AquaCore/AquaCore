<?php
namespace Page\Admin;

use Aqua\Core\App;
use Aqua\Log\BanLog;
use Aqua\Log\LoginLog;
use Aqua\Log\PayPalLog;
use Aqua\Log\TransferLog;
use Aqua\Site\Page;
use Aqua\UI\Menu;
use Aqua\UI\Pagination;
use Aqua\UI\Template;
use Aqua\Log\ErrorLog;
use Aqua\UI\Theme;

class Log
extends Page
{
	const ENTRIES_PER_PAGE = 20;

	public function run()
	{
		$nav      = new Menu;
		$base_url = ac_build_url(array( 'path'   => array( 'log' ), 'action' => '' ));
		$nav->append('login', array(
			'title' => __('admin-log', 'login-log'),
			'url'   => $base_url . 'login'
		))->append('ban', array(
			'title' => __('admin-log', 'ban-log'),
			'url'   => $base_url . 'ban'
		))->append('pp', array(
			'title' => __('admin-log', 'paypal-log'),
			'url'   => $base_url . 'paypal'
		))->append('credit', array(
			'title' => __('admin-log', 'credit-log'),
			'url'   => $base_url . 'credit'
		))->append('error', array(
			'title' => __('admin-log', 'error-log'),
			'url'   => $base_url . 'error'
		));
		$this->theme->set('nav', $nav);
	}

	public function index_action()
	{
		$this->response->status(302)->redirect(ac_build_url(array( 'path' => array( 'log' ), 'action' => 'login' )));
	}

	public function error_action()
	{
		$this->theme->head->section = $this->title = __('admin-log', 'error-log');
		try {
			$page   = $this->request->uri->getInt('page', 1, 1);
			$search = ErrorLog::search()
				->calcRows(true)
				->where(array( 'parent' => null ))
				->order(array( 'date' => 'DESC' ))
				->limit(($page - 1) * self::ENTRIES_PER_PAGE, self::ENTRIES_PER_PAGE)
				->query();
			$pgn    = new Pagination(App::user()->request->uri, ceil($search->rowsFound / self::ENTRIES_PER_PAGE), $page);
			$tpl    = new Template;
			$tpl->set('errors', $search->results)
				->set('error_count', $search->rowsFound)
				->set('paginator', $pgn)
				->set('page', $this);
			echo $tpl->render('admin/log/error');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function viewerror_action($id = 0, $type = 'html')
	{
		$this->theme->head->section = $this->title = __('error', 'view-error');
		try {
			if(!($error = ErrorLog::get((int)$id))) {
				$this->error(404);

				return;
			}
			$tpl = new Template;
			$tpl->set('error', $error);
			if($type === 'text') {
				$this->theme = new Theme();
				$this->title = '';
				$this->response->setHeader('Content-Type', 'text/plain');
				echo $tpl->render('exception/log');
			} else {
				echo $tpl->render('admin/log/view_error');
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function ban_action()
	{
		$this->theme->head->section = $this->title = __('admin-log', 'ban-log');
		try {
			$page   = $this->request->uri->getInt('page', 1, 1);
			$search = BanLog::search()
				->calcRows(true)
				->order(array( 'ban_date' => 'DESC' ))
				->limit(($page - 1) * self::ENTRIES_PER_PAGE, self::ENTRIES_PER_PAGE)
				->query();
			$pgn    = new Pagination(App::user()->request->uri, ceil($search->rowsFound / self::ENTRIES_PER_PAGE), $page);
			$tpl    = new Template;
			$tpl->set('ban', $search->results)
				->set('ban_count', $search->rowsFound)
				->set('paginator', $pgn)
				->set('page', $this);
			echo $tpl->render('admin/log/ban');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function paypal_action()
	{
		$this->theme->head->section = $this->title = __('admin-log', 'paypal-log');
		try {
			$page   = $this->request->uri->getInt('page', 1, 1);
			$search = PayPalLog::search()
				->calcRows(true)
				->order(array( 'process_date' => 'DESC' ))
				->limit(($page - 1) * self::ENTRIES_PER_PAGE, self::ENTRIES_PER_PAGE)
				->query();
			$pgn    = new Pagination(App::user()->request->uri, ceil($search->rowsFound / self::ENTRIES_PER_PAGE), $page);
			$tpl    = new Template;
			$tpl->set('txn', $search->results)
				->set('txn_count', $search->rowsFound)
				->set('paginator', $pgn)
				->set('page', $this);
			echo $tpl->render('admin/log/paypal');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function viewpaypal_action($id = null)
	{
		$this->theme->head->section = $this->title = __('donation', 'view-transaction');
		try {
			if(!$id || !($txn = PayPalLog::get($id))) {
				$this->error(404);

				return;
			}
			$tpl = new Template;
			$tpl->set('txn', $txn)
				->set('page', $this);
			echo $tpl->render('admin/log/view_pp');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function credit_action()
	{
		$this->theme->head->section = $this->title = __('admin-log', 'credit-log');
		try {
			$page   = $this->request->uri->getInt('page', 1, 1);
			$search = TransferLog::search()
				->calcRows(true)
				->order(array( 'date' => 'DESC' ))
				->limit(($page - 1) * self::ENTRIES_PER_PAGE, self::ENTRIES_PER_PAGE);
			$pgn    = new Pagination(App::user()->request->uri, ceil($search->rowsFound / self::ENTRIES_PER_PAGE), $page);
			$tpl    = new Template;
			$tpl->set('xfer', $search->results)
				->set('xfer_count', $search->rowsFound)
				->set('paginator', $pgn)
				->set('page', $this);
			echo $tpl->render('admin/log/credit');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function login_action()
	{
		$this->theme->head->section = $this->title = __('admin-log', 'login-log');
		try {
			$page   = $this->request->uri->getInt('page', 1, 1);
			$search = LoginLog::search()
				->calcRows(true)
				->order(array( 'date' => 'DESC' ))
				->limit(($page - 1) * self::ENTRIES_PER_PAGE, self::ENTRIES_PER_PAGE)
				->query();
			$pgn    = new Pagination(App::user()->request->uri, ceil($search->rowsFound / self::ENTRIES_PER_PAGE), $page);
			$tpl    = new Template;
			$tpl->set('login', $search->results)
				->set('login_count', $search->rowsFound)
				->set('paginator', $pgn)
				->set('page', $this);
			echo $tpl->render('admin/log/login');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}
}
