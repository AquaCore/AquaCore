<?php
namespace Page\Main\Ragnarok;

use Aqua\Core\App;
use Aqua\Core\L10n;
use Aqua\Http\Request;
use Aqua\Site\Page;
use Aqua\Ragnarok\Item;
use Aqua\Ragnarok\Server;
use Aqua\Ragnarok\Server\Login;
use Aqua\Ragnarok\Account as RagnarokAccount;
use Aqua\SQL\Search;
use Aqua\UI\Form;
use Aqua\UI\Menu;
use Aqua\UI\Pagination;
use Aqua\UI\Template;
use Aqua\User\Account as UserAccount;
use Aqua\Log\ErrorLog;
use PHPMailer\PHPMailerException;

class Account
extends Page
{
	/**
	 * @var \Aqua\Ragnarok\Server
	 */
	public $server;

	/**
	 * @var \Aqua\Ragnarok\Account
	 */
	public $account;

	/**
	 * @var int
	 */
	public static $storageItemsPerPage = 10;

	public function run()
	{
		$this->server = App::$activeServer;
		$this->account = App::$activeRagnarokAccount;
		if(!$this->server || !$this->account) {
			return;
		}
		$this->response->setHeader('Cache-Control', 'no-store, co-cache, must-revalidate, max-age=0');
		$this->response->setHeader('Expires', time() - 1);
		$base_url = $this->account->url(array( 'action' => '' ));
		$menu = new Menu;
		$menu->append('account', array(
			'title' => __('ragnarok', 'account'),
			'url'   => "{$base_url}index"
			))->append('options', array(
			'title' => __('ragnarok', 'preferences'),
			'url'   => "{$base_url}options"
			))->append('char', array(
			'title' => __('ragnarok', 'characters'),
			'url'   => "{$base_url}char"
			))->append('storage', array(
			'title' => __('ragnarok', 'storage'),
			'url'   => "{$base_url}storage"
			))->append('recoverpw', array(
			'title' => __('ragnarok', 'recover-password'),
			'url'   => "{$base_url}recoverpw"
			))
		;
		$this->theme->set('menu', $menu);
	}

	public function index_action()
	{
		$this->title = __('ragnarok-account', 'view-account-name', htmlspecialchars($this->account->username));
		$this->theme->head->section = __('ragnarok-account', 'view-account');
		$tpl = new Template;
		$tpl->set('account', $this->account);
		$tpl->set('page', $this);
		echo $tpl->render('ragnarok/account/view');
	}

	public function options_action()
	{
		if(!\Aqua\HTTPS && App::settings()->get('ssl', 0) >= 1) {
			$this->response->status(301)->redirect(App::request()->uri->url(array( 'protocol' => 'https://' )));
			return;
		}
		try {
			$frm = new Form($this->request);
			$frm->input('confirm_password')
			    ->type('password')
				->required()
			    ->setLabel(__('ragnarok', 'current-password'));
			$frm->input('password')
				->type('password')
				->setLabel(__('ragnarok', 'password'));
			$frm->input('password_r')
				->type('password')
				->setLabel(__('ragnarok', 'repeat-password'));
			if($this->server->login->getOption('use-pincode')) {
				$pincode_len = (int)App::settings()->get('ragnarok')->get('pincode_max_len', 4);
				$frm->input('confirm_pincode')
				    ->type('password')
				    ->attr('maxlen', $pincode_len)
				    ->attr('size', $pincode_len + 2)
				    ->setLabel(__('ragnarok', 'current-pincode'));
				$frm->input('pincode')
				    ->type('password')
				    ->attr('maxlen', $pincode_len)
				    ->attr('size', $pincode_len + 2)
				    ->setLabel(__('ragnarok', 'pincode'));
				$frm->input('pincode_r')
				    ->type('password')
				    ->attr('maxlen', $pincode_len)
				    ->attr('size', $pincode_len + 2)
				    ->setLabel(__('ragnarok', 'pincode-repeat'));
			}
			$frm->checkbox('locked')
				->value(array( '1' => ''))
				->checked($this->account->isLocked() ? '1' : null)
				->setLabel(__('ragnarok', 'locked'))
				->setDescription(__('ragnarok', 'lockdown-desc'));
			$frm->token('ragnarok_edit_account');
			$frm->submit();
			$pgn = $this;
			$frm->validate(function(Form $frm, &$warning) use (&$pgn) {
				if(!$pgn->server->login->checkCredentials($pgn->account->username,
				                                         $frm->request->getString('confirm_password'),
				                                         $frm->request->getString('confirm_pincode', null))) {
					$warning = __('ragnarok', 'password-incorrect');
					return false;
				}
				$password     = trim($frm->request->getString('password'));
				$password_r   = trim($frm->request->getString('password_r'));
				if($pgn->server->login->getOption('use-pincode') && $frm->request->getString('pincode')) {
					$pincode     = trim($frm->request->getString('pincode'));
					$pincode_r   = trim($frm->request->getString('pincode_r'));
					if($pgn->server->login->checkValidPincode($pincode, $message) !== Login::FIELD_OK) {
						$frm->field('pincode')->setWarning($pincode, $message);
						return false;
					} else if($pincode !== $pincode_r) {
						$frm->field('pincode_r')->setWarning(__('ragnarok', 'pincode-mismatch'));
						return false;
					}
				}
				if($password) {
					if($pgn->server->login->checkValidPassword($password, $message) !== Login::FIELD_OK) {
						$frm->field('password')->setWarning($message);
						return false;
					} else if($password !== $password_r) {
						$frm->field('password_r')->setWarning(__('ragnarok', 'password-mismatch'));
						return false;
					}
				}
				return true;
			});
			if($frm->status !== Form::VALIDATION_SUCCESS) {
				$this->title = __('ragnarok', 'edit-account', htmlspecialchars($this->account->username));
				$this->theme->head->section = __('ragnarok', 'preferences');
				$tpl = new Template;
				$tpl->set('account', $this->account)
					->set('form', $frm)
					->set('page', $this);
				echo $tpl->render('ragnarok/account/edit');
				return;
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
			return;
		}
		$this->response->status(302)->redirect(App::request()->uri->url());
		try {
			$update = array();
			if($password = trim($this->request->getString('password'))) {
				$update['password'] = $password;
			}
			if($pincode = trim($this->request->getString('pincode'))) {
				$update['pincode'] = $pincode;
			}
			if($this->request->getInt('locked') && !$this->account->isLocked()) {
				$update['state'] = RagnarokAccount::STATE_LOCKED;
			} else if(!$this->request->getInt('locked') && $this->account->isLocked()) {
				$update['state'] = RagnarokAccount::STATE_NORMAL;
			}
			if(!empty($update) && $this->account->update($update)) {
				App::user()->addFlash('success', null, __('ragnarok', 'account-updated'));
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			App::user()->addFlash('error', null, __('application', 'unexpected-error'));
		}
	}

	public function storage_action($charmap_key = '')
	{
		try {
			if($this->server->charmapCount === 1) {
				$charmap = current($this->server->charmap);
			} else if(!($charmap = $this->server->charmap($charmap_key))) {
				$this->error(404);
				return;
			}
			$this->title = __('ragnarok', 'x-storage', htmlspecialchars($this->account->username));
			$this->theme->head->section = __('ragnarok', 'storage');
			$current_page = $this->request->uri->getInt('page', 1, 1);
			$search = $charmap->storageSearch()
				->calcRows(true)
				->where(array( 'account_id' => $this->account->id ))
				->limit(($current_page - 1) * self::$storageItemsPerPage, self::$storageItemsPerPage)
				->order(array( 'name' => 'ASC' ));
			if(($x = $this->request->uri->getString('s', false))) {
				$search->where(array( Search::SEARCH_LIKE, '%' . addcslashes($x, '%_\\') . '%' ));
			}
			if(($x = $this->request->uri->getString('t', false)) !== false) {
				do {
					switch($x) {
						case 'use':    $type = array( Search::SEARCH_IN, 1, 2, 11 ); break;
						case 'misc':   $type = array( Search::SEARCH_IN, 3, 8, 12 ); break;
						case 'weapon': $type = 4; break;
						case 'armor':  $type = 5; break;
						case 'egg':    $type = 7; break;
						case 'card':   $type = 6; break;
						case 'ammo':   $type = 10; break;
						default: break 2;
					}
					$search->where(array( 'type' => $type ));
				} while(0);
			}
			if($x) {
				$search->where(array( 'intentify' => 1 ));
			}
			$search->query();
			$pgn = new Pagination(App::request()->uri, ceil($search->rowsFound / self::$storageItemsPerPage), $current_page, 'page');
			$tpl = new Template;
			$tpl->set('server',       $this->server);
			$tpl->set('charmap',      $charmap);
			$tpl->set('storage',      $search->results);
			$tpl->set('storage_size', $search->rowsFound);
			$tpl->set('paginator',    $pgn);
			$tpl->set('page',         $this);
			echo $tpl->render('ragnarok/account/storage');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function char_action($charmap_key = '')
	{
		$this->theme->head->section = $this->title = __('ragnarok', 'x-characters', $this->account->username);
		try {
			if($this->request->getString('x-change-slot') && ($charmap = $this->server->charmap($this->request->getString('selected-server')))) {
				$this->response->status(302)->redirect(App::request()->uri->url());
				try {
					$characters = $charmap->charSearch()
						->where(array( 'account_id' => $this->account->id ))
						->order(array( 'slot' => 'ASC' ))
						->query()
						->results;
					$new_slots = array();
					$max_slots = ($this->server->login->getOption('use-slots') ? $this->account->slots : (int)$this->server->login->getOption('max-slots', 9));
					foreach($characters as &$char) {
						if(($slot = $this->request->getInt("{$char->id}-slot", false, 1, $max_slots)) === false) {
							App::user()->addFlash('warning', null, __('ragnarok', 'changeslot-missing-char', htmlspecialchars($char->name)));
							return;
						}
						if(isset($new_slots[$slot])) {
							App::user()->addFlash('warning', null, __('ragnarok', 'changeslot-diplicate'));
							return;
						}
						$new_slots[$slot] = &$char;
					}
					foreach($new_slots as $slot => &$char) {
						if($slot !== $char->slot) {
							$char->update(array( 'slot' => $slot ));
						}
					}
					App::user()->addFlash('success', null, __('ragnarok', 'slot-saved'));
				} catch(\Exception $exception) {
					ErrorLog::logSql($exception);
					App::user()->addFlash('error', null, __('application', 'unexpected-error'));
				}
				return;
			}
			if($this->server->charmapCount === 1) {
				$charmap = current($this->server->charmap);
			} else if(!($charmap = $this->server->charmap($charmap_key))) {
				$this->error(404);
				return;
			}
			$characters = $charmap->charSearch()
				->where(array( 'account_id' => $this->account->id ))
				->order(array( 'slot' => 'ASC' ))
				->query()
				->results;
			$tpl = new Template;
			$tpl->set('characters', $characters)
				->set('server', $this->server)
				->set('charmap', $charmap)
				->set('page', $this);
			echo $tpl->render('ragnarok/account/characters');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
			return;
		}
	}

	public function recoverpw_action()
	{
		if(!\Aqua\HTTPS && App::settings()->get('ssl', 0) >= 1) {
			$this->response->status(301)->redirect(App::request()->uri->url(array( 'protocol' => 'https://' )));
			return;
		}
		$user  = App::user();
		try {
			$frm = new Form($this->request);
			$frm->input('password', false)
				->type('password')
				->setLabel(__('ragnarok', 'site-password'))
				->setDescription('Your site account\'s password.');
			$frm->token('ragnarok_reset_pass');
			if(App::settings()->get('captcha')->get('use_recaptcha', false)) {
				$frm->reCaptcha();
			} else {
				$frm->captcha();
			}
			$frm->submit();
			if($frm->message = $user->session->get('ragnarok_pass_reset_warning')) {
				$frm->status = Form::VALIDATION_FAIL;
			} else $frm->validate(function(Form $frm) {
				if(UserAccount::checkCredentials(App::$user->account->username,
				                                 $this->request->getString('password'), $id) !== 0) {
					$frm->field('password')->setWarning(__('ragnarok', 'password-incorrect'));
					return false;
				}
				return true;
			});
			if($frm->status !== Form::VALIDATION_SUCCESS) {
				$this->title = __('ragnarok', 'recover-x-password', htmlspecialchars($this->account->username));
				$this->theme->head->section = __('ragnarok', 'ro-password-recovery');
				$tpl = new Template;
				$tpl->set('form', $frm)
				    ->set('page', $this);
				echo $tpl->render('ragnarok/account/recover_password');
				return;
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
			return;
		}
		try {
			$key = bin2hex(secure_random_bytes(32));
			L10n::getDefault()->email('ragnarok-reset-pw', array(
				'site-title'   => App::settings()->get('title'),
				'site-url'     => \Aqua\URL,
				'ro-username'  => htmlspecialchars($this->account->username),
				'username'     => htmlspecialchars($user->account->username),
				'display-name' => htmlspecialchars($user->account->displayName),
				'email'        => htmlspecialchars($user->account->email),
				'time-now'     => strftime(App::settings()->get('date_format'), ''),
				'key'          => $key,
				'url'          => $this->account->url(array( 'action' => 'resetpass', 'arguments' => array( $key ) ))
			), $title, $content);
			App::user()->session->tmp('ragnarok-pw-reset::' . $this->account->id, $key, 3600 * 2);
			$mailer = ac_mailer(true);
			$mailer->AddAddress($user->account->email, $user->account->displayName);
			$mailer->Body    = $content;
			$mailer->Subject = $title;
			$mailer->isHTML(true);
			if(!$mailer->Send()) {
				throw new PHPMailerException($mailer->ErrorInfo);
			}
			$user->addFlash('success', null, __('ragnarok', 'password-email-sent'));
			$user->session->tmp('ragnarok_pass_reset::' . $this->account->id, $key, 3600);
			$this->response->redirect($this->account->url());
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$user->addFlash('error', null, __('application', 'unexpected-error'));
			$this->response->redirect($user->request->uri->url());
		}
	}

	public function resetpw_action($code = '')
	{
		if(!\Aqua\HTTPS && App::settings()->get('ssl', 0) >= 1) {
			$this->response->status(301)->redirect(App::request()->uri->url(array( 'protocol' => 'https://' )));
			return;
		}
		$user = App::user();
		try {
			if(!$code || $code !== $user->session->get('ragnarok_pass_reset::' . $this->account->id)) {
				$this->response->redirect($this->account->url());
				return;
			}
			$frm = new Form($this->request);
			$frm->input('password')
				->type('password')
				->setLabel(__('ragnarok', 'password'));
			$frm->input('password_r')
				->type('password')
				->setLabel(__('ragnarok', 'password-repeat'));
			if($this->server->login->getOption('use-pincode')) {
				$pincode_len = (int)App::settings()->get('ragnarok')->get('pincode_max_len', 4);
				$frm->input('pincode')
					->type('password')
					->attr('maxlen', $pincode_len)
					->attr('size', $pincode_len)
					->setLabel(__('ragnarok', 'pincode'));
				$frm->input('pincode_r')
					->type('password')
					->attr('maxlen', $pincode_len)
					->attr('size', $pincode_len)
					->setLabel(__('ragnarok', 'pincode-repeat'));
			}
			$frm->submit();
			$pgn = $this;
			$frm->validate(function(Form $frm) use (&$pgn) {
				$password     = trim($frm->request->getString('password'));
				$password_r   = trim($frm->request->getString('password_r'));
				if($pgn->server->login->getOption('use-pincode')) {
					$pincode     = trim($frm->request->getString('pincode'));
					$pincode_r   = trim($frm->request->getString('pincode_r'));
					if($pgn->server->login->checkValidPincode($pincode, $message) !== Login::FIELD_OK) {
						$frm->field('pincode')->setWarning($pincode, $message);
						return false;
					} else if($pincode !== $pincode_r) {
						$frm->field('pincode_r')->setWarning(__('ragnarok', 'pincode-mismatch'));
						return false;
					}
				}
				if($pgn->server->login->checkValidPassword($password, $message) !== Login::FIELD_OK) {
					$frm->field('password')->setWarning($message);
					return false;
				} else if($password !== $password_r) {
					$frm->field('password_r')->setWarning(__('ragnarok', 'password-mismatch'));
					return false;
				}
				return true;
			});
			if($frm->status !== Form::VALIDATION_SUCCESS) {
				$this->theme->head->section = $this->title = __('ragnarok', 'reset-x-password', htmlspecialchars($this->account->username));
				$tpl = new Template;
				$tpl->set('form', $frm)
				    ->set('page', $this);
				echo $tpl->render('ragnarok/account/reset_password');
				return;
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
			return;
		}
		try {
			$this->response->status(302);
			$update = array( 'password' => trim($this->request->getString('password')) );
			if($this->server->login->getOption('use-pincode')) {
				$update['pincode'] = trim($this->request->getString('pincode'));
			}
			if($this->account->update($update)) {
				$user->addFlash('success', null, __('ragnarok', 'password-reset-success'));
				$user->session->delete('ragnarok_pass_reset::' . $this->account->id);
			}
			$this->response->redirect($this->account->url());
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$user->addFlash('error', null, __('application', 'unexpected-error'));
			$this->response->redirect(App::request()->uri->url());
		}
	}
}
