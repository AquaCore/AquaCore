<?php
namespace Page\Admin;

use Aqua\Core\App;
use Aqua\Core\L10n;
use Aqua\Log\ErrorLog;
use Aqua\Captcha\ReCaptcha;
use Aqua\Site\Page;
use Aqua\UI\Form;
use Aqua\UI\Tag;
use Aqua\UI\Template;
use Aqua\User\Role;
use CharGen\CharacterRender;
use CharGen\RORender;

class Settings
extends Page
{
	public function index_action()
	{
		if(isset($this->request->data['x-delete-rss-image'])) {
			$this->response->status(302)->redirect(App::request()->uri->url());
			if(preg_match('/(\/uploads\/application\/[^\.]+\.(png|jpeg|jpg|gif))$/i', App::settings()->get('rss')->get('image', ''), $match) && is_writable(\Aqua\ROOT . $match[1])) {
				@unlink(\Aqua\ROOT . $match[1]);
			}
			App::settings()->get('rss')->set('image', null);
			App::settings()->export(\Aqua\ROOT . '/settings/application.php');
			return;
		}
		$settings = App::settings();
		$frm = new Form($this->request);
		// Application Settings
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
		$languages = array();
		foreach(L10n::$languages as $lang) {
			$languages[$lang->code] = $lang->name;
		}
		$frm->select('language')
			->value($languages)
			->selected($settings->get('language', null))
			->setLabel(__('settings', 'app-language-label'));
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
					$tz = trim($this->request->getString('timezone'));
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
				$domain = 'http://' . preg_replace('/$(https?:\/\/)/i', '', trim($this->request->getString('domain')));
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
		$this->response->status(302)->redirect(App::request()->uri->url());
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
		if($settings->get('ssl', 0) >= 2) {
			$settings->get('session')->set('secure', true);
			$settings->get('account')->get('persistent_login')->set('secure', true);
		} else {
			$settings->get('session')->set('secure', false);
			$settings->get('account')->get('persistent_login')->set('secure', false);
		}
		App::settings()->export(\Aqua\ROOT . '/settings/application.php');
		App::user()->addFlash('success', null, __('settings', 'settings-saved'));
	}

	public function user_action()
	{
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
		$this->response->status(302)->redirect(App::request()->uri->url());
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
	}

	public function account_action()
	{
		$settings = App::settings()->get('account');
		$frm = new Form($this->request);
		$frm->file('default_avatar')
	        ->attr('acceopt', 'image/jpeg, image/png, image/gif')
	        ->setLabel(__('account-settings', 'default-avatar-label'));

	}

	public function email_action()
	{
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
		$this->response->status(302)->redirect(App::request()->uri->url());
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
	}

	public function cms_action()
	{

	}

	public function donation_action()
	{
		$currencies = array( 'AUD', 'BRL', 'CAD', 'CZK', 'DKK', 'EUR', 'HKD', 'HUF', 'ILS', 'JPY', 'MYR', 'MXN', 'NOK', 'NZD', 'PHP', 'PLN', 'GBP', 'SGD', 'SEK', 'CHF', 'TWD', 'THB', 'TRY', 'USD' );
		$settings = App::settings()->get('donation');
		$frm = new Form($this->request);
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
		$this->response->status(302)->redirect(App::request()->uri->url());
		$settings = App::settings();
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
	}

	public function captcha_action()
	{
		$settings = App::settings()->get('captcha');
		$frm = new Form($this->request);
		$frm->input('width')
	        ->type('number')
	        ->attr('min', 1)
	        ->value($settings->get('width', 1))
			->setLabel(__('settings', 'captcha-width-label'));
		$frm->input('height')
	        ->type('number')
	        ->attr('min', 1)
	        ->value($settings->get('height', 1))
			->setLabel(__('settings', 'captcha-height-label'));
		$frm->checkbox('case_sensitive')
	        ->value(array( '1' => '' ))
			->checked($settings->get('case_sensitive', false) ? '1' : null)
			->setLabel(__('settings', 'captcha-case-label'));
		$frm->file('font_file')
			->setLabel(__('settings', 'captcha-font-label'))
			->setDescription(__('settings', 'captcha-font-desc'));
		$frm->input('font_size')
			->type('number')
			->attr('min', 1)
			->value($settings->get('font_size', 1))
			->setLabel(__('settings', 'captcha-font-size-label'));
		$frm->input('font_color')
			->type('color')
			->value(sprintf('#%06x', $settings->get('font_color', 0x000000)))
			->setLabel(__('settings', 'font-color-label'));
		$frm->input('font_color2')
			->type('color')
			->value(sprintf('#%06x', $settings->get('font_color_variation', 0x000000)))
			->setLabel(__('settings', 'font-color-var-label'));
		$frm->input('bg_color')
			->type('color')
			->value(sprintf('#%06x', $settings->get('background_color', 0x000000)))
			->setLabel(__('settings', 'captcha-background-color-label'));
		$frm->file('bg_file')
			->attr('accept', 'image/png, image/jpeg, image/gif')
			->setLabel(__('settings', 'captcha-background-image-label'));
		$frm->input('noise_color')
			->type('color')
			->value(sprintf('#%06x', $settings->get('noise_color', 0x000000)))
			->setLabel(__('captcha-settings', 'noise-color-label'));
		$frm->input('noise_level')
			->type('number')
			->attr('min', 0)
			->attr('max', 10)
			->value($settings->get('noise_level', 0))
			->setLabel(__('captcha-settings', 'noise-level-label'));
		$frm->input('lines_color')
			->type('color')
			->value(sprintf('#%06x', $settings->get('lines_color', 0x000000)))
			->setLabel(__('captcha-settings', 'lines-color-label'));
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
		$frm->input('length')
			->type('number')
			->attr('min', 1)
			->attr('max', 50)
			->value($settings->get('length', 1))
			->setLabel(__('settings', 'captcha-length-label'));
		$frm->input('characters')
			->type('text')
			->value(htmlspecialchars($settings->get('characters', '')))
			->setLabel(__('settings', 'captcha-characters-label'));
		$frm->input('expire')
			->type('number')
			->attr('min', 1)
			->value($settings->get('expire', 1))
			->setLabel(__('settings', 'captcha-expire-label'))
			->setDescription(__('settings', 'captcha-expire-desc'));
		$frm->input('gc')
			->type('number')
			->attr('min', 0)
			->value($settings->get('gc_probability', 1))
			->setLabel(__('settings', 'captcha-gc-label'))
			->setDescription(__('settings', 'captcha-gc-desc'));
		$frm->checkbox('use_recaptcha')
			->value(array( '1' => '' ))
			->checked($settings->get('use_recaptcha', false) ? '1' : null)
			->setLabel(__('captcha-settings', 'use-recaptcha-label'))
			->setDescription(__('captcha-settings', 'use-recaptcha-desc', ReCaptcha::signupUrl()));
		$frm->checkbox('recaptcha_ssl')
			->value(array( '1' => '' ))
			->checked($settings->get('recaptcha_ssl', false) ? '1' : null)
			->setLabel(__('captcha-settings', 'recaptcha-ssl-label'))
			->setDescription(__('captcha-settings', 'recaptcha-ssl-desc'));
		$frm->input('recaptcha_private_key')
			->type('text')
			->value(htmlspecialchars($settings->get('recaptcha_private_key', '')))
			->setLabel(__('captcha-settings', 'recaptcha-private-label'));
		$frm->input('recaptcha_public_key')
			->type('text')
			->value(htmlspecialchars($settings->get('recaptcha_public_key', '')))
			->setLabel(__('captcha-settings', 'recaptcha-public-label'));
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
		$this->response->status(302)->redirect(App::request()->uri->url());
		$settings = App::settings();
		$settings->get('captcha')->set('width', $this->request->getInt('width'));
		$settings->get('captcha')->set('height', $this->request->getInt('height'));
		$settings->get('captcha')->set('case_sensitive', (bool)$this->request->getInt('case_sensitive'));
		$settings->get('captcha')->set('font_size', $this->request->getInt('font_size'));
		$settings->get('captcha')->set('noise_level', $this->request->getInt('noise_level'));
		$settings->get('captcha')->set('min_lines', $this->request->getInt('min_lines'));
		$settings->get('captcha')->set('max_lines', $this->request->getInt('max_lines'));
		$settings->get('captcha')->set('length', $this->request->getInt('length'));
		$settings->get('captcha')->set('characters', $this->request->getString('characters'));
		$settings->get('captcha')->set('expire', $this->request->getInt('expire'));
		$settings->get('captcha')->set('gc_probability', $this->request->getInt('gc'));
		$settings->get('captcha')->set('font_color', hexdec(substr($this->request->getString('font_color'), 1)));
		$settings->get('captcha')->set('font_color_variation', hexdec(substr($this->request->getString('font_color2'), 1)));
		$settings->get('captcha')->set('background_color', hexdec(substr($this->request->getString('bg_color'), 1)));
		$settings->get('captcha')->set('lines_color', hexdec(substr($this->request->getString('lines_color'), 1)));
		$settings->get('captcha')->set('noise_color', hexdec(substr($this->request->getString('noise_color'), 1)));
		$settings->get('captcha')->set('use_recaptcha', (bool)$this->request->getInt('use_recaptcha'));
		$settings->get('captcha')->set('recaptcha_ssl', (bool)$this->request->getInt('recaptcha_ssl'));
		$settings->get('captcha')->set('recaptcha_private_key', $this->request->getString('recaptcha_private_key'));
		$settings->get('captcha')->set('recaptcha_public_key', $this->request->getString('recaptcha_public_key'));
		if(ac_file_uploaded('font_file') === 0) {
			$new_file = \Aqua\ROOT . '/uploads/application/' . uniqid() . '.ttf';
			if(!move_uploaded_file($_FILES['font_file']['tmp_name'], $new_file)) {
				App::user()->addFlash('warning', null, __('upload', 'upload-fail'));
			} else {
				if(($file = $settings->get('font_file')) && is_writable($file)) {
					@unlink($file);
				}
				$settings->get('captcha')->set('font_file', $new_file);
			}
		}
		App::settings()->export(\Aqua\ROOT . '/settings/application.php');
		App::user()->addFlash('success', null, __('settings', 'settings-saved'));
	}

	public function ragnarok_action()
	{
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
		    ->setLabel(__('settings', 'ro-char-url-label'));
		$frm->input('pincode_min', true)
			->type('number')
			->attr('min', 0)
			->value($settings->get('pincode_min_len', 4), false)
			->setLabel(__('settings', 'ro-item-script-label'));
		$frm->input('pincode_max', true)
			->type('number')
			->attr('min', 0)
			->value($settings->get('pincode_max_len', 4), false)
			->setLabel(__('settings', 'ro-pincode-max-label'));
		$frm->input('shop_min', true)
			->type('number')
			->attr('min', 0)
			->value($settings->get('cash_shop_min_amount', 4), false)
			->setLabel(__('settings', 'ro-purchase-min-label'));
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
			->selected($chargen->get('emblem')->get('body_action', CharacterRender::ACTION_IDLE), false)
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
		$this->response->status(302)->redirect(App::request()->uri->url());
		$settings = App::settings();
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
	}
}
