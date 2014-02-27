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

	const STORAGE_ITEMS_PER_PAGE = 10;

	public function run()
	{
		$this->server = App::$activeServer;
		$this->account = App::$activeRagnarokAccount;
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
		$this->title = __('ragnarok', 'edit-account', htmlspecialchars($this->account->username));
		$this->theme->head->section = __('ragnarok', 'preferences');
		if($password = $this->request->getString('confirm_password')) {
			$user = App::user();
			$this->response->status(302)->redirect(App::user()->request->uri->url());
			try {
				if($this->server->login->checkCredentials($this->account->username, $password, $this->request->getString('confirm_pincode', '')) === 0) {
					if($this->server->login->getOption('use-pincode')) {
						$user->addFlash('warning', null, __('ragnarok', 'pass-pin-incorrect'));
					} else {
						$user->addFlash('warning', null, __('ragnarok', 'password-incorrect'));
					}
					return;
				}
				$options = array();
				if($this->server->login->getOption('use-pincode') &&
				   ($new_pincode = $this->request->getString('pincode')) &&
				   ($new_pincode_r = $this->request->getString('pincode_r')) &&
				   $new_pincode !== $this->account->pinCode) {
					$len = strlen($new_pincode);
					if($len < App::settings()->get('ragnarok')->get('pincode_min_len') ||
					   $len > App::settings()->get('ragnarok')->get('pincode_min_len')) {
						$user->addFlash('warning', null, __(
							'ragnarok',
							(App::settings()->get('ragnarok')->get('pincode_max_len') !== App::settings()->get('ragnarok')->get('pincode_min_len') ? 'pincode-len' : 'pincode-len2'),
							App::settings()->get('ragnarok')->get('pincode_min_len'),
							App::settings()->get('ragnarok')->get('pincode_max_len')
						));
						return;
					} else if($new_pincode !== $new_pincode_r) {
						$user->addFlash('warning', null, __('ragnarok', 'pincode-mismatch'));
						return;
					} else if(!ctype_digit($new_pincode)) {
						$user->addFlash('warning', null, __('ragnarok', 'pincode-digit'));
						return;
					}
					$options['pincode'] = $new_pincode;
				}
				if(($new_pass = $this->request->getString('password')) && ($new_pass_r = $this->request->getString('password_r'))) {
					if($new_pass !== $new_pass_r) {
						$user->addFlash('warning', null, __('ragnarok', 'password-mismatch'));
						return;
					} else if($this->server->login->checkValidPassword($password, $message) !== Login::FIELD_OK) {
						$user->addFlash('warning', null, $message);
						return;
					}
					$options['password'] = $new_pass;
				}
				$lock = $this->request->getString('lockdown', false);
				if($lock === 'on' && $this->account->state === RagnarokAccount::STATE_NORMAL) {
					$options['state'] = RagnarokAccount::STATE_LOCKED;
				} else if(!$lock && $this->account->state === RagnarokAccount::STATE_LOCKED) {
					$options['state'] = RagnarokAccount::STATE_NORMAL;
				}
				if(empty($options)) {
					return;
				}
				$this->account->update($options);
				$user->addFlash('success', null, __('ragnarok', 'account-updated'));
				return;
			} catch(\Exception $exception) {
				ErrorLog::logSql($exception);
				$user->addFlash('error', null, __('application', 'unexpected-error'));
				return;
			}
		}
		$tpl = new Template;
		$tpl->set('account', $this->account);
		$tpl->set('page',    $this);
		echo $tpl->render('ragnarok/account/edit');
	}

	public function storage_action($charmap_key = '')
	{
		if($this->server->charmapCount === 1 || !($charmap = $this->server->charmap($charmap_key))) {
			$charmap = current($this->server->charmap);
		}
		$this->title = __('ragnarok', 'x-storage', htmlspecialchars($this->account->username));
		$this->theme->head->section = __('ragnarok', 'storage');
		$options = array( 'account_id' => $this->account->id );
		if(($x = $this->request->uri->getString('s', false))) {
			$x = addcslashes($x, '%_\\');
			$options['name'] = array( AC_SEARCH_LIKE, "%$x%" );
		}
		if(($x = $this->request->uri->getString('t', false)) !== false) {
			switch($x) {
				case 'use':    $options['type'] = array( AC_SEARCH_IN, 1, 2, 11 ); break;
				case 'misc':   $options['type'] = array( AC_SEARCH_IN, 3, 8, 12 ); break;
				case 'weapon': $options['type'] = 4; break;
				case 'armor':  $options['type'] = 5; break;
				case 'egg':    $options['type'] = 7; break;
				case 'card':   $options['type'] = 6; break;
				case 'ammo':   $options['type'] = 10; break;
			}
		}
		if(!empty($options)) $options['identify'] = 1;
		$current_page = $this->request->uri->getInt('page', 1, 1);
		try {
			$storage = $charmap->storageSearch(
				$options,
				array( AC_ORDER_ASC, 'name' ),
				array(($current_page - 1) * self::STORAGE_ITEMS_PER_PAGE, self::STORAGE_ITEMS_PER_PAGE),
				$storage_size
			);
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(1, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
			return;
		}
		$avail_pages = ceil($storage_size / self::STORAGE_ITEMS_PER_PAGE);
		$pgn = new Pagination(App::request()->uri, $avail_pages, $current_page, 'page');
		$tpl = new Template;
		$tpl->set('server',       $this->server);
		$tpl->set('charmap',      $charmap);
		$tpl->set('storage',      $storage);
		$tpl->set('storage_size', $storage_size);
		$tpl->set('paginator',    $pgn);
		$tpl->set('page',         $this);
		echo $tpl->render('ragnarok/account/storage');
	}

	public function char_action($charmap_key = '')
	{
		$this->theme->head->section = $this->title = __('ragnarok', 'x-characters', $this->account->username);
		if($this->request->getString('x-change-slot') && ($charmap = $this->server->charmap($this->request->getString('selected-server')))) {
			$this->response->status(302)->redirect(App::request()->uri->url());
			try {
				$characters = $charmap->charSearch(
					array( 'account_id' => $this->account->id ),
					array( AC_ORDER_ASC, 'slot'),
					false
				);
				$new_slots = array();
				$max_slots = ($this->server->login->getOption('use-slots') ? $this->account->slots : $this->server->login->getOption('max-slots', 9));
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
		try {
			if($this->server->charmapCount === 1 || !($charmap = $this->server->charmap($charmap_key))) {
				$charmap = current($this->server->charmap);
			}
			$characters = $charmap->charSearch(
				array( 'account_id' => $this->account->id ),
				array( AC_ORDER_ASC, 'slot'),
				false
			);
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(1, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
			return;
		}
		$tpl = new Template;
		$tpl->set('characters', $characters)
			->set('server', $this->server)
			->set('charmap', $charmap)
			->set('page', $this);
		echo $tpl->render('ragnarok/account/characters');
	}

	public function recoverpw_action()
	{
		if(!\Aqua\HTTPS && App::settings()->get('ssl', 0) >= 1) {
			$this->response->status(301)->redirect(App::request()->uri->url(array( 'protocol' => 'https://' )));
			return;
		}
		$this->title = __('ragnarok', 'recover-x-password', htmlspecialchars($this->account->username));
		$this->theme->head->section = __('ragnarok', 'ro-password-recovery');
		$user  = App::user();
		$frm = new Form($this->request);
		$frm->input('token', false)
			->type('hidden')
			->value($user->setToken('ragnarok_pass_reset'));
		$frm->input('password', false)
			->type('password')
			->setLabel(__('ragnarok', 'site-password'))
			->setDescription('Your site account\'s password.');
		if(App::settings()->get('captcha')->get('use_recaptcha', false)) {
			$frm->reCaptcha();
		} else {
			$frm->captcha();
		}
		$frm->submit();
		if($frm->message = $user->session->get('ragnarok_pass_reset_warning')) {
			$frm->status = Form::VALIDATION_FAIL;
		} else {
			$frm->validate(function(Request $request, &$message) use($user) {
				if($request->getString('token') !== $user->getToken('ragnarok_pass_reset')) {
					return false;
				}
				return true;
			});
		}
		switch($frm->status) {
			case Form::VALIDATION_FAIL:
				if(!$frm->message) {
					$frm->message.= __('form', 'validation-fail');
				}
			case Form::VALIDATION_INCOMPLETE:
				$tpl = new Template;
				$tpl->set('form', $frm)
					->set('page', $this);
				echo $tpl->render('ragnarok/account/recover_password');
				return;
			case Form::VALIDATION_SUCCESS:
				$this->response->status(302);
				try {
					if(UserAccount::checkCredentials($user->account->username, $this->request->getString('password'), $id) !== 0) {
						$user->session->flash(
							'ragnarok_pass_reset_warning',
							__('ragnarok', 'password-incorrect')
						);
						$this->response->redirect($user->request->uri->url());
						return;
					}
					$key = bin2hex(secure_random_bytes(32));
					L10n::getDefault()->email('ragnarok-resetpw', array(
							'site-title'   => App::settings()->get('title'),
							'site-url'     => \Aqua\URL,
							'ro-username'  => htmlspecialchars($this->account->username),
							'username'     => htmlspecialchars($user->account->username),
							'display-name' => htmlspecialchars($user->account->displayName),
							'email'        => htmlspecialchars($user->account->email),
							'time-now'     => strftime(App::settings()->get('date-format')),
							'key'          => $key,
							'url'          => $this->server->uri->url(array(
									'path' => array( 'a', (App::settings()->get('ragnarok')->get('acc_username_url', false) ?
														   urlencode($this->account->username) : $this->account->id) ),
									'action' => 'resetpass',
									'arguments' => array( $key )
								))
						), $title, $content);
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
	}

	public function resetpw_action($code = '')
	{
		if(!\Aqua\HTTPS && App::settings()->get('ssl', 0) >= 1) {
			$this->response->status(301)->redirect(App::request()->uri->url(array( 'protocol' => 'https://' )));
			return;
		}
		$user = App::user();
		if(!$code || $code !== $user->session->get('ragnarok_pass_reset::' . $this->account->id)) {
			$this->response->redirect($this->account->url());
			return;
		}
		$this->theme->head->section = $this->title = __('ragnarok', 'reset-x-password', htmlspecialchars($this->account->username));
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
		$status = $frm->validate(function(Request $request, &$message, &$key) use (&$pgn) {
			$password     = trim($request->getString('password'));
			$password_r   = trim($request->getString('password_r'));
			$password_len = strlen($password);
			if($pgn->server->login->usePincode) {
				$pincode     = trim($request->getString('pincode'));
				$pincode_r   = trim($request->getString('pincode_r'));
				$pincode_len = strlen($pincode);
				if($pincode_len < Ragnarok::$pincode_min_length || $pincode_len > Ragnarok::$pincode_max_length) {
					$message = __(
						'ragnarok',
						(Ragnarok::$pincode_max_length !== Ragnarok::$pincode_min_length ? 'pincode-len' : 'pincode-len2'),
						Ragnarok::$pincode_min_length,
						Ragnarok::$pincode_max_length
					);
					$key     = 'pincode';
					return false;
				} else if($pincode !== $pincode_r) {
					$message = __('ragnarok', 'pincode-mismatch');
					$key     = 'pincode_r';
					return false;
				} else if(!ctype_digit($pincode)) {
					$message = __('ragnarok', 'pincode-digit');
					$key     = 'pincode';
					return false;
				}
			}
			if($password_len < $pgn->server->login->passwordMinLen || $password_len > $pgn->server->login->passwordMaxLen) {
				$message = __(
					'ragnarok',
					'password-len',
					$this->server->login->passwordMinLen,
					$this->server->login->passwordMaxLen
				);
				$key     = 'password';
				return false;
			} else if($password !== $password_r) {
				$message = __('ragnarok', 'password-mismatch');
				$key     = 'password_r';
				return false;
			} else if($this->server->login->passwordRegex && preg_match_all($this->server->login->passwordRegex, $password_len, $match)) {
				$key     = 'password';
				$match = implode(', ', array_unique($match[0]));
				$message = __('ragnarok', 'password-character', $match);
				return false;
			}
			return true;
		});
		switch($status) {
			case Form::VALIDATION_FAIL:
				if(!$frm->message) {
					$frm->message.= __('form', 'validation-fail');
				}
			case Form::VALIDATION_INCOMPLETE:
				$tpl = new Template;
				$tpl->set('form', $frm)
					->set('page', $this);
				echo $tpl->render('ragnarok/account/reset_password');
				return;
			case Form::VALIDATION_SUCCESS:
				$this->response->status(302);
				try {
					$this->server->login->updateAccount($this->account->id, array(
						'password' => trim($this->request->data('password')),
						'pincode'  => trim($this->request->data('pincode')),
					));
					$user->addFlash('success', null, __('ragnarok', 'password-reset-success'));
					$user->session->delete('ragnarok_pass_reset::' . $this->account->id);
					$this->response->redirect($this->account->url());
				} catch(\Exception $exception) {
					ErrorLog::logSql($exception);
					$user->addFlash('error', null, __('application', 'unexpected-error'));
					$this->response->redirect($user->request->uri->url());
				}
		}
	}
}
