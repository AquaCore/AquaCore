<?php
namespace Aqua\Core;

use Aqua\Event\EventDispatcher;
use Aqua\Event\SubjectInterface;
use Aqua\Http\Request;
use Aqua\Http\Response;
use Aqua\Log\ErrorLog;
use Aqua\Log\LoginLog;
use Aqua\Ragnarok\Cart;
use Aqua\Ragnarok\Server\CharMap;
use Aqua\Session\Session;
use Aqua\User\Account;
use Aqua\User\PersistentLogin;
use Aqua\User\Role;

class User
implements SubjectInterface
{
	/**
	 * @var \Aqua\Http\Request
	 */
	public $request;

	/**
	 * @var \Aqua\Session\Session
	 */
	public $session;
	/**
	 * @var \Aqua\User\Account
	 */
	public $account;

	/**
	 * @var \Aqua\Event\EventDispatcher
	 */
	protected $_dispatcher;

	const FLASH_MESSAGE_KEY    = '__aquacore_flash_message';
	const SESSION_TOKEN_PREFIX = '__aquacore_token::';

	/**
	 * @param \Aqua\Http\Request  $request
	 * @param \Aqua\Http\Response $response
	 * @param \Aqua\Core\Settings $settings
	 */
	public function __construct(Request $request, Response $response, Settings $settings)
	{
		$this->_dispatcher = new EventDispatcher;
		$this->request     = $request;
		if(\Aqua\PROFILE === 'INSTALLER') {
			$this->session = new \stdClass();
			return;
		}
		$this->session     = new Session(
			$request->cookie($settings->get('name', ''), ''),
			$request->ip,
			$request->header('User-Agent'),
			$response,
			$settings->toArray()
		);
		$this->session->open();
		$loginSettings = App::settings()->get('account')->get('persistent_login');
		if($this->session->userId !== null) {
			$this->account = Account::get($this->session->userId);
			if($this->session->get('403-redirect')) {
				App::dispatcher()->attach('render-end', array( $this, '_403Callback' ));
			}
		} else if($loginSettings->get('enable', false) &&
		          ($key = $this->request->cookie($loginSettings->get('name', 'ac_persistent_login'), null))) {
			$response = App::response();
			try {
				$oldKey = $userId = null;
				if(PersistentLogin::getKey($key, $userId, $oldKey) &&
				   ($user = PersistentLogin::authenticate($key))) {
					if($user->status === Account::STATUS_BANNED || $user->status === Account::STATUS_SUSPENDED) {
						LoginLog::logSql($oldKey,
						                 $user->id,
						                 LoginLog::TYPE_PERSISTENT,
						                 LoginLog::STATUS_ACCESS_DENIED);
						$response->setCookie($loginSettings->get('name'), array(
							'value' => '',
							'ttl'   => -3600
						));
					} else {
						LoginLog::logSql($oldKey,
						                 $user->id,
						                 LoginLog::TYPE_PERSISTENT,
						                 LoginLog::STATUS_OK);
						$response->status(302)->redirect($this->request->uri->url());
						$this->login($user);
						$response->setCookie($loginSettings->get('name'), array(
							'value'     => $key,
							'ttl'       => 315360000,
							'http_only' => (bool)$loginSettings->get('http_only', true),
							'secure'    => (bool)$loginSettings->get('secure', false)
						));
						if($this->request->method === 'GET') {
							$response->send();
							die;
						}
					}
				} else {
					LoginLog::logSql($oldKey,
					                 $userId,
					                 LoginLog::TYPE_PERSISTENT,
					                 LoginLog::STATUS_INVALID_CREDENTIALS);
					$response->setCookie($loginSettings->get('name'), array(
						'value' => '',
						'ttl'   => -3600
					));
				}
			} catch(\Exception $exception) {
				ErrorLog::logSql($exception);
				$response->setCookie($loginSettings->get('name'), array(
					'value' => '',
					'ttl'   => -3600
				));
			}
		}
	}

	/**
	 * @return \Aqua\User\Role
	 */
	public function role()
	{
		return ($this->loggedIn() ? Role::get($this->account->roleId) : Role::get(Role::ROLE_GUEST));
	}

	/**
	 * @return bool
	 */
	public function loggedIn()
	{
		return $this->account !== null;
	}

	/**
	 * @param string $type
	 * @param string $title
	 * @param string $message
	 * @return \Aqua\Core\User
	 */
	public function addFlash($type, $title, $message)
	{
		$this->session->flash(
				self::FLASH_MESSAGE_KEY,
				array_merge(
					$this->session->get(self::FLASH_MESSAGE_KEY, array()),
					array(array(
						'type'    => $type,
						'title'   => $title,
						'message' => $message
				))
			));

		return $this;
	}

	/**
	 * @return array
	 */
	public function getFlash()
	{
		return $this->session->get(self::FLASH_MESSAGE_KEY);
	}

	/**
	 * @param string $key
	 * @param int    $length
	 * @return string
	 */
	public function setToken($key, $length = 32)
	{
		if(!($token = $this->session->get(self::SESSION_TOKEN_PREFIX . $key))) {
			$token = bin2hex(secure_random_bytes($length));
			$this->session->set(self::SESSION_TOKEN_PREFIX . $key, $token);
		}

		return $token;
	}

	/**
	 * @param string $key
	 * @return string
	 */
	public function getToken($key)
	{
		$key   = self::SESSION_TOKEN_PREFIX . $key;
		$token = $this->session->get($key, '');
		$this->session->delete($key);

		return $token;
	}

	/**
	 * @param \Aqua\Ragnarok\Server\CharMap $charmap
	 * @return \Aqua\Ragnarok\Cart
	 */
	public function cart(CharMap $charmap)
	{
		$key = "ragnarok-cart::{$charmap->server->key}.{$charmap->key}";
		if(!($cart = $this->session->get($key)) || !($cart instanceof Cart)) {
			$cart = new Cart($charmap);
			$this->session->set($key, $cart);
		}
		return $cart;
	}

	/**
	 * @var \Aqua\User\Account $account
	 * @return \Aqua\Core\User
	 */
	public function login(Account $account)
	{
		$this->session->userId = $account->id;
		$this->account         = $account;
		$feedback              = array( $account );
		$this->notify('login', $feedback);

		return $this;
	}

	/**
	 * @return \Aqua\Core\User
	 */
	public function logout()
	{
		if(!$this->loggedIn()) {
			return $this;
		}
		$settings = App::settings()->get('account')->get('persistent_login');
		if($settings->get('enable', false) &&
		   ($key = $this->request->cookie($settings->get('name', 'ac_persistent_login'), null))) {
			PersistentLogin::delete($key);
		}
		$feedback              = array( $this->account );
		$this->session->userId = null;
		$this->account         = null;
		$this->notify('logout', $feedback);

		return $this;
	}

	public function _403Callback()
	{
		if(App::response()->status === 403) {
			App::response()->status(302)->redirect(\Aqua\WORKING_URL);
		}
		$this->session->delete('403-redirect');
		$this->session->keep(self::FLASH_MESSAGE_KEY, 1);
	}

	public function attach($event, $listener)
	{
		$this->_dispatcher->attach("user.$event", $listener);

		return $this;
	}

	public function detach($event, $listener)
	{
		$this->_dispatcher->detach("user.$event", $listener);

		return $this;
	}

	public function notify($event, &$feedback = array())
	{
		return $this->_dispatcher->notify("user.$event", $feedback);
	}
}
