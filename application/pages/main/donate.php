<?php
namespace Page\Main;

use Aqua\Core\App;
use Aqua\Log\ErrorLog;
use Aqua\Log\PayPalLog;
use Aqua\Log\TransferLog;
use Aqua\Site\Page;
use Aqua\UI\Form;
use Aqua\UI\Menu;
use Aqua\UI\Pagination;
use Aqua\UI\Template;
use Aqua\User\Account;

class Donate
extends Page
{
	const ENTRIES_PER_PAGE = 10;

	public function run()
	{
		$menu = new Menu;
		$base_url = ac_build_url(array(
			'path' => array( 'donate' ),
			'action' => ''
		));
		$menu->append('donate', array(
			'title' => __('donation', 'donate'),
			'url'   => "{$base_url}index"
		));
		if(App::user()->loggedIn()) {
			$menu->append('history', array(
					'title' => __('donation', 'history'),
					'url'   => "{$base_url}history"
				))->append('transfer' , array(
					'title' => __('donation', 'transfer'),
					'url'   => "{$base_url}transfer"
				))->append('transfer-history' , array(
					'title' => __('donation', 'transfer-history'),
					'url'   => "{$base_url}transfer_history"
				));
		}
		$this->theme->set('menu', $menu);
	}

	public function index_action()
	{
		$this->theme->head->section = $this->title = __('donation', 'donate');
		if(($amount = $this->request->getFloat('amount', false)) !== false && $amount > 0) {
			$tpl = new Template;
			$tpl->set('credits', floor($amount / ((float)App::settings()->get('donation')->get('exchange_rate', 1))))
				->set('amount', round($amount, 2))
				->set('page', $this);
			echo $tpl->render('donation/complete');
			return;
		}
		$tpl = new Template;
		$tpl->set('page', $this);
		echo $tpl->render('donation/main');
	}

	public function history_action()
	{
		$this->theme->head->section = $this->title = __('donation', 'history');
		try {
			$current_page = $this->request->uri->getInt('page', 1, 1);
			$search = PayPalLog::search()
				->where(array( 'user_id' => App::user()->account->id ))
				->order(array( 'process_date' => 'DESC' ))
				->limit(($current_page - 1) * self::ENTRIES_PER_PAGE, self::ENTRIES_PER_PAGE)
				->query();
			$pgn = new Pagination(App::request()->uri, ceil($search->rowsFound / self::ENTRIES_PER_PAGE), $current_page);
			$tpl = new Template;
			$tpl->set('paginator', $pgn)
		        ->set('transactions', $search->results)
		        ->set('transaction_count', $search->rowsFound)
		        ->set('page', $this);
			echo $tpl->render('donation/history');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function transfer_action($key = null)
	{
		try {
			$account = App::user()->account;
			if($key) {
				if(!($data = App::user()->session->get('credit-transfer::' . $key))) {
					$this->error(404);
					return;
				}
				$target = Account::get($data['target']);
				$credits = $data['amount'];
				$frm = new Form($this->request);
				$frm->token('donation_confirm_transfer', 16);
				$frm->submit(__('application', 'confirm'));
				$frm->validate();
				if($frm->status === Form::VALIDATION_SUCCESS) {
					$this->response->status(302)->redirect(App::request()->uri->url(array( 'arguments' => array( ) )));
					try {
						if($credits > $account->credits) {
							App::user()->addFlash('warning', null, __('donation', 'transfer-not-enough-credits'));
						} else if($account->transferCredits($target, $credits)) {
							App::user()->addFlash('success', null, __('donation', 'credits-transferred', __('donation', 'credit-points', number_format($credits)), $target->display()));
						} else {
							App::user()->addFlash('warning', null, __('donation', 'credits-not-transferred'), __('donation', 'credit-points', number_format($credits), $target->display()));
						}
					} catch(\Exception $exception) {
						ErrorLog::logSql($exception);
						App::user()->addFlash('error', null, __('application', 'unexpected-error'));
					}
				} else {
					$this->title = $this->theme->head->section = __('donation', 'transfer');
					App::user()->session->keep('credit-transfer::' . $key, 1);
					$tpl = new Template;
					$tpl->set('target', $target)
						->set('amount', $credits)
						->set('form', $frm)
						->set('page', $this);
					echo $tpl->render('donation/confirm-transfer');
				}
				return;
			}
			$frm = new Form($this->request);
			$frm->input('display')
				->type('text')
				->required()
			    ->setLabel(__('donation', 'target-display-label'))
			    ->setDescription(__('donation', 'target-display-desc'));
			$frm->input('amount')
				->type('number')
				->attr('min', 1)
				->required()
			    ->setLabel(__('donation', 'transfer-amount'));
			$frm->input('password')
				->type('password')
				->required()
				->attr('autocomplete', 'off')
				->setLabel(__('profile', 'current-password'));
			$frm->token('donation_transfer_credits');
			$frm->submit();
			$target = null;
			$frm->validate(function(Form $frm) use (&$account, &$target) {
					if(Account::checkCredentials($account->username, $frm->request->getString('password')) !== 0) {
						$frm->field('password')->setWarning(__('profile', 'password-incorrect'));
						return false;
					}
					if($frm->request->getInt('amount') > $account->credits) {
						$frm->field('amount')->setWarning(__('donation', 'transfer-not-enough-credits'));
						return false;
					} else if(!($target = Account::get($frm->request->getString('display'), 'display_name'))) {
						$frm->field('display')->setWarning(__('donation', 'target-not-found'));
						return false;
					} else if($target->id === $account->id) {
						$frm->field('display')->setWarning(__('donation', 'cannot-transfer-to-self'));
						return false;
					}
					return true;
				});
			if($frm->status !== Form::VALIDATION_SUCCESS) {
				$this->title = $this->theme->head->section = __('donation', 'transfer');
				$tpl = new Template;
				$tpl->set('form', $frm)
			        ->set('page', $this);
				echo $tpl->render('donation/transfer');
				return;
			}
			$key = bin2hex(secure_random_bytes(32));
			App::user()->session->flash('credit-transfer::' . $key, array(
					'target' => $target->id,
					'amount' => $this->request->getInt('amount')
				));
			$this->response->status(302)->redirect(App::request()->uri->url(array( 'arguments' => array( $key ) )));
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function transfer_history_action()
	{
		$this->title = $this->theme->head->section = __('donation', 'transfer-history');
		try {
			$current_page = $this->request->uri->getInt('page', 1, 1);
			$search = TransferLog::search()
				->where(array(
						'receiver' => App::user()->account->id,
						'OR',
						'sender' => App::user()->account->id
					))
				->order(array( 'date' => 'DESC' ))
				->limit(($current_page - 1) * self::ENTRIES_PER_PAGE, self::ENTRIES_PER_PAGE)
				->query();
			$pgn = new Pagination(App::request()->uri, ceil($search->rowsFound / self::ENTRIES_PER_PAGE), $current_page);
			$tpl = new Template;
			$tpl->set('transfers', $search->results)
		        ->set('transfer_count', $search->rowsFound)
				->set('paginator', $pgn)
		        ->set('page', $this);
			echo $tpl->render('donation/transfer-history');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function thankyou_action()
	{
		$this->theme->head->section = $this->title = __('donation', 'donate');
		$tpl = new Template;
		$tpl->set('page', $this);
		echo $tpl->render('donation/thankyou');
	}
}