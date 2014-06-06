<?php
namespace Page\Main;

use Aqua\Content\ContentType;
use Aqua\Core\App;
use Aqua\Log\BanLog;
use Aqua\Log\ErrorLog;
use Aqua\Log\LoginLog;
use Aqua\Ragnarok\Server;
use Aqua\Site\Page;
use Aqua\UI\Form;
use Aqua\UI\Menu;
use Aqua\UI\Template;
use Aqua\User\Account as UserAccount;
use Aqua\User\PersistentLogin;
use Aqua\Util\Email;
use Aqua\Util\ImageUploader;
use PHPMailer\PHPMailerException;

class Account
extends Page
{
	public static $usernameLoginAttemptsCaptcha = 10;
	public static $usernameLoginInterval = 10;
	public static $ipLoginAttemptsCaptcha = 3;
	public static $ipLoginAttemptsLockout = 5;
	public static $ipLoginInterval = 15;

	const REGISTRATION_KEY = 'ac_registration_key';
	const RESET_PASS_KEY   = 'ac_reset_cp_password_key';

	public function run()
	{
		$this->response->setHeader('Cache-Control', 'no-store, co-cache, must-revalidate, max-age=0');
		$this->response->setHeader('Expires', time() - 3600);
		$menu     = new Menu;
		$base_url = ac_build_url(array( 'path' => array( 'account' ), 'action' => '' ));
		if(!App::user()->loggedIn()) {
			$menu->append('register', array(
				'title' => __('registration', 'register'),
				'url'   => "{$base_url}register"
			))->append('login', array(
				'title' => __('login', 'login'),
				'url'   => "{$base_url}login"
			))->append('recoverpw', array(
				'title' => __('reset-pw', 'recover-password'),
				'url'   => "{$base_url}recoverpw"
			));
			if(App::settings()->get('account')->get('registration')->get('email_validation', false )) {
				$menu->append('resetcode', array(
					'title' => __('registration', 'reset-code'),
					'url'   => "{$base_url}resetcode"
				));
			}
		} else {
			$menu->append('account', array(
				'title' => __('account', 'my-account'),
				'url'   => "{$base_url}index"
			))->append('editacc', array(
				'title' => __('profile', 'preferences'),
				'url'   => "{$base_url}options"
			))->append('logout', array(
				'title' => __('login', 'logout'),
				'url'   => "{$base_url}logout"
			));
		}
		$this->theme->set('menu', $menu);
	}

	public function index_action()
	{
		if(!App::user()->loggedIn()) {
			$this->response->status(302)->redirect(ac_build_url(array(
					'path'   => array( 'account' ),
					'action' => 'register'
				)));

			return;
		}
		try {
			$account                    = App::user()->account;
			$username                   = htmlspecialchars($account->username);
			$this->title                = __('profile', 'x-account', $username);
			$this->theme->head->section = $username;
			$ragnarok_accounts          = array();
			foreach(Server::$servers as $server) {
				$ragnarok_accounts = array_merge($ragnarok_accounts, $server->login->getAccounts($account));
			}
			reset(Server::$servers);
			$tpl = new Template;
			$tpl->set('account', $account)
			    ->set('ragnarok_accounts', $ragnarok_accounts)
			    ->set('page', $this);
			echo $tpl->render('account/view');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function options_action()
	{
		if(!\Aqua\HTTPS && App::settings()->get('ssl', 0) >= 1) {
			$this->response->status(301)->redirect(App::request()->uri->url(array( 'protocol' => 'https://' )));

			return;
		}
		if(App::user()->account->avatar &&
		   $this->request->uri->getString('token') === App::user()->getToken('account_remove_avatar') &&
		   $this->request->uri->getString('x-action') === 'delete-avatar') {
			$this->response->status(302)->redirect(App::request()->uri->url(array( 'query' => array() )));
			try {
				App::user()->account->removeAvatar(true);
				App::user()->addFlash('success', null, __('profile', 'avatar-removed'));
			} catch(\Exception $exception) {
				ErrorLog::logSql($exception);
				App::user()->addFlash('error', null, __('application', 'unexpected-error'));
			}

			return;
		}
		try {
			$account           = App::user()->account;
			$frm               = new Form($this->request);
			$frm->autocomplete = false;
			$frm->enctype      = 'multipart/form-data';
			$frm->radio('avatar_type')
			    ->value(array(
					'image'    => __('profile', 'use-custom-pic'),
					'gravatar' => __('profile', 'use-gravatar')
				))
			    ->checked('image')
			    ->setLabel(__('profile', 'avatar-type'));
			$frm->file('image')
			    ->attr('accept', 'image/jpeg, image/png, image/gif')
			    ->setLabel(__('profile', 'avatar-file'));
			$frm->input('url')
			    ->type('text')
			    ->attr('autocomplete', 'off')
			    ->setLabel(__('profile', 'avatar-url'));
			$frm->input('gravatar')
			    ->type('text')
			    ->attr('autocomplete', 'off')
			    ->setLabel(__('profile', 'gravatar'))
			    ->setDescription(__('profile', 'gravatar-desc'));
			$frm->input('display_name')
			    ->required()
			    ->type('text')
			    ->attr('autocomplete', 'off')
			    ->value(htmlspecialchars($account->displayName))
			    ->setLabel(__('profile', 'display-name'));
			$frm->input('email')
			    ->required()
			    ->type('email')
			    ->attr('autocomplete', 'off')
			    ->value(htmlspecialchars($account->email))
			    ->setLabel(__('profile', 'email'));
			$frm->input('birthday')
			    ->required()
			    ->type('date')
			    ->attr('autocomplete', 'off')
				->attr('max', date('Y-m-d'))
			    ->value(date('Y-m-d', $account->birthDate))
			    ->placeholder('YYYY-MM-DD')
			    ->setLabel(__('profile', 'birthday'));
			$frm->input('password')
			    ->type('password')
			    ->attr('autocomplete', 'off')
			    ->setLabel(__('profile', 'password'));
			$frm->input('repeat_password')
			    ->type('password')
			    ->attr('autocomplete', 'off')
			    ->setLabel(__('profile', 'repeat-password'));
			$frm->input('current_password')
			    ->required()
			    ->type('password')
			    ->attr('autocomplete', 'off')
			    ->setLabel(__('profile', 'current-password'));
			$frm->token('account_preferences');
			$frm->submit();
			$frm->validate(function (Form $frm) use ($account) {
				$display    = trim($frm->request->getString('display_name'));
				$email      = trim($frm->request->getString('email'));
				$password   = trim($frm->request->getString('password'));
				$password_r = trim($frm->request->getString('repeat_password'));
				$birthday   = trim($frm->request->getString('birthday'));
				$current_pw = trim($frm->request->getString('current_password'));
				$gravatar   = trim($frm->request->getString('gravatar'));
				if(UserAccount::checkCredentials($account->username, $current_pw) !== 0) {
					$frm->field('current_password')->setWarning(__('profile', 'password-incorrect'));

					return false;
				} else if((!empty($password) || !empty($password_r)) && $password !== $password_r) {
					$frm->field('repeat_password')->setWarning(__('profile', 'password-mismatch'));

					return false;
				} else if(UserAccount::checkValidDisplayName($display, $warning) !== UserAccount::FIELD_OK) {
					$frm->field('display_name')->setWarning($warning);

					return false;
				} else if(UserAccount::checkValidEmail($email, $warning) !== UserAccount::FIELD_OK) {
					$frm->field('email')->setWarning($warning);

					return false;
				} else if(UserAccount::checkValidBirthday(strtotime($birthday), $warning) !== UserAccount::FIELD_OK) {
					$frm->field('birthday')->setWarning($warning);

					return false;
				} else if(!empty($password) &&
				          UserAccount::checkValidPassword($password, $warning) !== UserAccount::FIELD_OK) {
					$frm->field('password')->setWarning($warning);

					return false;
				} else if($frm->request->getString('avatar_type') === 'gravatar' && !empty($gravatar) &&
				          !filter_var($gravatar, FILTER_VALIDATE_EMAIL)) {
					$frm->field('gravatar')->setWarning(__('form', 'invalid-email'));

					return false;
				} else {
					switch(UserAccount::exists(null, $display, $email, $account->id)) {
						case UserAccount::REGISTRATION_DISPLAY_NAME_TAKEN:
							$frm->field('display_name')->setWarning(__('profile', 'display-name-taken'));

							return false;
						case UserAccount::REGISTRATION_EMAIL_TAKEN:
							$frm->field('email')->setWarning(__('profile', 'email-taken'));

							return false;
						case 0:
							break;
						default:
							return false;
					}
				}

				return true;
			});
			if($frm->status !== Form::VALIDATION_SUCCESS) {
				$this->theme->head->section = $this->title = __('profile', 'edit-account');
				$tpl = new Template;
				$tpl->set('account', $account)
				    ->set('form', $frm)
				    ->set('page', $this)
				    ->set('token', App::user()->setToken('account_remove_avatar', 16));
				echo $tpl->render('account/edit');

				return;
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));

			return;
		}
		$this->response->status(302)->redirect(App::request()->uri->url());
		try {
			$updated  = 0;
			$uploader = new ImageUploader;
			$uploader->maxSize(ac_size(App::settings()->get('account')->get('avatar')->get('max_size', "100KB")))
				->dimension(App::settings()->get('account')->get('avatar')->get('max_width', 100),
				            App::settings()->get('account')->get('avatar')->get('max_height', 100));
			$display  = trim($this->request->getString('display_name'));
			$email    = trim($this->request->getString('email'));
			$password = trim($this->request->getString('password'));
			$birthday = \DateTime::createFromFormat('Y-m-d', trim($this->request->getString('birthday')));
			$birthday = strtotime('midnight', $birthday->getTimestamp());
			if($display !== $account->displayName) {
				switch($account->updateDisplayName($display)) {
					case 0:
						App::user()->addFlash('warning', null, __(
							'profile',
							'display-name-update-limit',
							App::settings()->get('account')->get('display_name')->get('update_limit', 3),
							App::settings()->get('account')->get('display_name')->get('update_days', 30)
						));
						break;
					case 1:
						++$updated;
						break;
				}
			}
			if($email !== $account->email) {
				switch($account->updateEmail($email)) {
					case 0:
						App::user()->addFlash('warning', null, __(
							'profile',
							'email-update-limit',
							App::settings()->get('account')->get('email')->get('update_limit', 3),
							App::settings()->get('account')->get('email')->get('update_days', 30)
						));
						break;
					case 1:
						++$updated;
						break;
				}
			}
			if(date('Y-m-d', $birthday) !== date('Y-m-d', $account->birthDate)) {
				switch($account->updateBirthday($birthday)) {
					case 0:
						App::user()->addFlash('warning', null, __(
							'profile',
							'birthday-update-limit',
							App::settings()->get('account')->get('birthday')->get('update_limit', 3),
							App::settings()->get('account')->get('birthday')->get('update_days', 30)
						));
						break;
					case 1:
						++$updated;
						break;
				}
			}
			if(!empty($password)) {
				switch($account->updatePassword($password)) {
					case 0:
						App::user()->addFlash('warning', null, __(
							'profile',
							'password-update-limit',
							App::settings()->get('account')->get('password')->get('update_limit', 3),
							App::settings()->get('account')->get('password')->get('update_days', 30)
						));
						break;
					case 1:
						++$updated;
						break;
				}
			}
			switch($this->request->getString('avatar_type')) {
				case 'image':
					if(ac_file_uploaded('image')) {
						$original = $_FILES['image']['tmp_name'];
						$uploader->uploadLocal($_FILES['image']['tmp_name'], $_FILES['image']['name']);
					} else if($url = $this->request->getString('url')) {
						$original = $url;
						$uploader->uploadRemote($url);
					} else {
						break;
					}
					if($uploader->error !== ImageUploader::UPLOAD_OK ||
					   !($path = $uploader->save('/uploads/avatar', uniqid($account->id . '-')))) {
						App::user()->addFlash('error', null, $uploader->errorStr());
					} else {
						$account->setAvatar($path, $original);
						++$updated;
					}
					break;
				case 'gravatar':
					$account->setGravatar(trim($this->request->getString('gravatar')));
					++$updated;
					break;
			}
			if($updated) {
				App::user()->addFlash('success', null, __('profile', 'account-updated'));
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			App::user()->addFlash('error', null, __('application', 'unexpected-error'));
		}
	}

	public function register_action()
	{
		if(!\Aqua\HTTPS && App::settings()->get('ssl', 0) >= 1) {
			$this->response->status(301)->redirect(App::request()->uri->url(array( 'protocol' => 'https://' )));

			return;
		}
		$settings = App::settings()->get('account')->get('registration');
		try {
			if($settings->get('multi_page', false)) {
				$multi_page = false;
			} else {
				$tos = ContentType::getContentType(ContentType::CTYPE_PAGE)->get('tos', 'slug');
				$multi_page = (bool)$tos->content;
			}
			if($multi_page && $settings->get('require_tos', false) &&
			   $this->request->method !== 'POST' &&
			   !App::user()->session->get('agreed-tos')) {
				if($this->request->method === 'POST' && $this->request->getInt('agree-tos') === 1) {
					App::user()->session->set('agreed-tos', true);
				} else {
					$this->theme->head->section = $this->title = __('registration', 'register');
					$tpl = new Template;
					$tpl->set('tos', $tos)
						->set('page', $this);
					echo $tpl->render('account/agree-tos');

					return;
				}
			}
			$user              = App::user();
			$frm               = new Form($this->request);
			$frm->autocomplete = false;
			$frm->input('username', true)
			    ->required()
			    ->type('text')
			    ->attr('autocomplete', 'off')
			    ->setLabel(__('profile', 'username'));
			$frm->input('display_name', true)
			    ->required()
			    ->type('text')
			    ->attr('autocomplete', 'off')
			    ->setLabel(__('profile', 'display-name'));
			$frm->input('password', false)
			    ->required()
			    ->type('password')
			    ->attr('autocomplete', 'off')
			    ->setLabel(__('profile', 'password'));
			$frm->input('repeat_password', false)
			    ->required()
			    ->type('password')
			    ->attr('autocomplete', 'off')
			    ->setLabel(__('profile', 'repeat-password'));
			$frm->input('email', true)
			    ->required()
			    ->type('email')
			    ->attr('autocomplete', 'off')
			    ->setLabel(__('profile', 'email'));
			$frm->input('birthday', true)
			    ->required()
			    ->type('date')
			    ->placeholder('YYYY-MM-DD')
			    ->attr('autocomplete', 'off')
			    ->setLabel(__('profile', 'birthday'));
			if($settings->get('captcha_confirmation', false)) {
				if(App::settings()->get('captcha')->get('use_recaptcha', false)) {
					$frm->reCaptcha();
				} else {
					$frm->captcha();
				}
			}
			if(!$multi_page && $settings->get('require_tos', false)) {
				$frm->checkbox('tos')
					->value(array( '1' => '' ))
					->required()
					->setLabel(__('registration',
					              'agree-tos-desc',
					              ac_build_url(array( 'path' => array( 'page', 'tos' ) ))));
			}
			$frm->token('account_registration_token');
			$frm->submit();
			if($frm->message = $user->session->get('ac_account_registration_warning')) {
				$frm->status = Form::VALIDATION_FAIL;
			} else {
				$frm->validate(function (Form $frm) use ($settings, $user, $multi_page) {
					$email     = trim($frm->request->getString('email'));
					$username  = trim($frm->request->getString('username'));
					$password  = trim($frm->request->getString('password'));
					$password2 = trim($frm->request->getString('repeat_password'));
					$birthday  = trim($frm->request->getString('birthday'));
					$display   = trim($frm->request->getString('display_name'));
					if(!($date = \DateTime::createFromFormat('Y-m-d', $birthday))) {
						$frm->field('username')->setWarning(__('form', 'invalid-date'));

						return false;
					} else if(UserAccount::checkValidUsername($username, $message) !== UserAccount::FIELD_OK) {
						$frm->field('username')->setWarning($message);

						return false;
					} else if(UserAccount::checkValidEmail($email, $message) !== UserAccount::FIELD_OK) {
						$frm->field('email')->setWarning($message);

						return false;
					} else if(UserAccount::checkValidDisplayName($display, $message) !== UserAccount::FIELD_OK) {
						$frm->field('display_name')->setWarning($message);

						return false;
					} else if(UserAccount::checkValidBirthday($date->getTimestamp(), $message) !== UserAccount::FIELD_OK) {
						$frm->field('birth_date')->setWarning($message);

						return false;
					} else if(UserAccount::checkValidPassword($password, $message) !== UserAccount::FIELD_OK) {
						$frm->field('password')->setWarning($message);

						return false;
					} else if($password !== $password2) {
						$frm->field('repeat_password')->setWarning(__('profile', 'password-mismatch'));

						return false;
					} else if(!$multi_page && $settings->get('require_tos', false) && !$frm->request->getInt('tos')) {
						$frm->field('repeat_password')->setWarning(__('registration', 'tos-required'));

						return false;
					} else if(($x = UserAccount::exists($username, $display, $email)) !== 0) {
						switch($x) {
							case UserAccount::REGISTRATION_USERNAME_TAKEN:
								$frm->field('username')->setWarning(__('profile', 'username-taken'));
								break;
							case UserAccount::REGISTRATION_DISPLAY_NAME_TAKEN:
								$frm->field('display_name')->setWarning(__('profile', 'display-name-taken'));
								break;
							case UserAccount::REGISTRATION_EMAIL_TAKEN:
								$frm->field('email')->setWarning(__('profile', 'email-taken'));
								break;
						}
						return false;
					}

					return true;
				});
			}
			if($frm->status !== Form::VALIDATION_SUCCESS) {
				$this->theme->head->section = $this->title = __('registration', 'register');
				$tpl = new Template;
				$tpl->set('form', $frm)
				    ->set('page', $this);
				echo $tpl->render('account/register');

				return;
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));

			return;
		}
		$this->response->status(302)->redirect(App::request()->uri->url());
		$username = trim($this->request->getString('username'));
		$password = trim($this->request->getString('password'));
		$display  = trim($this->request->getString('display_name'));
		$email    = trim(strtolower($this->request->getString('email')));
		try {
			$account = UserAccount::register(
				$username,
				$display,
				$password,
				$email,
				\DateTime::createFromFormat('Y-m-d', $this->request->getString('birthday'))->getTimestamp(),
				null,
				($settings->get('email_validation', false) ?
					UserAccount::STATUS_AWAITING_VALIDATION :
					UserAccount::STATUS_NORMAL),
				($multi_page ?
					App::user()->session->get('agreed-tos', false) :
					(bool)$this->request->getInt('tos'))
			);
			if(!$account instanceof UserAccount) {
				App::user()
				   ->addFlash('error', __('registration', 'failed-to-register'), __('application', 'unexpected-error'));

				return;
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			App::user()
			   ->addFlash('error', __('registration', 'failed-to-register'), __('application', 'unexpected-error'));

			return;
		}
		if($settings->get('email_validation', false)) {
			try {
				$key = bin2hex(secure_random_bytes(32));
				$account->meta->set(self::REGISTRATION_KEY, $key);
				$this->sendValidationEmail($account, $key);
				$user->addFlash('success', __('registration', 'completed'), __('registration', 'email-sent'));
			} catch(\Exception $exception) {
				ErrorLog::logSql($exception);
				$user->addFlash('error', __('registration', 'email-failed'), __('registration', 'email-not-sent'));
				$this->response->redirect(ac_build_url(array( 'path' => array( 'account' ), 'action' => 'resetcode' )));

				return;
			}
		} else {
			$user->addFlash('success', __('registration', 'completed'), __('registration', 'registered'));
			$user->login($account);
			if($account->spamFilter()) {
				$user->addFlash('warning', null, __('registration', 'flagged'));
			}
		}
		$this->response->redirect(\Aqua\URL);
	}

	public function activate_action($username = '', $key = '')
	{
		try {
			$account = App::user()->account;
			if(!$username || !$key ||
			   ($account && $account->username !== $username) ||
			   // Account doesn't exist
			   !($account = UserAccount::get($username, 'username')) ||
			   // Not awaiting validation
			   $account->status !== UserAccount::STATUS_AWAITING_VALIDATION ||
			   // Validation key expired
			   $account->registrationDate < (time() - ((int)App::settings()
						                                       ->get('account')
						                                       ->get('registration')
			                                                   ->get('validation_time', 48) * 3600)) ||
			   // Wrong validation key
			   $account->meta->get(self::REGISTRATION_KEY) !== $key) {
				$this->error(404);
				return;
			}
			$account->meta->delete(self::REGISTRATION_KEY);
			$account->update(array( 'status' => UserAccount::STATUS_NORMAL ));
			App::user()->addFlash('success', null, __('registration', 'account-activated'));
			if($account->spamFilter()) {
				App::user()->addFlash('warning', null, __('registration', 'flagged'));
			}
			$this->response->status(302)->redirect(\Aqua\URL);
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function resetcode_action()
	{
		if(!App::settings()->get('account')->get('registration')->get('email_validation', false )) {
			$this->error(404);

			return;
		}
		if(!\Aqua\HTTPS && App::settings()->get('ssl', 0) >= 1) {
			$this->response->status(301)->redirect(App::request()->uri->url(array( 'protocol' => 'https://' )));

			return;
		}
		try {
			$settings          = App::settings()->get('account')->get('registration');
			$frm               = new Form($this->request);
			$frm->autocomplete = false;
			$frm->input('email')
			    ->type('text')
			    ->attr('autocomplete', 'off')
			    ->setLabel(__('profile', 'email'));
			$frm->append('<div class="ac-separator-wrapper"><div class="ac-separator"><span>' .
			             __('application', 'or') . '</span></div></div>');
			$frm->input('username')
			    ->type('text')
			    ->attr('autocomplete', 'off')
			    ->setLabel(__('profile', 'username'));
			if(App::settings()->get('captcha')->get('use_recaptcha', false)) {
				$frm->reCaptcha();
			} else {
				$frm->captcha();
			}
			$frm->submit();
			$frm->validate(function (Form $frm, &$message) use ($settings) {
				$email    = trim($frm->request->getString('email'));
				$username = trim($frm->request->getString('username'));
				if(empty($email) && empty($username)) {
					$message = __('form', 'any-field-required');

					return false;
				}
				if(!empty($email)) {
					if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
						$frm->field('email')->setWarning(__('form', 'invalid-email'));

						return false;
					}
					$account = UserAccount::get($email, 'email');
				} else {
					$account = UserAccount::get($username, 'username');
				}
				if(!$account || $account->status !== UserAccount::STATUS_AWAITING_VALIDATION ||
				   $account->registrationDate < (time() - ((int)$settings->get('validation_time', 48) * 3600))) {
					$message = __('account', 'account-not-found');

					return false;
				}

				return true;
			});
			if($frm->status !== Form::VALIDATION_SUCCESS) {
				$this->title = $this->theme->head->section = __('registration', 'reset-code');
				$tpl         = new Template;
				$tpl->set('form', $frm)
				    ->set('page', $this);
				echo $tpl->render('account/reset-code');

				return;
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));

			return;
		}
		$this->response->status(302)->redirect(App::request()->uri->url());
		try {
			$email    = trim($this->request->getString('email'));
			$username = trim($this->request->getString('username'));
			if($email) {
				$account = UserAccount::get($email, 'email');
			} else {
				$account = UserAccount::get($username, 'username');
			}
			if(!($key = $account->meta->get(self::REGISTRATION_KEY))) {
				$key = bin2hex(secure_random_bytes(32));
				$account->meta->set(self::REGISTRATION_KEY, $key);
			}
			$this->sendValidationEmail($account, $key);
			$host = strrchr($account->email, '@');
			$name = substr($account->email, 0, -strlen($host));
			$count = ceil((strlen($name) / 100) * 25);
			$name = substr($name, 0, $count) . str_repeat('*', max(0, strlen($name) - $count));
			App::user()->addFlash('success', null, __('registration', 'email-resent', "$name$host"));
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			App::user()->addFlash('error', null, __('application', 'unexpected-error'));
		}
	}

	public function recoverpw_action()
	{
		if(!\Aqua\HTTPS && App::settings()->get('ssl', 0) >= 1) {
			$this->response->status(301)->redirect(App::request()->uri->url(array( 'protocol' => 'https://' )));

			return;
		}
		try {
			$frm               = new Form($this->request);
			$frm->autocomplete = false;
			$frm->input('email')
			    ->type('text')
			    ->attr('autocomplete', 'off')
			    ->setLabel(__('profile', 'email'));
			$frm->append('<div class="ac-separator-wrapper"><div class="ac-separator"><span>' .
			             __('application', 'or') . '</span></div></div>');
			$frm->input('username')
			    ->type('text')
			    ->attr('autocomplete', 'off')
			    ->setLabel(__('profile', 'username'));
			if(App::settings()->get('captcha')->get('use_recaptcha', false)) {
				$frm->reCaptcha();
			} else {
				$frm->captcha();
			}
			$frm->validate(function (Form $frm, &$message) {
				$email    = trim($frm->request->getString('email'));
				$username = trim($frm->request->getString('username'));
				if(empty($email) && empty($username)) {
					$message = __('form', 'any-field-required');

					return false;
				}
				if(!empty($email)) {
					if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
						$frm->field('email')->setWarning(__('form', 'invalid-email'));

						return false;
					}
					$account = UserAccount::get($email, 'email');
				} else {
					$account = UserAccount::get($username, 'username');
				}
				if(!$account || $account->status !== UserAccount::STATUS_NORMAL) {
					$message = __('account', 'account-not-found');

					return false;
				}

				return true;
			});
			if($frm->status !== Form::VALIDATION_SUCCESS) {
				$this->title = $this->theme->head->section = __('reset-pw', 'recover-password');
				$tpl         = new Template;
				$tpl->set('form', $frm)
				    ->set('page', $this);
				echo $tpl->render('account/recover-pw');

				return;
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));

			return;
		}
		$this->response->status(302)->redirect(App::request()->uri->url());
		try {
			$email    = trim($frm->request->getString('email'));
			$username = trim($frm->request->getString('username'));
			if(!empty($email)) {
				$account = UserAccount::get($email, 'email');
			} else {
				$account = UserAccount::get($username, 'username');
			}
			$key = bin2hex(secure_random_bytes(64));
			App::user()->session->tmp('password_reset', array( $account->id, $key ), 3600 * 2);
			$email = Email::fromTemplate('reset-pw')
				->isHtml(true)
				->replace(array(
					'site-title'   => htmlspecialchars(App::settings()->get('title')),
					'site-url'     => \Aqua\URL,
					'username'     => htmlspecialchars($account->username),
					'display-name' => htmlspecialchars($account->displayName),
					'email'        => htmlspecialchars($account->email),
					'time-now'     => strftime(App::settings()->get('date_format', ''), time()),
					'time-left'    => 2,
					'key'          => $key,
					'url'          => ac_build_url(array(
						'path'      => array( 'account' ),
						'action'    => 'resetpw',
						'arguments' => array( $key )
					))
				))
				->addAddress($account);
			if(!$email->send()) {
				throw new PHPMailerException(Email::phpMailer()->ErrorInfo);
			}
			$host  = strrchr($account->email, '@');
			$name  = substr($account->email, 0, -strlen($host));
			$count = (strlen($name) / 100) * 25;
			$name  = substr($name, 0, $count) . str_repeat('*', min(0, strlen($name) - $count));
			App::user()->addFlash('success', null, __('reset-pw', 'email-sent', "$name$host"));
			$this->response->redirect(\Aqua\URL);
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			App::user()->addFlash('error', null, __('application', 'unexpected-error'));
		}
	}

	public function resetpw_action($key = '')
	{
		if(!\Aqua\HTTPS && App::settings()->get('ssl', 0) >= 1) {
			$this->response->status(301)->redirect(App::request()->uri->url(array( 'protocol' => 'https://' )));

			return;
		}
		try {
			if(empty($key) || !ctype_xdigit($key)) {
				$this->error(404);

				return;
			}
			list($accountId, $resetKey) = App::user()->session->get('password_reset', array( null, null ));
			if(!$accountId || !$resetKey || $key !== $resetKey || !($account = UserAccount::get($accountId))) {
				$this->error(404);

				return;
			}
			$settings = App::settings()->get('account')->get('registration');
			$frm               = new Form($this->request);
			$frm->autocomplete = false;
			$frm->input('password')
			    ->required()
			    ->type('password')
			    ->attr('autocomplete', 'off')
			    ->setLabel(__('profile', 'password'));
			$frm->input('repeat_password')
			    ->required()
			    ->type('password')
			    ->attr('autocomplete', 'off')
			    ->setLabel(__('profile', 'repeat-password'));
			$frm->token('account_password_reset');
			$frm->submit();
			$frm->validate(function (Form $frm) use ($settings) {
				$password  = trim($frm->request->getString('password'));
				$password2 = trim($frm->request->getString('repeat_password'));
				if($password !== $password2) {
					$frm->field('repeat_password')->setWarning(__('profile', 'password-mismatch'));

					return false;
				} else if(UserAccount::checkValidPassword($password, $warning) !== UserAccount::FIELD_OK) {
					$frm->field('password')->setWarning($warning);

					return false;
				}

				return true;
			});
			if($frm->status !== Form::VALIDATION_SUCCESS) {
				$this->title = $this->theme->head->section = __('reset-pw', 'reset-x-password',
				                                                htmlspecialchars($account->username));
				$tpl         = new Template;
				$tpl->set('form', $frm)
				    ->set('page', $this);
				echo $tpl->render('account/recover-pw');

				return;
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));

			return;
		}
		$this->response->status(302);
		try {
			$account->updatePassword(trim($this->request->getString('password')), true);
			App::user()->addFlash('success', null, __('reset-pw', 'success'))->session->delete('password_reset');
			$this->response->redirect(\Aqua\URL);
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			App::user()->addFlash('error', null, __('application', 'unexpected-error'));
			$this->response->redirect(App::request()->uri->url());
		}
	}

	public function login_action()
	{
		if(!\Aqua\HTTPS && App::settings()->get('ssl', 0) >= 1) {
			$this->response->status(301)->redirect(App::request()->uri->url(array( 'protocol' => 'https://' )));

			return;
		}
		$frm = new Form($this->request);
		$frm->input('username', true)
		    ->required()
		    ->type('text')
		    ->setLabel(__('profile', 'username'));
		$frm->input('password', false)
		    ->required()
		    ->type('password')
		    ->setLabel(__('profile', 'password'));
		if(App::user()->session->get('ac_login_captcha')) {
			if(App::settings()->get('captcha')->get('use_recaptcha', false)) {
				$frm->reCaptcha();
			} else {
				$frm->captcha();
			}
		}
		if(App::settings()->get('account')->get('persistent_login')->get('enable', false)) {
			$frm->checkbox('remember_me')
			    ->value(array( '1' => '' ))
			    ->setLabel(__('login', 'remember-me'))
			    ->setDescription(__('login', 'remember-me-warning'));
		}
		$frm->token('account_login');
		$frm->submit();
		if($frm->message = App::user()->session->get('ac_login_warning', null)) {
			$frm->status = Form::VALIDATION_FAIL;
		} else $frm->validate();
		if($frm->status !== Form::VALIDATION_SUCCESS) {
			$this->title = $this->theme->head->section = __('login', 'login');
			$tpl         = new Template;
			$tpl->set('form', $frm)
			    ->set('page', $this);
			echo $tpl->render('account/login');

			return;
		}
		$this->response->status(302)->redirect(App::request()->uri->url());
		try {
			$username         = trim($this->request->getString('username'));
			$password         = trim($this->request->getString('password'));
			$ipAttempts       = LoginLog::attempts(App::request()->ipString, self::$ipLoginInterval, 'ip_address');
			$usernameAttempts = LoginLog::attempts($username, self::$usernameLoginInterval, 'username');
			if($ipAttempts >= self::$ipLoginAttemptsLockout) {
				App::user()->session
					->delete('ac_login_captcha')
					->flash('ac_login_warning', __('login', 'attempt-wait'));

				return;
			} else if($ipAttempts >= self::$ipLoginAttemptsCaptcha ||
			          $usernameAttempts >= self::$usernameLoginAttemptsCaptcha) {
				if(!App::user()->session->get('ac_login_captcha')) {
					App::user()->session
						->set('ac_login_captcha', true)
						->flash('ac_login_warning', __('login', 'attempt-captcha'));

					return;
				}
			} else if(App::user()->session->get('ac_login_captcha')) {
				App::user()->session->delete('ac_login_captcha');
			}
			switch(UserAccount::checkCredentials($username, $password, $account_id)) {
				case UserAccount::INVALID_CREDENTIALS:
					LoginLog::logSql($username,
					                 $account_id,
					                 LoginLog::TYPE_NORMAL,
					                 LoginLog::STATUS_INVALID_CREDENTIALS);
					App::user()->session->flash('ac_login_warning', __('login', 'invalid-credentials'));
					break;
				case UserAccount::STATUS_BANNED:
					LoginLog::logSql($username,
					                 $account_id,
					                 LoginLog::TYPE_NORMAL,
					                 LoginLog::STATUS_ACCESS_DENIED);
					$search = BanLog::search()
					                ->where(array( 'banned_user_id' => $account_id ))
					                ->order(array( 'ban_date' => 'DESC' ))
					                ->limit(1)
					                ->query();
					if($search->valid()) {
						$message = __('login', 'account-banned-reason', htmlspecialchars($search->current()->reason));
					} else {
						$message = __('login', 'account-banned');
					}
					App::user()->session->flash('ac_login_warning', $message);
					break;
				case UserAccount::STATUS_SUSPENDED:
					LoginLog::logSql($username,
					                 $account_id,
					                 LoginLog::TYPE_NORMAL,
					                 LoginLog::STATUS_ACCESS_DENIED);
					$search = BanLog::search()
					                ->where(array( 'banned_user_id' => $account_id ))
					                ->order(array( 'ban_date' => 'DESC' ))
					                ->limit(1)
					                ->query();
					if($search->valid()) {
						$message = __('login', 'account-suspended-reason',
						              $search->current()->unbanDate(App::settings()->get('datetime_format')),
						              htmlspecialchars($search->current()->reason));
					} else {
						$message = __('login', 'account-suspended');
					}
					App::user()->session->flash('ac_login_warning', $message);
					break;
				case 0:
					LoginLog::logSql($username,
					                 $account_id,
					                 LoginLog::TYPE_NORMAL,
					                 LoginLog::STATUS_OK);
					$pl_settings = App::settings()->get('account')->get('persistent_login');
					$account     = UserAccount::get($account_id);
					App::user()
					   ->login($account)
					   ->addFlash('success', null, __('login', 'logged-in', htmlspecialchars($account->username)));
					if($pl_settings->get('enable', false) && $this->request->getInt('remember_me')) {
						$name = $pl_settings->get('name', '');
						if($key = $this->request->cookie($name)) {
							PersistentLogin::delete($key);
						}
						$this->response->setCookie($name, array(
							'value'     => PersistentLogin::create($account_id),
							'ttl'       => 315360000,
							'http_only' => (bool)$pl_settings->get('http_only', true),
							'secure'    => (bool)$pl_settings->get('secure', false)
						));
					}
					if($url = $this->request->getString('return_url')) {
						$this->response->redirect($url);
					} else {
						$this->response->redirect(\Aqua\URL);
					}
					App::user()->session->flash('403-redirect', true);
					App::user()->session->delete('ac_login_captcha');
					break;
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			App::user()->addFlash('error', null, __('application', 'unexpected-error'));
		}
	}

	public function logout_action()
	{
		App::user()
		   ->logout()
		   ->addFlash('notification', null, __('login', 'logged-out'));
		$this->response->status(302)->redirect(\Aqua\URL);
	}

	public function sendValidationEmail(UserAccount $account, $key)
	{
		$timeLeft = floor((time() / 3600) - ($account->registrationDate / 3600));
		$timeLeft = App::settings()->get('account')->get('registration')->get('validation_time', 48) - $timeLeft;
		$email = Email::fromTemplate('registration')
			->isHtml(true)
			->replace(array(
				'site-title'   => App::settings()->get('title'),
				'site-url'     => \Aqua\URL,
				'username'     => htmlspecialchars($account->username),
				'display-name' => htmlspecialchars($account->displayName),
				'email'        => htmlspecialchars($account->email),
				'time-now'     => strftime(App::settings()->get('date_format', '')),
				'time-left'    => $timeLeft,
				'key'          => $key,
				'url'          => ac_build_url(array(
					'path'      => array( 'account' ),
					'action'    => 'activate',
					'arguments' => array( $account->username, $key )
				))
			))
			->addAddress($account);
		if(!$email->send()) {
			throw new PHPMailerException(Email::phpMailer()->ErrorInfo);
		}
	}
}
