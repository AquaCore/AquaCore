<?php
namespace Page\Admin;

use Aqua\Core\App;
use Aqua\Core\L10n;
use Aqua\Log\BanLog;
use Aqua\Log\LoginLog;
use Aqua\Log\PayPalLog;
use Aqua\Log\TransferLog;
use Aqua\Site\Page;
use Aqua\UI\Menu;
use Aqua\UI\Pagination;
use Aqua\UI\Search;
use Aqua\UI\Template;
use Aqua\Log\ErrorLog;
use Aqua\UI\Theme;
use Aqua\User\Account;
use Aqua\Util\DataPreload;

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
			$currentPage = $this->request->uri->getInt('page', 1, 1);
			$frm = new Search(App::request(), $currentPage);
			$frm->order(array(
					'id'    => 'id',
			        'url'   => 'url',
			        'ip'    => 'ip_address',
			        'code'  => 'code',
			        'class' => 'type',
					'date'  => 'date'
				))
				->limit(0, 6, 20, 5)
				->defaultOrder('id', Search::SORT_DESC)
				->defaultLimit(20)
				->persist('admin.errorlog');
			$search = ErrorLog::search()
				->calcRows(true)
				->where(array( 'parent' => null ));
			$frm->apply($search);
			$search->query();
			$pgn = new Pagination(App::user()->request->uri,
			                      ceil($search->rowsFound / $frm->getLimit()),
			                      $currentPage);
			$tpl = new Template;
			$tpl->set('errors', $search->results)
				->set('errorCount', $search->rowsFound)
				->set('paginator', $pgn)
				->set('search', $frm)
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
				$this->theme->set('return', ac_build_url(array(
						'path' => array( 'log' ),
						'action' => 'error'
					)));
				echo $tpl->render('admin/log/view-error');
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
			$currentPage = $this->request->uri->getInt('page', 1, 1);
			$search = BanLog::search()->calcRows(true);
			$frm = new Search(App::request(), $currentPage);
			$frm->order(array(
					'id'    => 'id',
			        'type'  => 'type',
			        'ban'   => 'ban_date',
			        'unban' => 'unban_date'
				))
			    ->limit(0, 7, 15, 5)
			    ->defaultOrder('id', Search::SORT_DESC)
			    ->defaultLimit(15)
			    ->persist('admin.banlog');
			$frm->input('user')
				->setColumn('display_name')
				->setParser(array( $this, 'parseDisplayNameSearch' ),
				            array( array( 'display_name' => 'b._banned_id' ), $search ))
				->setLabel(__('profile', 'user'));
			$frm->range('ban')
				->setColumn('ban_date')
				->setLabel(__('profile', 'ban-date'))
				->type('datetime')
				->attr('placeholder', 'YYYY-MM-DD HH:MM:SS');
			$frm->range('unban')
				->setColumn('unban_date')
				->setLabel(__('profile', 'unban-date'))
				->type('datetime')
				->attr('placeholder', 'YYYY-MM-DD HH:MM:SS');
			$frm->select('type')
				->setColumn('type')
				->setLabel(__('profile', 'ban-type'))
				->multiple()
				->value(L10n::getDefault()->rangeList('ban-type', range(1, 3)));
			$frm->apply($search);
			$search->query();
			$users = new DataPreload('Aqua\\User\\Account::search', Account::$users);
			$users->add($search, array( 'user_id', 'banned_user_id' ))->run();
			$pgn = new Pagination(App::user()->request->uri,
			                      ceil($search->rowsFound / $frm->getLimit()),
			                      $currentPage);
			$tpl = new Template;
			$tpl->set('ban', $search->results)
				->set('banCount', $search->rowsFound)
				->set('paginator', $pgn)
				->set('search', $frm)
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
			$currentPage = $this->request->uri->getInt('page', 1, 1);
			$frm = new Search(App::request(), $currentPage);
			$search = PayPalLog::search()->calcRows(true);
			$frm->order(array(
					'id'          => 'id',
			        'deposited'   => 'deposited',
			        'gross'       => 'gross',
			        'credits'     => 'credits',
			        'type'        => 'txn_type',
			        'email'       => 'payer_email',
			        'processdate' => 'process_date',
			        'paydate'     => 'payment_date'
				))
			    ->limit(0, 6, 20, 5)
			    ->defaultOrder('id', Search::SORT_DESC)
			    ->defaultLimit(20)
			    ->persist('admin.pplog');
			$frm->input('user')
				->setColumn('display_name')
				->setParser(array( $this, 'parseDisplayNameSearch' ),
				            array( array( 'display_name' => 'pp._user_id' ), $search ))
				->setLabel(__('donation', 'user'));
			$frm->input('email')
				->setColumn('email')
				->setLabel(__('donation', 'payer-email'));
			$frm->range('gross')
				->setColumn('gross')
				->setLabel(__('donation', 'gross'))
				->type('number')
				->attr('min', '0');
			$frm->range('credits')
				->setColumn('credits')
				->setLabel(__('donation', 'credits'))
				->type('number')
				->attr('min', '0');
			$frm->range('date')
				->setColumn('payment_date')
				->setLabel(__('donation', 'payment-date'))
				->type('datetime')
				->attr('placeholder', 'YYYY-MM-DD HH:MM:SS');
			$frm->apply($search);
			$search->query();
			$users = new DataPreload('Aqua\\User\\Account::search', Account::$users);
			$users->add($search, array( 'user_id' ))->run();
			$pgn    = new Pagination(App::user()->request->uri, ceil($search->rowsFound / self::ENTRIES_PER_PAGE), $currentPage);
			$tpl    = new Template;
			$tpl->set('txn', $search->results)
				->set('txnCount', $search->rowsFound)
				->set('paginator', $pgn)
				->set('search', $frm)
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
			$this->theme->set('return', ac_build_url(array(
					'path' => array( 'log' ),
					'action' => 'paypal'
				)));
			$tpl = new Template;
			$tpl->set('txn', $txn)
				->set('page', $this);
			echo $tpl->render('admin/log/view-pp');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function credit_action()
	{
		$this->theme->head->section = $this->title = __('admin-log', 'credit-log');
		try {
			$currentPage = $this->request->uri->getInt('page', 1, 1);
			$search = TransferLog::search()->calcRows(true);
			$frm = new Search(App::request(), $currentPage);
			$frm->order(array(
		            'id'     => 'id',
		            'amount' => 'amount',
		            'date'   => 'date',
	            ))
			    ->limit(0, 7, 20, 5)
			    ->defaultOrder('id', Search::SORT_DESC)
			    ->defaultLimit(20)
			    ->persist('admin.xferlog');
			$frm->input('user')
			    ->setColumn(array( 'sender_display_name', 'receiver_display_name' ))
			    ->setParser(array( $this, 'parseDisplayNameSearch' ), array( array(
					'sender_display_name'   => 'tl._sender_id',
					'receiver_display_name' => 'tl._receiver_id'
				), $search ))
			    ->setLabel(__('profile', 'user'));
			$frm->range('amount')
				->setColumn('amount')
				->setLabel(__('donation', 'credits'))
				->type('number')
				->attr('min', 0);
			$frm->range('date')
				->setColumn('date')
				->setLabel(__('donation', 'xfer-date'))
				->type('datetime')
				->attr('placeholder', 'YYYY-MM-DD HH:MM:SS');
			$frm->apply($search);
			$search->query();
			$users = new DataPreload('Aqua\\User\\Account::search', Account::$users);
			$users->add($search, array( 'sender', 'receiver' ))->run();
			$pgn = new Pagination(App::user()->request->uri,
			                      ceil($search->rowsFound / $frm->getLimit()),
			                      $currentPage);
			$tpl = new Template;
			$tpl->set('xfer', $search->results)
				->set('xferCount', $search->rowsFound)
				->set('paginator', $pgn)
				->set('search', $frm)
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
			$currentPage = $this->request->uri->getInt('page', 1, 1);
			$search = LoginLog::search()->calcRows(true);
			$frm = new Search(App::request(), $currentPage);
			$frm->order(array(
		            'date'   => 'date',
					'ip'     => 'ip',
					'type'   => 'type',
					'status' => 'status',
					'uname' => 'username',
				))
			    ->limit(0, 7, 20, 5)
			    ->defaultOrder('date', Search::SORT_DESC)
			    ->defaultLimit(20)
			    ->persist('admin.loginlog');
			$frm->input('user')
			    ->setColumn('display_name')
			    ->setParser(array( $this, 'parseDisplayNameSearch' ), array( array( 'display_name' => 'll._user_id' ), $search ))
			    ->setLabel(__('profile', 'display-name'));
			$frm->input('uname')
			    ->setColumn('username')
			    ->setLabel(__('login-log', 'username'));
			$frm->input('ip')
			    ->setColumn('ip-address')
			    ->setLabel(__('login-log', 'ip-address'));
			$frm->range('date')
			    ->setColumn('date')
			    ->setLabel(__('login-log', 'date'))
				->type('datetime')
				->attr('placeholder', 'YYYY-MM-DD HH:MM:SS');
			$frm->select('type')
				->setColumn('type')
				->setLabel(__('login-log', 'type'))
				->value(array(
					'' => __('application', 'any'),
					LoginLog::TYPE_NORMAL => __('login-type', LoginLog::TYPE_NORMAL),
					LoginLog::TYPE_PERSISTENT => __('login-type', LoginLog::TYPE_PERSISTENT)
				));
			$frm->select('status')
				->setColumn('status')
				->setLabel(__('login-log', 'status'))
				->multiple()
				->value(array(
					0 => __('login-status', 0),
					1 => __('login-status', 1),
					2 => __('login-status', 2)
				));
			$frm->apply($search);
			$search->query();
			$users = new DataPreload('Aqua\\User\\Account::search', Account::$users);
			$users->add($search, array( 'user_id' ))->run();
			$pgn    = new Pagination(App::user()->request->uri,
			                         ceil($search->rowsFound / $frm->getLimit()),
			                         $currentPage);
			$tpl    = new Template;
			$tpl->set('login', $search->results)
				->set('loginCount', $search->rowsFound)
				->set('paginator', $pgn)
				->set('search', $frm)
				->set('page', $this);
			echo $tpl->render('admin/log/login');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function parseDisplayNameSearch($input, $frm, $username, array $joins, \Aqua\SQL\Search $search) {
		$username = addcslashes($username, '%_\\');
		$username = "%$username%";
		foreach($joins as $alias => $column) {
			$i = App::uid();
			$search->innerJoin(ac_table('users'), "u$i.id = $column", "u$i")
			       ->whereOptions(array( $alias => "u$i._display_name" ));
		}
		return array( \Aqua\SQL\Search::SEARCH_LIKE, $username );
	}
}
