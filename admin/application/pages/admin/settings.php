<?php
namespace Page\Admin;

use Aqua\Content\ContentType;
use Aqua\Core\App;
use Aqua\Log\ErrorLog;
use Aqua\Captcha\ReCaptcha;
use Aqua\Site\Page;
use Aqua\UI\Form;
use Aqua\UI\Tag;
use Aqua\UI\Template;
use Aqua\Util\ImageUploader;
use CharGen\CharacterRender;
use CharGen\RORender;

class Settings
extends Page
{
	public function index_action()
	{
		try {
			$settings = App::settings();
			$frm = new Form($this->request);
			$frm->input('title')
		        ->type('text')
				->value(htmlspecialchars($settings->get('title', '')))
		        ->setLabel(__('settings', 'app-title-label'));
			$frm->input('domain')
		        ->type('text')
				->required()
				->value(htmlspecialchars($settings->get('domain', '')))
		        ->setLabel(__('settings', 'app-domain-label'));
			$frm->input('base_dir')
		        ->type('text')
				->attr('pattern', '/[a-zA-Z0-9\+\-\_\=\~\.\!\$\&\\\'\(\)\*\,\;\:\@]*/')
				->value(htmlspecialchars($settings->get('base_dir', '')))
		        ->setLabel(__('settings', 'app-base-dir-label'));
			$frm->checkbox('rewrite_url')
				->value(array( '1' => '' ))
				->checked(($settings->get('rewrite_url', false) ? '1' : null))
			    ->setLabel(__('settings', 'app-rewrite-label'))
			    ->setDescription(__('settings', 'app-rewrite-desc'));
			$frm->checkbox('ob')
				->value(array( '1' => '' ))
				->checked(($settings->get('output_compression', false) ? '1' : null))
			    ->setLabel(__('settings', 'app-ob-label'))
			    ->setDescription(__('settings', 'app-ob-desc'));
			$frm->checkbox('tasks')
				->value(array( '1' => '' ))
				->checked(($settings->get('tasks', false) ? '1' : null))
			    ->setLabel(__('settings', 'app-task-label'))
			    ->setDescription(__('settings', 'app-task-desc'));
			$frm->select('ssl')
		        ->value(array(
						'0' => __('settings', 'app-ssl-0'),
						'1' => __('settings', 'app-ssl-1'),
						'2' => __('settings', 'app-ssl-2'),
					))
			    ->selected($settings->get('ssl', 0))
			    ->setLabel(__('settings', 'app-ssl-label'))
			    ->setDescription(__('settings', 'app-ssl-desc'));
			$frm->input('timezone')
				->type('text')
				->value(htmlspecialchars($settings->get('timezone', '')))
				->setLabel(__('settings', 'app-timezone-label'))
				->setDescription(__('settings', 'app-timezone-desc'));
			$frm->input('date_format')
				->type('text')
			    ->value(htmlspecialchars($settings->get('date_format', '')))
			    ->setLabel(__('settings', 'app-date-format-label'))
			    ->setDescription(__('settings', 'app-date-format-desc'));
			$frm->input('time_format')
				->type('text')
			    ->value(htmlspecialchars($settings->get('time_format', '')))
			    ->setLabel(__('settings', 'app-time-format-label'))
			    ->setDescription(__('settings', 'app-date-format-desc'));
			$frm->input('datetime_format')
				->type('text')
			    ->value(htmlspecialchars($settings->get('datetime_format', '')))
			    ->setLabel(__('settings', 'app-datetime-format-label'))
			    ->setDescription(__('settings', 'app-date-format-desc'));
			$frm->validate(function(Form $frm) {
					try {
						strftime(trim($frm->request->getString('date_format')));
					} catch(\Exception $e) {
						$frm->field('date_format')
							->setWarning(__('settings',
							                'invalid-date-format',
							                htmlspecialchars($frm->request->getString('date_format'))));
						return false;
					}
					try {
						strftime(trim($frm->request->getString('time_format')));
					} catch(\Exception $e) {
						$frm->field('time_format')
							->setWarning(__('settings',
							                'invalid-date-format',
							                htmlspecialchars($frm->request->getString('time_format'))));
						return false;
					}
					try {
						strftime(trim($frm->request->getString('datetime_format')));
					} catch(\Exception $e) {
						$frm->field('datetime_format')
							->setWarning(__('settings',
							                'invalid-date-format',
							                htmlspecialchars($frm->request->getString('datetime_format'))));
						return false;
					}
					try {
						$tz = trim($frm->request->getString('timezone'));
						if(!empty($tz)) {
							new \DateTimeZone($tz);
						}
					} catch(\Exception $e) {
						$frm->field('timezone')
							->setWarning(__('settings',
							                'invalid-timezone',
							                htmlspecialchars($frm->request->getString('timezone'))));
						return false;
					}
					$domain = 'http://' . preg_replace('/$(https?:\/\/)/i', '', trim($frm->request->getString('domain')));
					if(!filter_var($domain, FILTER_VALIDATE_URL)) {
						$frm->field('domain')
							->setWarning(__('form', 'invalid-url'));
						return false;
					}
					return true;
				});
			if($frm->status !== Form::VALIDATION_SUCCESS) {
				$this->title = $this->theme->head->section = __('settings', 'application');
				$tpl = new Template;
				$tpl->set('form', $frm)
					->set('page', $this);
				echo $tpl->render('admin/settings/application');
				return;
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));

			return;
		}
		$this->response->status(302)->redirect(App::request()->uri->url());
		try {
			$settings = App::settings();
			$settings->set('title', $this->request->getString('title'));
			$settings->set('rewrite_url', (bool)$this->request->getInt('rewrite_url'));
			$settings->set('ssl', $this->request->getInt('ssl', 0, 0, 2));
			$settings->set('domain', preg_replace('/$(https?:\/\/)/', '', trim($this->request->getString('domain'))));
			$settings->set('base_dir', trim($this->request->getString('base_dir'), "\n/\\"));
			$settings->set('timezone', trim($this->request->getString('timezone')));
			$settings->set('date_format', trim($this->request->getString('date_format')));
			$settings->set('time_format', trim($this->request->getString('time_format')));
			$settings->set('datetime_format', trim($this->request->getString('datetime_format')));
			$settings->set('output_compression', (bool)$this->request->getInt('ob'));
			$settings->set('tasks', (bool)$this->request->getInt('tasks'));
			if($settings->get('ssl', 0) >= 2) {
				$settings->get('session')->set('secure', true);
				$settings->get('account')->get('persistent_login')->set('secure', true);
			} else {
				$settings->get('session')->set('secure', false);
				$settings->get('account')->get('persistent_login')->set('secure', false);
			}
			App::settings()->export(\Aqua\ROOT . '/settings/application.php');
			App::user()->addFlash('success', null, __('settings', 'settings-saved'));
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			App::user()->addFlash('error', null, __('application', 'unexpected-error'));
		}
	}

	public function user_action()
	{
		try {
			$settings = App::settings()->get('account');
			$frm = new Form($this->request);
			$frm->checkbox('email_login', true)
				->value(array( '1' => '' ))
				->checked($settings->get('email_login', false) ? '1' : null, false)
				->setLabel(__('settings', 'user-login-email-label'))
				->setDescription(__('settings', 'user-login-email-desc'));
			$settings = App::settings()->get('account')->get('registration');
			$frm->checkbox('require_tos', true)
				->value(array( '1' => '' ))
				->checked($settings->get('require_tos', false) ? '1' : null, false)
				->setLabel(__('settings', 'user-tos-label'))
				->setDescription(__('settings', 'user-tos-desc', ac_build_url(array(
						'path' => array( 'page', 'tos' ),
						'base_dir' => App::settings()->get('base_dir')
					))));
			$frm->checkbox('require_captcha', true)
				->value(array( '1' => '' ))
				->checked($settings->get('captcha_confirmation', false) ? '1' : null, false)
				->setLabel(__('settings', 'user-captcha-label'));
			$frm->checkbox('email_validation', true)
				->value(array( '1' => '' ))
				->checked($settings->get('email_validation', false) ? '1' : null, false)
				->setLabel(__('settings', 'user-email-validation-label'));
			$frm->input('email_validation_expire', true)
				->type('number')
				->attr('min', 1)
				->value($settings->get('validation_time', 48), false)
				->setLabel(__('settings', 'user-validation-time-label'))
				->setDescription(__('settings', 'user-validation-time-desc'));
			$frm->input('validation_key_len', true)
				->type('number')
				->attr('min', 1)
				->value($settings->get('activation_key_length', 32), false)
				->setLabel(__('settings', 'user-key-len-label'));
			$settings = App::settings()->get('session');
			$frm->input('session_name', true)
				->type('text')
				->attr('maxlength', 50)
				->value(htmlspecialchars($settings->get('name', '')), false)
				->setLabel(__('settings', 'user-session-name-label'));
			$frm->checkbox('session_http_only', true)
				->value(array( '1' => '' ))
				->checked($settings->get('http_only', false) ? '1' : null, false)
				->setLabel(__('settings', 'user-session-http-only-label'));
			$frm->checkbox('session_match_ip', true)
				->value(array( '1' => '' ))
				->checked($settings->get('match_ip_address', false) ? '1' : null, false)
				->setLabel(__('settings', 'user-session-ip-label'));
			$frm->checkbox('session_match_user_agent', true)
				->value(array( '1' => '' ))
				->checked($settings->get('match_user_agent', false) ? '1' : null, false)
				->setLabel(__('settings', 'user-session-user-agent-label'));
			$frm->input('session_expire', true)
				->type('number')
				->attr('min', 1)
				->value($settings->get('expire', 1), false)
				->setLabel(__('settings', 'user-session-expire-label'))
				->setDescription(__('settings', 'user-session-expire-desc'));
			$frm->input('session_regenerate_id', true)
				->type('number')
				->attr('min', 1)
				->value($settings->get('regenerate_id', 1), false)
				->setLabel(__('settings', 'user-session-regen-label'))
				->setDescription(__('settings', 'user-session-regen-desc'));
			$frm->input('session_collision', true)
				->type('number')
				->attr('min', 1)
				->value($settings->get('max_collision', 1), false)
				->setLabel(__('settings', 'user-session-collision-label'));
			$frm->input('session_gc', true)
				->type('number')
				->attr('min', 0)
				->attr('max', 50)
				->value($settings->get('gc_probability', 1), false)
				->setLabel(__('settings', 'user-session-gc-label'))
				->setDescription(__('settings', 'user-session-gc-desc'));
			$settings = App::settings()->get('account')->get('persistent_login');
			$frm->checkbox('pl_enabled', true)
				->value(array( '1' => '' ))
				->checked($settings->get('enable', false) ? '1' : null, false)
				->setLabel(__('settings', 'user-pl-label'));
			$frm->input('pl_name', true)
				->type('text')
				->attr('maxlength', 100)
				->value(htmlspecialchars($settings->get('name', '')), false)
				->setLabel(__('settings', 'user-pl-name-label'));
			$frm->checkbox('pl_http_only', true)
				->value(array( '1' => '' ))
				->checked($settings->get('http_only', false) ? '1' : null, false)
				->setLabel(__('settings', 'user-pl-http-only-label'));
			$frm->input('pl_timeout', true)
				->type('number')
				->attr('min', 1)
				->value($settings->get('persistent_login')->get('timeout', 1), false)
				->setLabel(__('settings', 'user-pl-timeout-label'))
				->setDescription(__('settings', 'user-pl-timeout-desc'));
			$frm->input('pl_expire', true)
				->type('number')
				->attr('min', 1)
				->value($settings->get('persistent_login')->get('expire', 1), false)
				->setLabel(__('settings', 'user-pl-expire-label'))
				->setDescription(__('settings', 'user-pl-expire-desc'));
			$frm->input('pl_len', true)
				->type('number')
				->attr('min', 1)
				->value($settings->get('key_length', 1), false)
				->setLabel(__('settings', 'user-pl-len-label'));
			$frm->validate();
			if($frm->status !== Form::VALIDATION_SUCCESS) {
				$this->title = $this->theme->head->section = __('settings', 'user');
				$tpl = new Template;
				$tpl->set('form', $frm)
			        ->set('page', $this);
				echo $tpl->render('admin/settings/user');
				return;
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));

			return;
		}
		$this->response->status(302)->redirect(App::request()->uri->url());
		try {
			$settings = App::settings();
			$settings->get('account')->set('email_login', (bool)$this->request->getInt('email_login'));
			$settings->get('account')->get('registration')->set('require_tos', (bool)$this->request->getInt('require_tos'));
			$settings->get('account')->get('registration')->set('captcha_confirmation', (bool)$this->request->getInt('require_captcha'));
			$settings->get('account')->get('registration')->set('email_validation', (bool)$this->request->getInt('email_validation'));
			$settings->get('account')->get('registration')->set('validation_time', $this->request->getInt('email_validation_expire'));
			$settings->get('account')->get('registration')->set('activation_key_length', $this->request->getInt('validation_key_len'));
			$settings->get('account')->get('persistent_login')->set('enabled', (bool)$this->request->getInt('pl_enabled'));
			$settings->get('account')->get('persistent_login')->set('name', trim($this->request->getString('pl_name')));
			$settings->get('account')->get('persistent_login')->set('http_only', (bool)$this->request->getInt('pl_http_only'));
			$settings->get('account')->get('persistent_login')->set('timeout', $this->request->getInt('pl_timeout'));
			$settings->get('account')->get('persistent_login')->set('expire', $this->request->getInt('pl_expire'));
			$settings->get('account')->get('persistent_login')->set('key_length', $this->request->getInt('pl_len'));
			$settings->get('session')->set('name', trim($this->request->getString('session_name')));
			$settings->get('session')->set('http_only', (bool)$this->request->getInt('session_http_only'));
			$settings->get('session')->set('match_ip_address', (bool)$this->request->getInt('session_match_ip'));
			$settings->get('session')->set('match_user_agent', (bool)$this->request->getInt('session_match_user_agent'));
			$settings->get('session')->set('expire', $this->request->getInt('session_expire'));
			$settings->get('session')->set('regenerate_id', $this->request->getInt('session_regenerate_id'));
			$settings->get('session')->set('max_collision', $this->request->getInt('session_collision'));
			$settings->get('session')->set('gc_probability', $this->request->getInt('session_gc'));
			App::settings()->export(\Aqua\ROOT . '/settings/application.php');
			App::user()->addFlash('success', null, __('settings', 'settings-saved'));
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			App::user()->addFlash('error', null, __('application', 'unexpected-error'));
		}
	}

	public function account_action()
	{
		try {
			$settings = App::settings()->get('account');
			$frm = new Form($this->request);
			$frm->enctype = 'multipart/form-data';
			$frm->file('default_avatar')
				->accept('image/png', 'png')
				->accept('image/gif', 'gif')
				->accept('image/jpeg', array( 'jpg', 'jpeg' ))
				->accept('image/svg+xml', array( 'svg', 'svgx' ))
		        ->setLabel(__('settings', 'acc-avatar-default-label'));
			$frm->input('avatar_size', true)
				->type('text')
				->required()
				->attr('pattern', '/[1-9]+[0-9]*(B|KB|MB|GB|TB|PB)/')
				->value($settings->get('avatar')->get('max_size', '100KB'), false)
				->setLabel(__('settings', 'acc-avatar-size-label'))
				->setDefaultErrorMessage(Form\Input::VALIDATION_PATTERN, __('form', 'invalid-storage-size'));
			$frm->input('avatar_width', true)
				->type('number')
				->required()
				->attr('min', 1)
				->value($settings->get('avatar')->get('max_width', 1), false)
				->setLabel(__('settings', 'acc-avatar-width-label'));
			$frm->input('avatar_height', true)
				->type('number')
				->required()
				->attr('min', 1)
				->value($settings->get('avatar')->get('max_height', 1), false)
				->setLabel(__('settings', 'acc-avatar-height-label'));
			$frm->input('username_min_len', true)
				->type('number')
				->required()
				->attr('min', 1)
				->attr('max', 255)
				->value($settings->get('username')->get('min_length', 3), false)
				->setLabel(__('settings', 'acc-username-min-label'));
			$frm->input('username_max_len', true)
				->type('number')
				->required()
				->attr('min', 1)
				->attr('max', 255)
				->value($settings->get('username')->get('max_length', 26), false)
				->setLabel(__('settings', 'acc-username-max-label'));
			$frm->input('username_pattern', true)
				->type('text')
				->value($settings->get('username')->get('regex', ''), false)
				->setLabel(__('settings', 'acc-username-regex-label'))
				->setDescription(__('settings', 'acc-username-regex-desc'));
			$frm->input('display_min_len', true)
				->type('number')
				->required()
				->attr('min', 1)
				->attr('max', 255)
				->value($settings->get('display_name')->get('min_length', 3), false)
				->setLabel(__('settings', 'acc-display-min-label'));
			$frm->input('display_max_len', true)
				->type('number')
				->required()
				->attr('min', 1)
				->attr('max', 255)
				->value($settings->get('display_name')->get('max_length', 26), false)
				->setLabel(__('settings', 'acc-display-max-label'));
			$frm->input('display_pattern', true)
				->type('text')
				->value($settings->get('display_name')->get('regex', ''), false)
				->setLabel(__('settings', 'acc-display-regex-label'))
				->setDescription(__('settings', 'acc-display-regex-desc'));
			$frm->input('display_limit', true)
				->type('number')
				->attr('min', 0)
				->value($settings->get('display_name')->get('update_limit', 0), false)
				->setLabel(__('settings', 'acc-display-limit-label'))
				->setDescription(__('settings', 'acc-display-limit-desc'));
			$frm->input('display_days', true)
				->type('number')
				->attr('min', 1)
				->value($settings->get('display_name')->get('update_days', 0), false)
				->setLabel(__('settings', 'acc-display-interval-label'))
				->setDescription(__('settings', 'acc-display-interval-desc'));
			$frm->input('password_min_len', true)
				->type('number')
				->required()
				->attr('min', 1)
				->value($settings->get('password')->get('min_length', 3), false)
				->setLabel(__('settings', 'acc-password-min-label'));
			$frm->input('password_max_len', true)
				->type('number')
				->required()
				->attr('min', 1)
				->value($settings->get('password')->get('max_length', 50), false)
				->setLabel(__('settings', 'acc-password-max-label'));
			$frm->input('password_pattern', true)
				->type('text')
				->value($settings->get('password')->get('regex', ''), false)
				->setLabel(__('settings', 'acc-password-regex-label'))
				->setDescription(__('settings', 'acc-password-regex-desc'));
			$frm->input('password_limit', true)
				->type('number')
				->attr('min', 0)
				->value($settings->get('password')->get('update_limit', 0), false)
				->setLabel(__('settings', 'acc-password-limit-label'))
				->setDescription(__('settings', 'acc-password-limit-desc'));
			$frm->input('password_days', true)
				->type('number')
				->attr('min', 1)
				->value($settings->get('password')->get('update_days', 0), false)
				->setLabel(__('settings', 'acc-password-interval-label'))
				->setDescription(__('settings', 'acc-password-interval-desc'));
			$frm->input('email_limit', true)
				->type('number')
				->attr('min', 0)
				->value($settings->get('email')->get('update_limit', 0), false)
				->setLabel(__('settings', 'acc-email-limit-label'))
				->setDescription(__('settings', 'acc-email-limit-desc'));
			$frm->input('email_days', true)
				->type('number')
				->attr('min', 1)
				->value($settings->get('email')->get('update_days', 0), false)
				->setLabel(__('settings', 'acc-email-interval-label'))
				->setDescription(__('settings', 'acc-email-interval-desc'));
			$frm->input('birthday_min', true)
				->type('text')
				->attr('min', '1997-01-01')
				->value($settings->get('birthday')->get('min_length', ''), false)
				->placeholder('YYYY-MM-DD')
				->setLabel(__('settings', 'acc-birthday-min-label'))
				->setDescription(__('settings', 'acc-birthday-min-desc'));
			$frm->input('birthday_limit', true)
				->type('number')
				->attr('min', 0)
				->value($settings->get('birthday')->get('update_limit', 0), false)
				->setLabel(__('settings', 'acc-birthday-limit-label'))
				->setDescription(__('settings', 'acc-birthday-limit-desc'));
			$frm->input('birthday_days', true)
				->type('number')
				->attr('min', 1)
				->value($settings->get('birthday')->get('update_days', 0), false)
				->setLabel(__('settings', 'acc-birthday-interval-label'))
				->setDescription(__('settings', 'acc-birthday-interval-desc'));
			$frm->validate(function(Form $frm) {
				if($birthdayMin = $frm->request->getString('birthday_min')) {
					do {
						try {
							$date  = \DateTime::createFromFormat('Y-m-d', $birthdayMin);
							$error = \DateTime::getLastErrors();
							if($date && empty($error['warning_count'])) {
								break;
							}
						} catch(\Exception $e) { }
						$frm->field('birthday_min')->setWarning(__('form', 'invalid-date'));
						return false;
					} while(0);
				}
				foreach(array(
					'username_pattern',
				    'password_pattern',
				    'display_pattern'
					) as $key) {
					if($pattern = trim($frm->request->getString($key))) {
						@preg_match($pattern, '');
						if($mes = ac_pcre_error_str()) {
							$frm->field('password_pattern')->setWarning($mes);
							return false;
						}
					}
				}
				return true;
			});
			if($frm->status !== Form::VALIDATION_SUCCESS) {
				$this->title = $this->theme->head->section = __('settings', 'account');
				$tpl = new Template;
				$tpl->set('form', $frm)
				    ->set('page', $this);
				echo $tpl->render('admin/settings/account');
				return;
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));

			return;
		}
		try {
			$this->response->status(302)->redirect(App::request()->uri->url());
			if($timestamp = $this->request->getString('birthday_min')) {
				$timestamp = \DateTime::createFromFormat('Y-m-d', $timestamp)->getTimestamp();
			}
			$settings = App::settings()->get('account');
			$settings->get('avatar')->set('max_size', $this->request->getString('avatar_size'));
			$settings->get('avatar')->set('max_width', $this->request->getInt('avatar_width'));
			$settings->get('avatar')->set('max_height', $this->request->getInt('avatar_height'));
			$settings->get('username')->set('min_length', $this->request->getInt('username_min_len'));
			$settings->get('username')->set('max_length', $this->request->getInt('username_max_len'));
			$settings->get('username')->set('regex', $this->request->getString('username_pattern'));
			$settings->get('display_name')->set('min_length', $this->request->getInt('display_min_len'));
			$settings->get('display_name')->set('max_length', $this->request->getInt('display_max_len'));
			$settings->get('display_name')->set('regex', $this->request->getString('display_pattern'));
			$settings->get('display_name')->set('update_limit', $this->request->getInt('display_limit'));
			$settings->get('display_name')->set('update_days', $this->request->getInt('display_days'));
			$settings->get('password')->set('min_length', $this->request->getInt('password_min_len'));
			$settings->get('password')->set('max_length', $this->request->getInt('password_max_len'));
			$settings->get('password')->set('regex', $this->request->getString('password_pattern'));
			$settings->get('password')->set('update_limit', $this->request->getInt('password_limit'));
			$settings->get('password')->set('update_days', $this->request->getInt('password_days'));
			$settings->get('birthday')->set('min_length', $timestamp);
			$settings->get('birthday')->set('update_limit', $this->request->getInt('birthday_limit'));
			$settings->get('birthday')->set('update_days', $this->request->getInt('birthday_days'));
			$settings->get('email')->set('update_limit', $this->request->getInt('email_limit'));
			$settings->get('email')->set('update_days', $this->request->getInt('email_days'));
			if(ac_file_uploaded('default_avatar', false, $error, $errorStr)) {
				$uploader = new ImageUploader;
				if($uploader->uploadLocal($_FILES['default_avatar']['tmp_name'],
				                       $_FILES['default_avatar']['name']) &&
				   ($path = $uploader->save('/uploads/application'))) {
					if(($oldPath = $settings->get('default_avatar')) && is_readable(\Aqua\ROOT . $oldPath)) {
						@unlink(\Aqua\ROOT . $oldPath);
					}
					$settings->set('default_avatar', $path);
				} else if($uploader->error) {
					App::user()->addFlash('warning', null, $uploader->errorStr());
				}
			}
			App::settings()->export(\Aqua\ROOT . '/settings/application.php');
			App::user()->addFlash('success', null, __('settings', 'settings-saved'));
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			App::user()->addFlash('error', null, __('application', 'unexpected-error'));
		}
	}

	public function email_action()
	{
		try {
			$settings = App::settings()->get('email');
			$frm = new Form($this->request);
			$frm->input('from', true)
				->type('email')
				->required()
				->value($settings->get('from_address', ''), false)
				->setLabel(__('settings', 'email-address-label'));
			$frm->input('name', true)
				->type('text')
				->required()
				->value($settings->get('from_name', ''), false)
				->setLabel(__('settings', 'email-name-label'));
			$frm->checkbox('smtp', true)
				->value(array( '1' => '' ))
				->checked($settings->get('use_smtp', false) ? '1' : null, false)
				->setLabel(__('settings', 'email-use-smtp-label'));
			$frm->input('smtp_host', true)
				->type('text')
				->value($settings->get('smtp_host', ''), false)
				->setLabel(__('settings', 'email-smtp-host-label'));
			$frm->input('smtp_port', true)
				->type('number')
				->value($settings->get('smtp_port', 25), false)
				->setLabel(__('settings', 'email-smtp-port-label'));
			$frm->select('smtp_enc', true)
				->value(array(
					''    => __('settings', 'email-enc-none'),
					'ssl' => __('settings', 'email-enc-ssl'),
					'tls' => __('settings', 'email-enc-tls'),
				))
				->selected($settings->get('smtp_encryption', ''), false)
				->setLabel(__('settings', 'email-smtp-enc-label'));
			$frm->input('smtp_username', true)
				->type('text')
				->value($settings->get('smtp_username', ''), false)
				->setLabel(__('settings', 'email-smtp-username-label'))
				->setDescription(__('settings', 'email-smtp-username-desc'));
			$frm->input('smtp_password', true)
				->type('password')
				->value($settings->get('smtp_password', ''), false)
				->setLabel(__('settings', 'email-smtp-password-label'));
			$frm->input('smtp_timeout', true)
				->type('number')
				->attr('min', 0)
				->value($settings->get('smtp_timeout', 10), false)
				->setLabel(__('settings', 'email-smtp-timeout-label'))
				->setDescription(__('settings', 'email-smtp-timeout-desc'));
			$frm->validate();
			if($frm->status !== Form::VALIDATION_SUCCESS) {
				$this->title = $this->theme->head->section = __('settings', 'email');
				$tpl = new Template;
				$tpl->set('form', $frm)
				    ->set('page', $this);
				echo $tpl->render('admin/settings/email');
				return;
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));

			return;
		}
		$this->response->status(302)->redirect(App::request()->uri->url());
		try {
			$settings = App::settings()->get('email');
			$settings->set('from_address', trim($this->request->getString('from')));
			$settings->set('from_name', trim($this->request->getString('name')));
			$settings->set('use_smtp', (bool)$this->request->getInt('smtp'));
			$settings->set('smtp_host', trim($this->request->getString('smtp_host')));
			$settings->set('smtp_port', $this->request->getInt('smtp_port'));
			$settings->set('smtp_username', $this->request->getString('smtp_username'));
			$settings->set('smtp_password', $this->request->getString('smtp_password'));
			$settings->set('smtp_timeout', $this->request->getInt('smtp_timeout'));
			$settings->set('smtp_encryption', $this->request->getString('smtp_enc'));
			if(!$settings->get('smtp_username')) {
				$settings->set('smtp_authentication', false);
			} else {
				$settings->set('smtp_authentication', true);
			}
			App::settings()->export(\Aqua\ROOT . '/settings/application.php');
			App::user()->addFlash('success', null, __('settings', 'settings-saved'));
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			App::user()->addFlash('error', null, __('application', 'unexpected-error'));
		}
	}

	public function cms_action()
	{
		try {
			$settings = App::settings()->get('cms');
			$post = ContentType::getContentType(ContentType::CTYPE_POST);
			$page = ContentType::getContentType(ContentType::CTYPE_PAGE);
			$frm = new Form($this->request);
			$frm->checkbox('post_rating', true)
				->value(array( '1' => '' ))
				->checked($settings->get('post')->get('enable_rating_by_default', false) ? '1' : null, false)
				->setLabel(__('settings', 'cms-rating-label'));
			$frm->checkbox('post_comments', true)
				->value(array( '1' => '' ))
				->checked($settings->get('post')->get('enable_comments_by_default', false) ? '1' : null, false)
				->setLabel(__('settings', 'cms-comments-label'));
			$frm->checkbox('post_anon', true)
				->value(array( '1' => '' ))
				->checked($settings->get('post')->get('enable_anonymous_by_default', false) ? '1' : null, false)
				->setLabel(__('settings', 'cms-anon-label'));
			$frm->checkbox('post_archive', true)
				->value(array( '1' => '' ))
				->checked($settings->get('post')->get('enable_archiving_by_default', false) ? '1' : null, false)
				->setLabel(__('settings', 'cms-archiving-label'));
			$frm->input('post_weight', true)
				->type('number')
				->attr('min', 1)
				->value($post->filter('ratingFilter')->getOption('maxweight', 10), false)
				->setLabel(__('settings', 'cms-weight-label'));
			$frm->checkbox('post_comment_edit', true)
				->value(array( '1' => '' ))
				->checked($post->filter('commentFilter')->getOption('editing', true) ? '1' : null)
				->setLabel(__('settings', 'cms-comment-edit'));
			$frm->checkbox('post_comment_rate', true)
				->value(array( '1' => '' ))
				->checked($post->filter('commentFilter')->getOption('rating', true) ? '1' : null)
				->setLabel(__('settings', 'cms-comment-rate'));
			$frm->input('post_nesting', true)
				->type('number')
				->attr('min', 1)
				->value($post->filter('commentFilter')->getOption('nesting', 5), false)
				->setLabel(__('settings', 'cms-nesting-label'));
			$frm->input('post_archive_interval', true)
				->type('number')
				->attr('min', 0)
				->value($post->filter('archiveFilter')->getOption('interval', 20), false)
				->setLabel(__('settings', 'cms-archive-label'))
				->setDescription(__('settings', 'cms-archive-desc'));
			$frm->checkbox('page_rating', true)
			    ->value(array( '1' => '' ))
			    ->checked($settings->get('page')->get('enable_rating_by_default', false) ? '1' : null, false)
			    ->setLabel(__('settings', 'cms-rating-label'));
			$frm->input('page_weight', true)
			    ->type('number')
			    ->attr('min', 1)
			    ->value($page->filter('ratingFilter')->getOption('maxweight', 10), false)
			    ->setLabel(__('settings', 'cms-weight-label'));
			$frm->validate();
			if($frm->status !== Form::VALIDATION_SUCCESS) {
				$this->title = $this->theme->head->section = __('settings', 'content');
				$tpl = new Template;
				$tpl->set('form', $frm)
				    ->set('page', $this);
				echo $tpl->render('admin/settings/content');
				return;
			}
			$this->response->status(302)->redirect(App::request()->uri->url());
			try {
				$page->filter('RatingFilter')->setOption(array(
					'maxweight' => $this->request->getInt('page_weight')
				));
				$post->filter('CommentFilter')->setOption(array(
					'nesting' => $this->request->getInt('post_nesting'),
					'rating'  => (bool)$this->request->getInt('post_comment_rate'),
					'editing' => (bool)$this->request->getInt('post_comment_edit')
				));
				$post->filter('ArchiveFilter')->setOption(array(
					'interval' => $this->request->getInt('post_archive_interval')
				));
				$settings->get('page')->set('enable_rating_by_default', (bool)$this->request->getString('page_rating'));
				$settings->get('post')->set('enable_comments_by_default', (bool)$this->request->getString('post_comments'));
				$settings->get('post')->set('enable_anonymous_by_default', (bool)$this->request->getString('post_anon'));
				$settings->get('post')->set('enable_archiving_by_default', (bool)$this->request->getString('post_archive'));
				App::settings()->export(\Aqua\ROOT . '/settings/application.php');
				App::user()->addFlash('success', null, __('settings', 'settings-saved'));
			} catch(\Exception $exception) {
				ErrorLog::logSql($exception);
				App::user()->addFlash('error', null, __('application', 'unexpected-error'));
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));

			return;
		}
	}

	public function donation_action()
	{
		try {
			$currencies = array( 'AUD', 'BRL', 'CAD', 'CZK', 'DKK', 'EUR', 'HKD', 'HUF', 'ILS', 'JPY', 'MYR', 'MXN',
			                     'NOK', 'NZD', 'PHP', 'PLN', 'GBP', 'SGD', 'SEK', 'CHF', 'TWD', 'THB', 'TRY', 'USD' );
			$settings = App::settings()->get('donation');
			$frm = new Form($this->request);
			$frm->checkbox('enable')
				->value(array( '1' => '' ))
			    ->checked($settings->get('enable', true) ? '1' : null)
			    ->setLabel(__('settings', 'donation-enable'));
			$frm->checkbox('logging')
				->value(array( '1' => '' ))
			    ->checked($settings->get('pp_log_requests', false) ? '1' : null)
			    ->setLabel(__('settings', 'donation-logging'))
				->setDescription(__('settings', 'donation-logging-desc'));
			$frm->checkbox('pp_sandbox')
				->value(array( '1' => '' ))
			    ->checked($settings->get('pp_sandbox', false) ? '1' : null)
			    ->setLabel(__('settings', 'donation-pp-sandbox-label'))
			    ->setDescription(__('settings', 'donation-pp-sandbox-desc'));
			$frm->input('business_email')
				->type('email')
			    ->value(htmlspecialchars($settings->get('pp_business_email', '')))
			    ->setLabel(__('settings', 'donation-pp-email-label'));
			$frm->input('receiver_email')
				->type('text')
			    ->value(htmlspecialchars(implode(', ', $settings->get('pp_receiver_email')->toArray())))
			    ->setLabel(__('settings', 'donation-pp-receiver-label'))
			    ->setDescription(__('settings', 'donation-pp-receiver-desc'));
			$_currencies = array();
			foreach($currencies as $c) $_currencies[$c] = __('currency', $c);
			$frm->select('currency')
				->required()
				->value($_currencies)
			    ->selected($settings->get('currency', 'USD'))
			    ->setLabel(__('settings', 'donation-currency-label'))
			    ->setDescription(__('settings', 'donation-currency-desc'));
			$frm->input('min_donation')
				->required()
				->type('number')
				->attr('min', 1)
				->value($settings->get('min_donation', 1))
			    ->setLabel(__('settings', 'donation-min-label'))
			    ->setDescription(__('settings', 'donation-min-desc'));
			$frm->input('exchange_rate')
				->required()
				->type('number')
				->attr('min', 1)
				->value($settings->get('exchange_rate', 1))
			    ->setLabel(__('settings', 'donation-exchange-rate-label'))
			    ->setDescription(__('settings', 'donation-exchange-rate-desc'));
			$frm->input('goal')
				->type('number')
			    ->attr('min', 0)
			    ->value($settings->get('goal', 0))
				->setLabel(__('settings', 'donation-goal-label'));
			$frm->select('goal_interval')
				->value(array(
						'monthly' => __('timespan', 'monthly'),
						'weekly' => __('timespan', 'weekly'),
						'daily' => __('timespan', 'daily'),
					))
				->selected($settings->get('goal_interval', 'monthly'))
				->setLabel(__('settings', 'donation-goal-interval-label'));
			$frm->validate(function(Form $frm) {
					$business_email = array_filter(preg_split('/ *, */', $frm->request->getString('receiver_email')));
					foreach($business_email as &$email) {
						$email = trim($email);
						if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
							$frm->field('receiver_email')->setWarning(__('form', 'invalid-email'));
							return false;
						}
					}
					$frm->request->data['receiver_email'] = implode(', ', $business_email);
					return true;
				});
			if($frm->status !== Form::VALIDATION_SUCCESS) {
				$this->title = $this->theme->head->section = __('settings', 'donation');
				$tpl = new Template;
				$tpl->set('form', $frm)
					->set('page', $this);
				echo $tpl->render('admin/settings/donation');
				return;
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));

			return;
		}
		$this->response->status(302)->redirect(App::request()->uri->url());
		try {
			$settings = App::settings();
			$settings->get('donation')->set('enable', (bool)$this->request->getInt('enable'));
			$settings->get('donation')->set('pp_log_requests', (bool)$this->request->getInt('logging'));
			$settings->get('donation')->set('pp_sandbox', (bool)$this->request->getInt('pp_sandbox'));
			$settings->get('donation')->set('pp_business_email', $this->request->getString('business_email'));
			$settings->get('donation')->set('pp_receiver_email', preg_split('/ *, */', $this->request->getString('receiver_email')));
			$settings->get('donation')->set('currency', $this->request->getString('currency'));
			$settings->get('donation')->set('min_donation', $this->request->getInt('min_donation'));
			$settings->get('donation')->set('exchange_rate', $this->request->getInt('exchange_rate'));
			$settings->get('donation')->set('goal', $this->request->getInt('goal'));
			$settings->get('donation')->set('goal_interval', $this->request->getString('goal_interval'));
			App::settings()->export(\Aqua\ROOT . '/settings/application.php');
			App::user()->addFlash('success', null, __('settings', 'settings-saved'));
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			App::user()->addFlash('error', null, __('application', 'unexpected-error'));
		}
	}

	public function captcha_action()
	{
		try {
			$settings = App::settings()->get('captcha');
			$frm = new Form($this->request);
			$frm->enctype = 'multipart/form-data';
			$frm->input('width', true)
		        ->type('number')
		        ->attr('min', 1)
		        ->value($settings->get('width', 1), false)
				->setLabel(__('settings', 'captcha-width-label'));
			$frm->input('height', true)
		        ->type('number')
		        ->attr('min', 1)
		        ->value($settings->get('height', 1), false)
				->setLabel(__('settings', 'captcha-height-label'));
			$frm->checkbox('case_sensitive', true)
		        ->value(array( '1' => '' ))
				->checked($settings->get('case_sensitive', false) ? '1' : null, false)
				->setLabel(__('settings', 'captcha-case-label'));
			$frm->file('font_file')
				->setLabel(__('settings', 'captcha-font-label'))
				->setDescription(__('settings', 'captcha-font-desc'));
			$frm->input('font_size', true)
				->type('number')
				->attr('min', 1)
				->value($settings->get('font_size', 12), false)
				->setLabel(__('settings', 'captcha-font-size-label'));
			$frm->input('font_color', true)
				->type('color')
				->value(sprintf('#%06x', $settings->get('font_color', 0x000000)), false)
				->setLabel(__('settings', 'captcha-font-color-label'));
			$frm->input('font_color2', true)
				->type('color')
				->value(sprintf('#%06x', $settings->get('font_color_variation', 0x000000)), false)
				->setLabel(__('settings', 'captcha-font-color-var-label'));
			$frm->input('bg_color', true)
				->type('color')
				->value(sprintf('#%06x', $settings->get('background_color', 0x000000)), false)
				->setLabel(__('settings', 'captcha-bg-color-label'));
			$frm->file('bg_file')
				->accept('image/png', 'png')
				->accept('image/gif', 'gif')
				->accept('image/jpeg', array( 'jpg', 'jpeg' ))
				->accept('image/svg+xml', array( 'svg', 'svgx' ))
				->setLabel(__('settings', 'captcha-bg-image-label'));
			$frm->input('noise_color', true)
				->type('color')
				->value(sprintf('#%06x', $settings->get('noise_color', 0x000000)), false)
				->setLabel(__('settings', 'captcha-noise-color-label'));
			$frm->input('noise_color2', true)
				->type('color')
				->value(sprintf('#%06x', $settings->get('noise_color', 0x000000)), false)
				->setLabel(__('settings', 'captcha-noise-color-var-label'));
			$frm->input('noise_level', true)
				->type('number')
				->attr('min', 0)
				->attr('max', 10)
				->value($settings->get('noise_level', 0), false)
				->setLabel(__('settings', 'captcha-noise-level-label'));
			$frm->input('min_lines')
				->type('number')
				->attr('min', 0)
				->value($settings->get('min_lines', 0))
				->setLabel(__('settings', 'captcha-min-lines-label'));
			$frm->input('max_lines')
				->type('number')
				->attr('min', 0)
				->value($settings->get('max_lines', 0))
				->setLabel(__('settings', 'captcha-max-lines-label'));
			$frm->input('min_len')
				->type('number')
				->attr('min', 1)
				->attr('max', 50)
				->value($settings->get('min_length', 1))
				->setLabel(__('settings', 'captcha-min-len-label'));
			$frm->input('max_len')
				->type('number')
				->attr('min', 1)
				->attr('max', 50)
				->value($settings->get('max_length', 1))
				->setLabel(__('settings', 'captcha-max-len-label'));
			$frm->input('characters')
				->type('text')
				->value(htmlspecialchars($settings->get('characters', '')))
				->setLabel(__('settings', 'captcha-char-list-label'));
			$frm->input('expire')
				->type('number')
				->attr('min', 1)
				->value($settings->get('expire', 1))
				->setLabel(__('settings', 'captcha-ttl-label'))
				->setDescription(__('settings', 'captcha-ttl-desc'));
			$frm->input('gc')
				->type('number')
				->attr('min', 0)
				->value($settings->get('gc_probability', 1))
				->setLabel(__('settings', 'captcha-gc-label'))
				->setDescription(__('settings', 'captcha-gc-desc'));
			$frm->checkbox('use_recaptcha')
				->value(array( '1' => '' ))
				->checked($settings->get('use_recaptcha', false) ? '1' : null)
				->setLabel(__('settings', 'use-recaptcha-label'))
				->setDescription(__('settings', 'use-recaptcha-desc', ReCaptcha::signupUrl()));
			$frm->checkbox('recaptcha_ssl')
				->value(array( '1' => '' ))
				->checked($settings->get('recaptcha_ssl', false) ? '1' : null)
				->setLabel(__('settings', 'recaptcha-ssl-label'));
			$frm->input('recaptcha_private_key')
				->type('text')
				->value(htmlspecialchars($settings->get('recaptcha_private_key', '')))
				->setLabel(__('settings', 'recaptcha-private-label'));
			$frm->input('recaptcha_public_key')
				->type('text')
				->value(htmlspecialchars($settings->get('recaptcha_public_key', '')))
				->setLabel(__('settings', 'recaptcha-public-label'));
			$frm->validate(function(Form $frm) {
					if(ac_file_uploaded('font_file') === 0 && ac_font_info($_FILES['font_file']['tmp_name']) === false) {
						$frm->field('font_file')->setWarning(__('captcha-settings', 'invalid-font'));
					    return false;
					}
					return true;
				});
			if($frm->status !== Form::VALIDATION_SUCCESS) {
				$this->title = $this->theme->head->section = __('settings', 'captcha');
				$tpl = new Template;
				$tpl->set('form', $frm)
					->set('page', $this);
				echo $tpl->render('admin/settings/captcha');
				return;
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));

			return;
		}
		$this->response->status(302)->redirect(App::request()->uri->url());
		try {
			if(array_key_exists('x-delete-captcha-bg', $this->request->data)) {
				if(($file = $settings->get('background_image')) && is_readable(\Aqua\ROOT . $file)) {
					@unlink(\Aqua\ROOT . $file);
				}
				$settings->set('background_image', null);
			} else if(array_key_exists('x-delete-captcha-font', $this->request->data)) {
				if(($file = $settings->get('font_file')) && is_readable(\Aqua\ROOT . $file)) {
					@unlink(\Aqua\ROOT . $file);
				}
				$settings->set('font_file', null);
			} else {
				$settings = App::settings()->get('captcha');
				$settings->set('width', $this->request->getInt('width'));
				$settings->set('height', $this->request->getInt('height'));
				$settings->set('case_sensitive', (bool)$this->request->getInt('case_sensitive'));
				$settings->set('font_size', $this->request->getInt('font_size'));
				$settings->set('noise_level', $this->request->getInt('noise_level'));
				$settings->set('min_lines', $this->request->getInt('min_lines'));
				$settings->set('max_lines', $this->request->getInt('max_lines'));
				$settings->set('min_length', $this->request->getInt('min_len'));
				$settings->set('max_length', $this->request->getInt('max_len'));
				$settings->set('characters', $this->request->getString('characters'));
				$settings->set('expire', $this->request->getInt('expire'));
				$settings->set('gc_probability', $this->request->getInt('gc'));
				$settings->set('font_color', hexdec(substr($this->request->getString('font_color'), 1)));
				$settings->set('font_color_variation', hexdec(substr($this->request->getString('font_color2'), 1)));
				$settings->set('background_color', hexdec(substr($this->request->getString('bg_color'), 1)));
				$settings->set('lines_color', hexdec(substr($this->request->getString('lines_color'), 1)));
				$settings->set('noise_color', hexdec(substr($this->request->getString('noise_color'), 1)));
				$settings->set('use_recaptcha', (bool)$this->request->getInt('use_recaptcha'));
				$settings->set('recaptcha_ssl', (bool)$this->request->getInt('recaptcha_ssl'));
				$settings->set('recaptcha_private_key', $this->request->getString('recaptcha_private_key'));
				$settings->set('recaptcha_public_key', $this->request->getString('recaptcha_public_key'));
				if(ac_file_uploaded('font_file', false, $error, $errorStr)) {
					$path = '/uploads/application/' . uniqid() . '.ttf';
					$newFile = \Aqua\ROOT . $path;
					if(!move_uploaded_file($_FILES['font_file']['tmp_name'], $newFile)) {
						App::user()->addFlash('warning', null, __('upload', 'failed-to-move'));
					} else {
						if(($file = $settings->get('font_file')) && is_writable(\Aqua\ROOT . $file)) {
							@unlink(\Aqua\ROOT . $file);
						}
						$settings->set('font_file', $path);
					}
				}
				if(ac_file_uploaded('bg_file', false, $error, $errorStr)) {
					$uploader = new ImageUploader;
					if($uploader->uploadLocal($_FILES['bg_file']['tmp_name'],
					                          $_FILES['bg_file']['name']) &&
					   ($path = $uploader->save('/uploads/application'))) {
						if(($file = $settings->get('background_image')) && is_readable(\Aqua\ROOT . $file)) {
							@unlink(\Aqua\ROOT . $file);
						}
						$settings->set('background_image', $path);
					} else {
						App::user()->addFlash('warning', null, $uploader->errorStr());
					}
				}
			}
			App::settings()->export(\Aqua\ROOT . '/settings/application.php');
			App::user()->addFlash('success', null, __('settings', 'settings-saved'));
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			App::user()->addFlash('error', null, __('application', 'unexpected-error'));
		}
	}

	public function ragnarok_action()
	{
		try {
			$settings = App::settings()->get('ragnarok');
			$chargen  = App::settings()->get('chargen');
			$frm = new Form($this->request);
			$frm->checkbox('acc', true)
			    ->value(array( '1' => '' ))
			    ->checked($settings->get('acc_username_url', false) ? '1' : null, false)
			    ->setLabel(__('settings', 'ro-acc-url-label'));
			$frm->checkbox('char', true)
			    ->value(array( '1' => '' ))
			    ->checked($settings->get('char_name_url', false) ? '1' : null, false)
			    ->setLabel(__('settings', 'ro-char-url-label'));
			$frm->checkbox('script', true)
			    ->value(array( '1' => '' ))
			    ->checked($settings->get('display_item_script', false) ? '1' : null, false)
			    ->setLabel(__('settings', 'ro-item-script-label'));
			$frm->input('pincode_min', true)
				->type('number')
				->attr('min', 0)
				->value($settings->get('pincode_min_len', 4), false)
				->setLabel(__('settings', 'ro-pincode-min-label'));
			$frm->input('pincode_max', true)
				->type('number')
				->attr('min', 0)
				->value($settings->get('pincode_max_len', 4), false)
				->setLabel(__('settings', 'ro-pincode-max-label'));
			$frm->input('shop_max', true)
				->type('number')
				->attr('min', 0)
				->value($settings->get('cash_shop_max_amount', 4), false)
				->setLabel(__('settings', 'ro-purchase-max-label'));
			$frm->checkbox('emblem_cache', true)
			    ->value(array( '1' => '' ))
			    ->checked($chargen->get('emblem')->get('cache_browser', false) ? '1' : null, false)
			    ->setLabel(__('settings', 'ro-emblem-cache-label'));
			$frm->input('emblem_ttl', true)
				->type('number')
				->attr('min', -1)
				->value($chargen->get('emblem')->get('cache_ttl', 300), false)
				->setLabel(__('settings', 'ro-emblem-ttl-label'))
				->setDescription(__('settings', 'ro-emblem-ttl-desc'));
			$frm->input('emblem_compress', true)
				->type('number')
				->attr('min', 0)
				->attr('max', 9)
				->value($chargen->get('emblem')->get('compression', 0), false)
				->setLabel(__('settings', 'ro-emblem-compress-label'));
			$frm->checkbox('sprite_cache', true)
			    ->value(array( '1' => '' ))
			    ->checked($chargen->get('sprite')->get('cache_browser', false) ? '1' : null, false)
			    ->setLabel(__('settings', 'ro-sprite-cache-label'));
			$frm->input('sprite_ttl', true)
				->type('number')
				->attr('min', -1)
				->value($chargen->get('sprite')->get('cache_ttl', -1), false)
				->setLabel(__('settings', 'ro-emblem-ttl-label'))
				->setDescription(__('settings', 'ro-sprite-ttl-desc'));
			$frm->input('sprite_compress', true)
				->type('number')
				->attr('min', 0)
				->attr('max', 9)
				->value($chargen->get('sprite')->get('compression', 0), false)
				->setLabel(__('settings', 'ro-sprite-compress-label'));
			$frm->select('head_pos', true)
				->value(array(
					RORender::DIRECTION_SOUTH => __('settings', 'ro-pos-s'),
					RORender::DIRECTION_SOUTHWEST => __('settings', 'ro-pos-sw'),
					RORender::DIRECTION_WEST => __('settings', 'ro-pos-w'),
					RORender::DIRECTION_NORTHWEST => __('settings', 'ro-pos-nw'),
					RORender::DIRECTION_NORTH => __('settings', 'ro-pos-n'),
					RORender::DIRECTION_NORTHEAST => __('settings', 'ro-pos-ne'),
					RORender::DIRECTION_EAST => __('settings', 'ro-pos-e'),
					RORender::DIRECTION_SOUTHEAST => __('settings', 'ro-pos-se'),
				))
				->selected($chargen->get('sprite')->get('head_direction', RORender::DIRECTION_NORTH), false)
				->setLabel(__('settings', 'ro-head-dir-label'));
			$frm->select('body_pos', true)
				->value(array(
					RORender::DIRECTION_SOUTH => __('settings', 'ro-pos-s'),
					RORender::DIRECTION_SOUTHWEST => __('settings', 'ro-pos-sw'),
					RORender::DIRECTION_WEST => __('settings', 'ro-pos-w'),
					RORender::DIRECTION_NORTHWEST => __('settings', 'ro-pos-nw'),
					RORender::DIRECTION_NORTH => __('settings', 'ro-pos-n'),
					RORender::DIRECTION_NORTHEAST => __('settings', 'ro-pos-ne'),
					RORender::DIRECTION_EAST => __('settings', 'ro-pos-e'),
					RORender::DIRECTION_SOUTHEAST => __('settings', 'ro-pos-se'),
				))
				->selected($chargen->get('sprite')->get('body_direction', RORender::DIRECTION_NORTH), false)
				->setLabel(__('settings', 'ro-body-dir-label'));
			$frm->select('body_act', true)
				->value(array(
					CharacterRender::ACTION_IDLE => __('settings', 'ro-act-idle'),
					CharacterRender::ACTION_WALK => __('settings', 'ro-act-walk'),
					CharacterRender::ACTION_SIT => __('settings', 'ro-act-sit'),
					CharacterRender::ACTION_PICKUP => __('settings', 'ro-act-pick'),
					CharacterRender::ACTION_READYFIGHT => __('settings', 'ro-act-fight'),
					CharacterRender::ACTION_ATTACK => __('settings', 'ro-act-attack'),
					CharacterRender::ACTION_HURT => __('settings', 'ro-act-hurt'),
					CharacterRender::ACTION_DIE => __('settings', 'ro-act-die'),
					CharacterRender::ACTION_ATTACK2 => __('settings', 'ro-act-attack2'),
					CharacterRender::ACTION_ATTACK3 => __('settings', 'ro-act-attack3'),
					CharacterRender::ACTION_SKILL=> __('settings', 'ro-act-skill'),
				))
				->selected($chargen->get('sprite')->get('body_action', CharacterRender::ACTION_IDLE), false)
				->setLabel(__('settings', 'ro-body-action-label'));
			$frm->validate();
			if($frm->status !== Form::VALIDATION_SUCCESS) {
				$this->title = $this->theme->head->section = __('settings', 'captcha');
				$tpl = new Template;
				$tpl->set('form', $frm)
					->set('page', $this);
				echo $tpl->render('admin/settings/ragnarok');
				return;
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));

			return;
		}
		$this->response->status(302)->redirect(App::request()->uri->url());
		try {
			$settings = App::settings();
			if($settings->get('chargen')->get('sprite')->get('body_direction') !== $this->request->getInt('body_pos') ||
			   $settings->get('chargen')->get('sprite')->get('body_action') !== $this->request->getInt('body_act')) {
				ac_delete_dir(\Aqua\ROOT . '/tmp/chargen/body');
			}
			if($settings->get('chargen')->get('sprite')->get('head_direction') !== $this->request->getInt('head_pos')) {
				ac_delete_dir(\Aqua\ROOT . '/tmp/chargen/head');
			}
			$settings->get('ragnarok')->set('acc_username_url', (bool)$this->request->getInt('acc'));
			$settings->get('ragnarok')->set('char_name_url', (bool)$this->request->getInt('char'));
			$settings->get('ragnarok')->set('display_item_script', (bool)$this->request->getInt('script'));
			$settings->get('ragnarok')->set('pincode_min_len', $this->request->getInt('pincode_min'));
			$settings->get('ragnarok')->set('pincode_max_len', $this->request->getInt('pincode_max'));
			$settings->get('ragnarok')->set('cash_shop_min_amount', $this->request->getInt('shop_min'));
			$settings->get('ragnarok')->set('cash_shop_max_amount', $this->request->getInt('shop_max'));
			$settings->get('chargen')->get('emblem')->set('cache_ttl', $this->request->getInt('emblem_ttl'));
			$settings->get('chargen')->get('emblem')->set('cache_browser', (bool)$this->request->getInt('emblem_cache'));
			$settings->get('chargen')->get('emblem')->set('compression', $this->request->getInt('emblem_compress'));
			$settings->get('chargen')->get('sprite')->set('cache_ttl', $this->request->getInt('sprite_ttl'));
			$settings->get('chargen')->get('sprite')->set('cache_browser', (bool)$this->request->getInt('sprite_cache'));
			$settings->get('chargen')->get('sprite')->set('compression', $this->request->getInt('sprite_compress'));
			$settings->get('chargen')->get('sprite')->set('head_direction', $this->request->getInt('head_pos'));
			$settings->get('chargen')->get('sprite')->set('body_direction', $this->request->getInt('body_pos'));
			$settings->get('chargen')->get('sprite')->set('body_action', $this->request->getInt('body_act'));
			App::settings()->export(\Aqua\ROOT . '/settings/application.php');
			App::user()->addFlash('success', null, __('settings', 'settings-saved'));
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			App::user()->addFlash('error', null, __('application', 'unexpected-error'));
		}
	}
}
