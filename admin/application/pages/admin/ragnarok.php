<?php
namespace Page\Admin;

use Aqua\Core\App;
use Aqua\Core\Settings;
use Aqua\Log\ErrorLog;
use Aqua\Site\Page;
use Aqua\SQL\Search;
use Aqua\UI\Form;
use Aqua\UI\Menu;
use Aqua\UI\Pagination;
use Aqua\UI\Template;
use Aqua\Ragnarok\Account;
use Aqua\Ragnarok\Server;
use Aqua\UI\Theme;

class Ragnarok
extends Page
{
	/**
	 * @var \Aqua\Ragnarok\Server
	 */
	public $server;
	public static $accountsPerPage = 20;
	public static $itemsPerPage    = 20;
	public static $logsPerPage     = 20;

	public function run()
	{
		$this->server = App::$activeServer;
		if($this->server) {
			$nav      = new Menu;
			$base_url = ac_build_url(array(
					'path'   => array('r', $this->server->key),
					'action' => ''
				));
			$nav->append('server', array(
					'title' => htmlspecialchars($this->server->name),
					'url'   => $base_url . 'index'
				))->append('settings', array(
					'title' => __('ragnarok-server', 'settings'),
					'url'   => $base_url . 'edit'
				))->append('accounts', array(
					'title' => __('ragnarok-server', 'accounts'),
					'url'   => $base_url . 'account'
				))->append('login-log', array(
					'title' => __('ragnarok-server', 'login-log'),
					'url'   => $base_url . 'login'
				))->append('ban-log', array(
					'title' => __('ragnarok-server', 'ban-log'),
					'url'   => $base_url . 'ban'
				))->append('password-reset-log', array(
					'title' => __('ragnarok-server', 'password-reset-log'),
					'url'   => $base_url . 'password'
				));
			$this->theme->set('nav', $nav);
		} else {
			if($this->request->uri->action !== 'index') {
				$this->error(404);
			}
		}
	}

	public function index_action()
	{
		if($this->server) {
			if(empty($this->server->charmap)) {
				$this->response->status(302)->redirect(ac_build_url(array(
						'path' => array( 'r', $this->server->key, 'server' )
					)));
			} else if($this->server->charmapCount === 1) {
				$server = current($this->server->charmap);
				$this->response->status(302)->redirect(ac_build_url(array(
					'path' => array( 'r', $this->server->key, $server->key )
					)));
			} else {
				$this->server_index();
			}

			return;
		}
		try {
			$frm = new Form($this->request);
			$frm->input('name', true)
			    ->type('text')
			    ->required()
			    ->setLabel(__('ragnarok-server', 'server-name-label'));
			$frm->input('key', true)
			    ->type('text')
			    ->attr('maxlength', 255)
			    ->required()
			    ->setLabel(__('ragnarok-server', 'server-key-label'))
			    ->setDescription(__('ragnarok-server', 'server-key-desc'));
			$frm->input('host', true)
			    ->type('text')
			    ->required()
			    ->setLabel(__('ragnarok-server', 'login-server-host-label'));
			$frm->input('port', true)
			    ->type('number')
			    ->required()
			    ->value(6900, false)
			    ->setLabel(__('ragnarok-server', 'login-server-port-label'));
			$frm->select('emulator', true)
			    ->value(array(
					'1' => __('ragnarok-emulator', 1),
					'2' => __('ragnarok-emulator', 2),
				))
			    ->required()
			    ->selected(1, false)
			    ->setLabel(__('ragnarok-server', 'emulator-label'));
			$frm->input('login-host', true)
			    ->type('text')
			    ->required()
			    ->setLabel(__('ragnarok-server', 'db-host-label'));
			$frm->input('login-port', true)
			    ->type('number')
			    ->attr('min', 0)
			    ->value(3306, false)
			    ->required()
			    ->setLabel(__('ragnarok-server', 'db-port-label'));
			$frm->input('login-database', true)
			    ->type('text')
			    ->required()
			    ->setLabel(__('ragnarok-server', 'db-database-label'));
			$frm->input('login-username', true)
			    ->type('text')
			    ->required()
			    ->setLabel(__('ragnarok-server', 'db-username-label'));
			$frm->input('login-password')
			    ->type('password')
			    ->setLabel(__('ragnarok-server', 'db-password-label'));
			$frm->input('login-timezone', true)
			    ->type('text')
			    ->setLabel(__('ragnarok-server', 'db-timezone-label'))
			    ->setDescription(__('ragnarok-server', 'db-timezone-desc'));
			$frm->input('login-charset', true)
			    ->type('text')
			    ->value('UTF8', null)
			    ->setLabel(__('ragnarok-server', 'db-charset-label'));
			$frm->input('log-host', true)
			    ->type('text')
			    ->required()
			    ->setLabel(__('ragnarok-server', 'db-host-label'));
			$frm->input('log-port', true)
			    ->type('number')
			    ->attr('min', 0)
			    ->required()
			    ->value(3306, false)
			    ->setLabel(__('ragnarok-server', 'db-port-label'));
			$frm->input('log-database', true)
			    ->type('text')
			    ->required()
			    ->setLabel(__('ragnarok-server', 'db-database-label'));
			$frm->input('log-username', true)
			    ->type('text')
			    ->required()
			    ->setLabel(__('ragnarok-server', 'db-username-label'));
			$frm->input('log-password')
			    ->type('password')
			    ->setLabel(__('ragnarok-server', 'db-password-label'));
			$frm->input('log-timezone', true)
			    ->type('text')
			    ->setLabel(__('ragnarok-server', 'db-timezone-label'))
			    ->setDescription(__('ragnarok-server', 'db-timezone-desc'));
			$frm->input('log-charset', true)
			    ->type('text')
			    ->value('UTF8', null)
			    ->setLabel(__('ragnarok-server', 'db-charset-label'));
			$frm->checkbox('md5', true)
			    ->value(array('1' => ''))
			    ->checked(1, false)
			    ->setLabel(__('ragnarok-server', 'use-md5-label'));
			$frm->checkbox('pincode', true)
			    ->value(array('1' => ''))
			    ->setLabel(__('ragnarok-server', 'use-pincode-label'));
			$frm->checkbox('link', true)
			    ->value(array('1' => ''))
			    ->setLabel(__('ragnarok-server', 'link-label'))
			    ->setDescription(__('ragnarok-server', 'link-desc'));
			$frm->checkbox('cs-login', true)
			    ->value(array('1' => ''))
			    ->setLabel(__('ragnarok-server', 'case-sensitive-login-label'));
			$frm->input('default-group-id', true)
			    ->type('number')
			    ->attr('min', 0)
			    ->attr('max', 99)
			    ->value(0, false)
			    ->setLabel(__('ragnarok-server', 'default-group-id-label'));
			$frm->input('slots', true)
			    ->type('number')
			    ->attr('min', 0)
			    ->attr('max', 9)
			    ->value(0, false)
			    ->setLabel(__('ragnarok-server', 'use-slots-label'))
			    ->setDescription(__('ragnarok-server', 'use-slots-desc'));
			$frm->input('max-accounts', true)
			    ->type('number')
			    ->attr('min', 0)
			    ->value(0, false)
			    ->setLabel(__('ragnarok-server', 'max-acc-label'))
			    ->setDescription(__('ragnarok-server', 'max-acc-desc'));
			$frm->input('timeout', true)
			    ->type('number')
			    ->attr('min', 1)
			    ->value(1, false)
			    ->setLabel(__('ragnarok-server', 'status-timeout-label'))
			    ->setDescription(__('ragnarok-server', 'status-timeout-desc'));
			$frm->input('cache', true)
			    ->type('number')
			    ->attr('min', 1)
			    ->value(180, false)
			    ->setLabel(__('ragnarok-server', 'status-cache-label'))
			    ->setDescription(__('ragnarok-server', 'status-cache-desc'));
			$frm->input('password-min', true)
			    ->type('number')
			    ->attr('min', 1)
			    ->required()
			    ->value(4, false)
			    ->setLabel(__('ragnarok-server', 'password-min-label'));
			$frm->input('password-max', true)
			    ->type('number')
			    ->attr('min', 1)
			    ->required()
			    ->value(32, false)
			    ->setLabel(__('ragnarok-server', 'password-regex-label'))
			    ->setLabel(__('ragnarok-server', 'password-max-label'));
			$frm->input('password-regex', true)
			    ->type('text')
			    ->setLabel(__('ragnarok-server', 'password-regex-label'))
			    ->setDescription(__('ragnarok-server', 'password-regex-desc'));
			$frm->input('username-min', true)
			    ->type('number')
			    ->attr('min', 1)
			    ->required()
			    ->value(4, false)
			    ->setLabel(__('ragnarok-server', 'username-min-label'));
			$frm->input('username-max', true)
			    ->type('number')
			    ->attr('min', 1)
			    ->attr('max', 23)
			    ->required()
			    ->value(23, false)
			    ->setLabel(__('ragnarok-server', 'username-max-label'));
			$frm->input('username-regex', true)
			    ->type('text')
			    ->setLabel(__('ragnarok-server', 'username-regex-label'))
			    ->setDescription(__('ragnarok-server', 'username-regex-desc'));
			$frm->submit();
			$dbh  = null;
			$ldbh = null;
			$self = $this;
			$frm->validate(function (Form $frm, &$message) use ($self, &$dbh, &$ldbh) {
				$error = false;
				if(Server::get(strtolower($frm->request->getString('key')))) {
					$frm->field('name')->setWarning(__('ragnarok-server', 'duplicate-key'));
					$error = true;
				}
				$ids   = $frm->request->getArray('group-id', false);
				$names = $frm->request->getArray('group-name', false);
				if($ids === false || $names === false) {
					return false;
				}
				if(count($ids) !== count($names)) {
					$message = __('ragnarok-server', 'group-count-mismatch');

					return false;
				}
				foreach($ids as $i) {
					$i = trim($i);
					if($i === '') continue;
					if(!ctype_digit($i)) {
						$message = __('ragnarok-server', 'group-id-not-number');

						return false;
					}
					$i = (int)$i;
					if($i < 0 || $i > 99) {
						$message = __('ragnarok-server', 'group-id-wrong-value');

						return false;
					}
				}
				foreach($names as $n) {
					$n = trim($n);
					if($n === '') continue;
					if(!is_string($n)) {
						return false;
					}
					if(strlen($n) > 255) {
						$message = __('ragnarok-server', 'group-name-len');

						return false;
					}
				}
				if($frm->request->getInt('password-min') > $frm->request->getInt('password-max')) {
					$frm->field('password-max')->setWarning(__('ragnarok-server', 'max-password-err'));
					$error = true;
				}
				if($frm->request->getInt('username-min') > $frm->request->getInt('username-max')) {
					$frm->field('username-max')->setWarning(__('ragnarok-server', 'max-username-err'));
					$error = true;
				}
				if($regex = $frm->request->getString('password-regex')) {
					@preg_match($regex, '');
					if($mes = ac_pcre_error_str()) {
						$frm->field('password-regex')->setWarning($mes);
						$error = true;
					}
				}
				if($regex = $frm->request->getString('username-regex')) {
					@preg_match($regex, '');
					if($mes = ac_pcre_error_str()) {
						$frm->field('username-regex')->setWarning($mes);
						$error = true;
					}
				}
				try {
					$dbh = ac_mysql_connection(array(
							'host'     => $frm->request->getString('login-host'),
							'port'     => $frm->request->getInt('login-port'),
							'username' => $frm->request->getString('login-username'),
							'password' => $frm->request->getString('login-password'),
							'database' => $frm->request->getString('login-database'),
							'timezone' => $frm->request->getString('login-timezone'),
							'options'  => array(\PDO::ATTR_TIMEOUT => 5)
						));
				} catch(\PDOException $exception) {
					if(($info = $exception->errorInfo) && count($info) === 3) {
						$code = $info[1];
					} else {
						$code = $exception->getCode();
					}
					switch($code) {
						case 2002:
							$frm->field('login-host')->setWarning(__('exception',
							                                         'pdo-connection-failed',
							                                         $exception->getCode(),
							                                         $exception->getMessage()));
							break;
						case 1298:
							$frm->field('login-timezone')->setWarning(__('exception',
							                                             'pdo-invalid-timezone',
							                                             $exception->getCode(),
							                                             $exception->getMessage()));
							break;
						case 1045:
							$frm->field('login-password')->setWarning(__('exception',
							                                             'pdo-access-denied',
							                                             $exception->getCode(),
							                                             $exception->getMessage()));
							break;
						case 1049:
							$frm->field('login-database')->setWarning(__('exception',
							                                             'pdo-unknown-db',
							                                             $exception->getCode(),
							                                             $exception->getMessage()));
							break;
						default:
							$message = __('exception',
							              'pdo-exception',
							              $exception->getCode(),
							              $exception->getMessage());
							break;
					}

					return false;
				}
				try {
					$ldbh = ac_mysql_connection(array(
						'host'     => $frm->request->getString('log-host'),
						'port'     => $frm->request->getInt('log-port'),
						'username' => $frm->request->getString('log-username'),
						'password' => $frm->request->getString('log-password'),
						'database' => $frm->request->getString('log-database'),
						'timezone' => $frm->request->getString('log-timezone'),
						'options'  => array(\PDO::ATTR_TIMEOUT => 5)
					));
				} catch(\PDOException $exception) {
					if(($info = $exception->errorInfo) && count($info) === 3) {
						$code = $info[1];
					} else {
						$code = $exception->getCode();
					}
					switch($code) {
						case 2002:
							$frm->field('log-host')->setWarning(__('exception',
							                                       'pdo-connection-failed',
							                                       $exception->getCode(),
							                                       $exception->getMessage()));
							break;
						case 1298:
							$frm->field('log-timezone')->setWarning(__('exception',
							                                           'pdo-invalid-timezone',
							                                           $exception->getCode(),
							                                           $exception->getMessage()));
							break;
						case 1045:
							$frm->field('log-password')->setWarning(__('exception',
							                                           'pdo-access-denied',
							                                           $exception->getCode(),
							                                           $exception->getMessage()));
							break;
						case 1049:
							$frm->field('log-database')->setWarning(__('exception',
							                                           'pdo-unknown-db',
							                                           $exception->getCode(),
							                                           $exception->getMessage()));
							break;
						default:
							$message = __('exception',
							              'pdo-exception',
							              $exception->getCode(),
							              $exception->getMessage());
							break;
					}

					return false;
				}

				return !$error;
			});
			if($frm->status !== Form::VALIDATION_SUCCESS) {
				$dbh         = null;
				$ldbh        = null;
				$this->title = $this->theme->head->section = __('ragnarok-server', 'new-server');
				$tpl         = new Template;
				$tpl->set('form', $frm)
				    ->set('page', $this);
				echo $tpl->render('admin/ragnarok/new-server');

				return;
			}
			try {
				/**
				 * @var $dbh  \PDO
				 * @var $ldbh \PDO
				 */
				$dbh->exec(file_get_contents(\Aqua\ROOT . '/schema/login/ac_groups.sql'));
				$dbh->exec(file_get_contents(\Aqua\ROOT . '/schema/login/ac_login_settings.sql'));
				try {
					$dbh->exec(file_get_contents(\Aqua\ROOT . '/schema/login/login.ac_options.sql'));
				} catch(\PDOException $e) { }
				try {
					$dbh->exec(file_get_contents(\Aqua\ROOT . '/schema/login/login.ac_user_id.sql'));
				} catch(\PDOException $e) { }
				$ldbh->exec(file_get_contents(\Aqua\ROOT . '/schema/login-log/ac_ban_log.sql'));
				$ldbh->exec(file_get_contents(\Aqua\ROOT . '/schema/login-log/ac_password_reset_log.sql'));
				$sth = $dbh->prepare("
				REPLACE INTO `ac_login_settings` VALUES
				 ('host', :host)
				,('port', :port)
				,('status-timeout', :timeout)
				,('status-cache', :cache)
				,('default-slots', :slots)
				,('use-md5', :md5)
				,('use-pincode', :pincode)
				,('link-accounts', :link)
				,('default-group-id', :groupid)
				,('max-accounts', :acc)
				,('case-sensitive-username', :cs_usr)
				,('password-min-len', :passwd_min)
				,('password-max-len', :passwd_max)
				,('password-regex', :passwd_regex)
				,('username-min-len', :usr_min)
				,('username-max-len', :usr_max)
				,('username-regex', :usr_regex)
				;
				");
				$sth->bindValue(':host', $this->request->getString('host', ''));
				$sth->bindValue(':port', $this->request->getInt('port', 0));
				$sth->bindValue(':timeout', $this->request->getInt('timeout', 1));
				$sth->bindValue(':cache', $this->request->getInt('cache', 1));
				$sth->bindValue(':slots', $this->request->getInt('slots', 0));
				$sth->bindValue(':md5', $this->request->getInt('md5', 0) ? '1' : '');
				$sth->bindValue(':pincode', $this->request->getInt('pincode', 0) ? '1' : '');
				$sth->bindValue(':link', $this->request->getInt('link', 0) ? '1' : '');
				$sth->bindValue(':groupid', $this->request->getInt('default-group-id', 0));
				$sth->bindValue(':acc', $this->request->getInt('max-accounts', 0));
				$sth->bindValue(':cs_usr', $this->request->getInt('cs-login', 0) ? '1' : '');
				$sth->bindValue(':passwd_min', $this->request->getInt('password-min', 1));
				$sth->bindValue(':passwd_max', $this->request->getInt('password-max', 1));
				$sth->bindValue(':passwd_regex', $this->request->getString('password-regex', ''));
				$sth->bindValue(':usr_min', $this->request->getInt('username-min', 4));
				$sth->bindValue(':usr_max', $this->request->getInt('username-max', 23));
				$sth->bindValue(':usr_regex', $this->request->getString('username-regex', ''));
				$sth->execute();
				$sth->closeCursor();
				$ids   = $this->request->getArray('group-id', array());
				$names = $this->request->getArray('group-name', array());
				$count = count($ids);
				$sth   = $dbh->prepare("REPLACE INTO `ac_groups` VALUES (:id, :name)");
				for($i = 0; $i < $count; ++$i) {
					if($ids[$i] === '' || $names[$i] === '') {
						continue;
					}
					$sth->bindValue(':id', (int)trim($ids[$i]), \PDO::PARAM_INT);
					$sth->bindValue(':name', trim($names[$i]), \PDO::PARAM_STR);
					$sth->execute();
				}
				$sth->closeCursor();
				$key      = strtolower($this->request->getString('key'));
				$file     = \Aqua\ROOT . '/settings/ragnarok.php';
				$settings = new Settings;
				$settings->import($file);
				$settings->set(strtolower($key), array(
					'name'     => $this->request->getString('name', ''),
					'emulator' => $this->request->getInt('emulator', Server::EMULATOR_HERCULES),
					'login'    => array(
						'database_name'     => $this->request->getString('login-database'),
						'log_database_name' => $this->request->getString('log-database'),
						'db'                => array(
							'host'       => $this->request->getString('login-host'),
							'port'       => $this->request->getString('login-port'),
							'username'   => $this->request->getString('login-username'),
							'password'   => $this->request->getString('login-password'),
							'timezone'   => $this->request->getString('login-timezone'),
							'charset'    => $this->request->getString('login-charset'),
							'persistent' => false
						),
						'log_db'            => array(
							'host'       => $this->request->getString('log-host'),
							'port'       => $this->request->getString('log-port'),
							'username'   => $this->request->getString('log-username'),
							'password'   => $this->request->getString('log-password'),
							'timezone'   => $this->request->getString('log-timezone'),
							'charset'    => $this->request->getString('log-charset'),
							'persistent' => false
						),
						'tables'            => array(),
						'log_tables'        => array()
					),
					'charmap'  => array()
					));
				$settings->export($file);
				$this->response->status(302)->redirect(ac_build_url(array('path' => array('ro', $key))));
			} catch(\Exception $exception) {
				ErrorLog::logSql($exception);
				App::user()->addFlash('error', null, __('application', 'unexpected-error'));
				$this->response->status(302)->redirect(App::request()->uri->url());

				return;
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function server_index()
	{
		try {
			$this->title = $this->theme->head->section = htmlspecialchars($this->server->name);
			$tpl         = new Template;
			$tpl->set('server', $this->server)
			    ->set('page', $this);
			echo $tpl->render('admin/ragnarok/login');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function edit_action()
	{
		try {
			$frm = new Form($this->request);
			$frm->input('name', true)
			    ->type('text')
			    ->required()
			    ->value(htmlspecialchars($this->server->name), false)
			    ->setLabel(__('ragnarok-server', 'server-name-label'));
			$frm->input('key', true)
			    ->type('text')
			    ->attr('maxlength', 255)
			    ->required()
			    ->value(htmlspecialchars($this->server->key), false)
			    ->setLabel(__('ragnarok-server', 'server-key-label'))
			    ->setDescription(__('ragnarok-server', 'server-key-desc'));
			$frm->input('host', true)
			    ->type('text')
			    ->required()
			    ->value(htmlspecialchars($this->server->login->getOption('host')), false)
			    ->setLabel(__('ragnarok-server', 'login-server-host-label'));
			$frm->input('port', true)
			    ->type('number')
			    ->required()
			    ->value($this->server->login->getOption('port'), false)
			    ->setLabel(__('ragnarok-server', 'login-server-port-label'));
			$frm->select('emulator', true)
			    ->value(array(
					'1' => __('ragnarok-emulator', 1),
					'2' => __('ragnarok-emulator', 2),
				))
			    ->required()
			    ->selected($this->server->emulator, false)
			    ->setLabel(__('ragnarok-server', 'emulator-label'));
			$frm->checkbox('md5', true)
			    ->value(array('1' => ''))
			    ->checked(1, false)
			    ->checked($this->server->login->getOption('use-md5'), false)
			    ->setLabel(__('ragnarok-server', 'use-md5-label'));
			$frm->checkbox('pincode', true)
			    ->value(array('1' => ''))
			    ->checked($this->server->login->getOption('use-pincode'), false)
			    ->setLabel(__('ragnarok-server', 'use-pincode-label'));
			$frm->checkbox('link', true)
			    ->value(array('1' => ''))
			    ->checked($this->server->login->getOption('link-accounts'), false)
			    ->setLabel(__('ragnarok-server', 'link-label'))
			    ->setDescription(__('ragnarok-server', 'link-desc'));
			$frm->checkbox('cs-login', true)
			    ->value(array('1' => ''))
			    ->checked($this->server->login->getOption('case-sensitive-username'), false)
			    ->setLabel(__('ragnarok-server', 'case-sensitive-login-label'));
			$frm->input('default-group-id', true)
			    ->type('number')
			    ->attr('min', 0)
			    ->attr('max', 99)
			    ->value($this->server->login->getOption('default-group-id'), false)
			    ->setLabel(__('ragnarok-server', 'default-group-id-label'));
			$frm->input('slots', true)
			    ->type('number')
			    ->attr('min', 0)
			    ->attr('max', 9)
			    ->value($this->server->login->getOption('default-slots'), false)
			    ->setLabel(__('ragnarok-server', 'use-slots-label'))
			    ->setDescription(__('ragnarok-server', 'use-slots-desc'));
			$frm->input('max-accounts', true)
			    ->type('number')
			    ->attr('min', 0)
			    ->value($this->server->login->getOption('max-accounts'), false)
			    ->setLabel(__('ragnarok-server', 'max-acc-label'))
			    ->setDescription(__('ragnarok-server', 'max-acc-desc'));
			$frm->input('timeout', true)
			    ->type('number')
			    ->attr('min', 1)
			    ->value($this->server->login->getOption('status-timeout'), false)
			    ->setLabel(__('ragnarok-server', 'status-timeout-label'))
			    ->setDescription(__('ragnarok-server', 'status-timeout-desc'));
			$frm->input('cache', true)
			    ->type('number')
			    ->attr('min', 1)
			    ->value($this->server->login->getOption('status-cache'), false)
			    ->setLabel(__('ragnarok-server', 'status-cache-label'))
			    ->setDescription(__('ragnarok-server', 'status-cache-desc'));
			$frm->input('password-min', true)
			    ->type('number')
			    ->attr('min', 1)
			    ->required()
			    ->value($this->server->login->getOption('password-min-len'), false)
			    ->setLabel(__('ragnarok-server', 'password-min-label'));
			$frm->input('password-max', true)
			    ->type('number')
			    ->attr('min', 1)
			    ->required()
			    ->value($this->server->login->getOption('password-max-len'), false)
			    ->setLabel(__('ragnarok-server', 'password-regex-label'))
			    ->setLabel(__('ragnarok-server', 'password-max-label'));
			$frm->input('password-regex', true)
			    ->type('text')
			    ->value($this->server->login->getOption('password-regex'), false)
			    ->setLabel(__('ragnarok-server', 'password-regex-label'))
			    ->setDescription(__('ragnarok-server', 'password-regex-desc'));
			$frm->input('username-min', true)
			    ->type('number')
			    ->attr('min', 1)
			    ->required()
			    ->value($this->server->login->getOption('username-min-len'), false)
			    ->setLabel(__('ragnarok-server', 'username-min-label'));
			$frm->input('username-max', true)
			    ->type('number')
			    ->attr('min', 1)
			    ->attr('max', 23)
			    ->required()
			    ->value($this->server->login->getOption('username-max-len'), false)
			    ->setLabel(__('ragnarok-server', 'username-max-label'));
			$frm->input('username-regex', true)
			    ->type('text')
			    ->value($this->server->login->getOption('username-regex'), false)
			    ->setLabel(__('ragnarok-server', 'username-regex-label'))
			    ->setDescription(__('ragnarok-server', 'username-regex-desc'));
			$frm->input('submit')
			    ->type('submit')
			    ->value(__('application', 'submit'));
			$frm->input('delete')
			    ->type('submit')
			    ->value(__('ragnarok-server', 'delete-server'));
			$self = $this;
			$frm->validate(function (Form $frm, &$message) use ($self) {
				$error = false;
				$key   = strtolower($frm->request->getString('key'));
				if($self->server->key !== $key && Server::get($key)) {
					$frm->field('name')->setWarning(__('ragnarok-server', 'duplicate-key'));
					$error = true;
				}
				$ids   = $frm->request->getArray('group-id', false);
				$names = $frm->request->getArray('group-name', false);
				if($ids === false || $names === false) {
					return false;
				}
				if(count($ids) !== count($names)) {
					$message = __('ragnarok-server', 'group-count-mismatch');

					return false;
				}
				foreach($ids as $i) {
					$i = trim($i);
					if($i === '') continue;
					if(!ctype_digit($i)) {
						$message = __('ragnarok-server', 'group-id-not-number');

						return false;
					}
					$i = (int)$i;
					if($i < 0 || $i > 99) {
						$message = __('ragnarok-server', 'group-id-wrong-value');

						return false;
					}
				}
				foreach($names as $n) {
					$n = trim($n);
					if($n === '') continue;
					if(!is_string($n)) {
						return false;
					}
					if(strlen($n) > 255) {
						$message = __('ragnarok-server', 'group-name-len');

						return false;
					}
				}
				if($frm->request->getInt('password-min') > $frm->request->getInt('password-max')) {
					$frm->field('password-max')->setWarning(__('ragnarok-server', 'max-password-err'));
					$error = true;
				}
				if($frm->request->getInt('username-min') > $frm->request->getInt('username-max')) {
					$frm->field('username-max')->setWarning(__('ragnarok-server', 'max-username-err'));
					$error = true;
				}
				if($regex = $frm->request->getString('password-regex')) {
					@preg_match($regex, '');
					if($mes = ac_pcre_error_str()) {
						$frm->field('password-regex')->setWarning($mes);
						$error = true;
					}
				}
				if($regex = $frm->request->getString('username-regex')) {
					@preg_match($regex, '');
					if($mes = ac_pcre_error_str()) {
						$frm->field('username-regex')->setWarning($mes);
						$error = true;
					}
				}

				return !$error;
			});
			if($frm->status !== Form::VALIDATION_SUCCESS) {
				$this->title =
				$this->theme->head->section =
					__('ragnarok-server', 'edit-server', htmlspecialchars($this->server->name));
				$tpl         = new Template;
				$tpl->set('server', $this->server)
				    ->set('form', $frm)
				    ->set('page', $this);
				echo $tpl->render('admin/ragnarok/login-settings');

				return;
			}
			$file     = \Aqua\ROOT . '/settings/ragnarok.php';
			$settings = new Settings;
			$settings->import($file);
			try {
				if(!empty($this->request->data['delete'])) {
					foreach($this->server->charmap as $charmap) {
						$charmap->flushCache();
					}
					$this->server->login->flushCache();
					$this->server->login->log->flushCache();
					$settings->delete($this->server->key);
					$this->response->status(302)->redirect(ac_build_url(array()));
					App::user()
					   ->addFlash('success',
					              null,
					              __('ragnarok-server', 'server-deleted', htmlspecialchars($this->server->name)));
				} else {
					$this->server->login->setOption(array(
							'use-md5'                 => $this->request->getInt('md5') ? '1' : '',
							'use-pincode'             => $this->request->getInt('pincode') ? '1' : '',
							'link-accounts'           => $this->request->getInt('link') ? '1' : '',
							'case-sensitive-username' => $this->request->getInt('cs-login') ? '1' : '',
							'max-accounts'            => $this->request->getInt('max-accounts'),
							'default-group-id'        => $this->request->getInt('default-group-id'),
							'default-slots'           => $this->request->getInt('slots'),
							'status-timeout'          => $this->request->getInt('timeout'),
							'status-cache'            => $this->request->getInt('cache'),
							'password-min-len'        => $this->request->getInt('password-min'),
							'password-max-len'        => $this->request->getInt('password-max'),
							'password-regex'          => $this->request->getString('password-regex'),
							'username-min-len'        => $this->request->getInt('username-min'),
							'username-max-len'        => $this->request->getInt('username-max'),
							'username-regex'          => $this->request->getString('username-regex'),
							'host'                    => $this->request->getString('host'),
							'port'                    => $this->request->getInt('port')
						));
					$ids   = $frm->request->getArray('group-id', false);
					$names = $frm->request->getArray('group-name', false);
					$tbl   = $this->server->login->table('ac_groups');
					$this->server->login->connection()->exec("TRUNCATE TABLE $tbl");
					$sth   =
						$this->server->login->connection()->prepare("REPLACE INTO $tbl (id, `name`) VALUES (?, ?)");
					$count = count($ids);
					for($i = 0; $i < $count; ++$i) {
						if($ids[$i] === '' || $names[$i] === '') continue;
						$sth->bindValue(1, $ids[$i], \PDO::PARAM_INT);
						$sth->bindValue(2, $names[$i], \PDO::PARAM_STR);
						$sth->execute();
						$sth->closeCursor();
					}
					$this->server->login->fetchGroups(true);
					if(($name = $this->request->getString('name')) && $name !== $this->server->name) {
						$settings->get($this->server->key)->set('name', $name);
						$this->server->name = $name;
					}
					if(($key = strtolower($this->request->getString('key'))) && $key !== $this->server->key) {
						$settings->set($key, $settings->get($this->server->key));
						$settings->delete($this->server->key);
						$this->server->key = $key;
					}
					App::user()
					   ->addFlash('success',
					              null,
					              __('ragnarok-server', 'updated', htmlspecialchars($this->server->name)));
					$this->response->status(302)->redirect(ac_build_url(array(
							'path'   => array('ro', $this->server->key),
							'action' => 'edit'
						)));
				}
				$settings->export($file);
			} catch(\Exception $exception) {
				ErrorLog::logSql($exception);
				$this->response->status(302)->redirect(ac_build_url(array(
						'path'   => array('ro', $this->server->key),
						'action' => 'edit'
					)));
				App::user()->addFlash('error', null, __('application', 'unexpected-error'));
			}

			return;
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function account_action()
	{
		try {
			$this->title = $this->theme->head->section = __('ragnarok-server', 'account');
			$where       = array( 'id' => array(Search::SEARCH_HIGHER, Server\Login::MIN_ACCOUNT_ID - 1) );
			if($x = $this->request->uri->getString('u')) {
				$where['username'] = array(Search::SEARCH_LIKE, '%' . addcslashes($x, '%_\\') . '%');
			}
			if($x = $this->request->uri->getString('e')) {
				$where['email'] = array(Search::SEARCH_LIKE, '%' . addcslashes($x, '%_\\') . '%');
			}
			if($x = $this->request->uri->getString('ip')) {
				$where['last_ip_address'] = array(Search::SEARCH_LIKE, addcslashes($x, '%_\\') . '%');
			}
			if(($x = $this->request->uri->getInt('g', false)) !== false) {
				$where['username'] = intval($x);
			}
			if(($x = $this->request->uri->getArray('s', false)) && !empty($x)) {
				$x = array_map('intval', $x);
				array_unshift($x, Search::SEARCH_IN);
				$where['state'] = $x;
			}
			$current_page = $this->request->uri->getInt('page', 1, 1);
			$search       = $this->server->login->search()
				->calcRows()
				->where($where)
				->order(array('id' => 'ASC'))
				->limit(($current_page - 1) * self::$accountsPerPage, self::$accountsPerPage)
				->query();
			$pgn          = new Pagination(App::request()->uri,
			                               ceil($search->rowsFound / self::$accountsPerPage),
			                               $current_page);
			$tpl          = new Template;
			$tpl->set('accounts', $search->results)
			    ->set('account_count', $search->rowsFound)
			    ->set('paginator', $pgn)
			    ->set('page', $this);
			echo $tpl->render('admin/ragnarok/account/search');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function login_action()
	{
		try {
			$current_page = $this->request->uri->getInt('page', 1, 1);
			$search       = $this->server->login->log->searchLogin()
				->calcRows()
				->limit(($current_page - 1) * self::$logsPerPage, self::$logsPerPage)
				->order(array('date' => 'DESC'))
				->query();
			$pgn          = new Pagination(App::request()->uri,
			                               ceil($search->rowsFound / self::$logsPerPage),
			                               $current_page);
			$this->title  = $this->theme->head->section = __('ragnarok-server', 'login-log');
			$tpl          = new Template;
			$tpl->set('logs', $search->results)
			    ->set('log_count', $search->rowsFound)
			    ->set('paginator', $pgn)
			    ->set('page', $this);
			echo $tpl->render('admin/ragnarok/log/login-log');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function ban_action()
	{
		try {
			$current_page = $this->request->uri->getInt('page', 1, 1);
			$search       = $this->server->login->log->searchBan()
				->calcRows()
				->limit(($current_page - 1) * self::$logsPerPage, self::$logsPerPage)
				->order(array('ban_date' => 'DESC'))
				->query();
			$pgn          = new Pagination(App::request()->uri,
			                               ceil($search->rowsFound / self::$logsPerPage),
			                               $current_page);
			$this->title  = $this->theme->head->section = __('ragnarok-server', 'ban-log');
			$tpl          = new Template;
			$tpl->set('logs', $search->results)
			    ->set('log_count', $search->rowsFound)
			    ->set('paginator', $pgn)
			    ->set('page', $this);
			echo $tpl->render('admin/ragnarok/log/ban-log');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function password_action()
	{
		try {
			$current_page = $this->request->uri->getInt('page', 1, 1);
			$search       = $this->server->login->log->searchPasswordReset()
				->calcRows()
				->limit(($current_page - 1) * self::$logsPerPage, self::$logsPerPage)
				->order(array('request_date' => 'DESC'))
				->query();
			$pgn          = new Pagination(App::request()->uri,
			                               ceil($search->rowsFound / self::$logsPerPage),
			                               $current_page);
			$this->title  = $this->theme->head->section = __('ragnarok-server', 'ban-log');
			$tpl          = new Template;
			$tpl->set('logs', $search->results)
			    ->set('log_count', $search->rowsFound)
			    ->set('paginator', $pgn)
			    ->set('page', $this);
			echo $tpl->render('admin/ragnarok/log/password-reset-log');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function viewaccount_action($id = null)
	{
		try {
			if((int)$id < Server\Login::MIN_ACCOUNT_ID || !($account = $this->server->login->get($id))) {
				$this->error(404);

				return;
			}
			$characters = array();
			foreach($this->server->charmap as $charmap) {
				$characters[$charmap->key] = $charmap->charSearch()
					->where(array( 'account_id' => $account->id ))
					->order(array( 'slot' => 'ASC' ))
					->query()
					->results;
			}
			$this->title = $this->theme->head->section = __('ragnarok', 'account-info', htmlspecialchars($account->username));
			$tpl = new Template;
			$tpl->set('account', $account)
			    ->set('characters', $characters)
			    ->set('page', $this);
			echo $tpl->render('admin/ragnarok/account/view');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function storage_action($id = null, $charmap = null)
	{
		try {
			if($charmap === null) {
				$charmap = current($this->server->charmap);
			} else if(!($charmap = $this->server->charmap($charmap))) {
				$this->error(404);

				return;
			}
			if(!($account = $this->server->login->get($id, 'id'))) {
				$this->error(404);

				return;
			}
			$currentPage = $this->request->uri->getInt('page', 1, 1);
			$search = $charmap->storageSearch()
				->calcRows(true)
				->limit(($currentPage - 1) * self::$itemsPerPage, self::$itemsPerPage)
				->where(array( 'account_id' => $account->id ));
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function editaccount_action($id = null)
	{
		try {
			if((int)$id < Server\Login::MIN_ACCOUNT_ID || !($account = $this->server->login->get($id))) {
				$this->error(404);

				return;
			}
			$frm = new Form($this->request);
			$frm->input('username', true)
			    ->type('text')
			    ->required()
				->value(htmlspecialchars($account->username), false)
			    ->setLabel(__('ragnarok', 'username'));
			$frm->input('user', true)
			    ->type('text')
				->value($account->owner ? htmlspecialchars($account->user()->displayName) : '', false)
			    ->setLabel(__('ragnarok', 'user'));
			$frm->input('password')
			    ->type('password')
			    ->setLabel(__('ragnarok', 'password'));
			if($this->server->login->getOption('use-pincode', false)) {
				$frm->input('pincode')
					->type('text')
					->attr('maxlength', App::settings()->get('ragnarok')->get('pincode_max_len', 4))
					->attr('pattern', '/\d*/')
					->value(htmlspecialchars($account->pinCode))
					->setLabel(__('ragnarok', 'pincode'));
			}
			if((int)$this->server->login->getOption('default-slots', 0)) {
				$frm->input('slots')
					->type('number')
					->attr('min', 0)
					->required()
					->value($account->slots)
					->setLabel(__('ragnarok', 'slots'));
			}
			$frm->radio('sex')
			    ->value(array(
					'M' => __('ragnarok-gender', 1),
					'F' => __('ragnarok-gender', 2)
				))
			    ->checked($account->gender ? 'F' : 'M')
			    ->setLabel(__('ragnarok', 'sex'));
			if(!$account->owner) {
				$frm->input('email')
					->type('email')
					->required()
					->value(htmlspecialchars($account->email))
					->setLabel(__('ragnarok', 'email'));
				$frm->input('birthday')
					->type('date')
					->required()
					->value(date('Y-m-d', $account->birthday))
					->placeholder('YYYY-MM-DD')
					->setLabel(__('ragnarok', 'birthday'));
			}
			$frm->input('group-id')
			    ->type('number')
			    ->attr('min', 0)
			    ->attr('max', 99)
			    ->required()
				->value($account->groupId)
			    ->setLabel(__('ragnarok', 'group'));
			$frm->submit();
			$self = $this;
			$frm->validate(function(Form $frm) use($self) {
				$error = false;
				if($self->server->login->checkValidUsername($frm->request->getString('username'), $message) !== Server\Login::FIELD_OK) {
					$frm->field('username')->setWarning($message);
					$error = true;
				}
				if(($password = trim($frm->request->getString('password'))) &&
				   $self->server->login->checkValidPassword($password, $message) !== Server\Login::FIELD_OK) {
					$frm->field('password')->setWarning($message);
					$error = true;
				}
				if($self->server->login->getOption('use-pincode') &&
				   ($pincode = trim($frm->request->getString('pincode'))) &&
				   $self->server->login->checkValidPincode($pincode, $message) !== Server\Login::FIELD_OK) {
					$frm->field('pincode')->setWarning($message);
					$error = true;
				}
				if(($user = trim($frm->request->getString('user'))) &&
				   !($account = \Aqua\User\Account::get($user, 'display_name'))) {
					$frm->field('user')->setWarning(__('ragnarok', 'user-not-found'));
					$error = true;
				}

				return !$error;
			}, !$this->request->ajax);
			if($frm->status !== Form::VALIDATION_SUCCESS && !$this->request->ajax) {
				$this->title = $this->theme->head->section = __('ragnarok', 'edit-account', htmlspecialchars($account->username));
				$tpl = new Template;
				$tpl->set('form', $frm)
					->set('page', $this);
				echo $tpl->render('admin/ragnarok/account/edit');

				return;
			}
			$error = false;
			$message = null;
			try {
				$updated = 0;
				$update = array();
				if(!$frm->field('username')->getWarning()) {
					$update['username'] = $this->request->getString('username');
				}
				if($this->server->login->getOption('use-pincode') &&
				   !$frm->field('pincode')->getWarning() &&
				   $account->pinCode !== $this->request->getString('pincode')) {
					$update['pincode'] = $this->request->getString('pincode');
					$update['pincode_change'] = time();
				}
				if((int)$this->server->login->getOption('default-slots') && !$frm->field('slots')->getWarning()) {
					$update['slots'] = $this->request->getInt('slots');
				}
				if(!$frm->field('password')->getWarning() && $this->request->getString('password')) {
					$update['password'] = $this->request->getString('password');
				}
				if(!$frm->field('sex')->getWarning()) {
					$update['sex'] = $this->request->getString('sex');
				}
				if(!$frm->field('group-id')->getWarning()) {
					$update['group_id'] = $this->request->getInt('group-id');
				}
				if(!$account->owner) {
					if(!$frm->field('email')->getWarning()) {
						$update['email'] = $this->request->getString('email');
					}
					if(!$frm->field('birthday')->getWarning()) {
						$update['birthday'] = \DateTime::createFromFormat('Y-m-d', $this->request->getString('birthday'))->getTimestamp();
					}
				}
				if($account->update($update)) {
					++$updated;
				}
				if(!$frm->field('user')->getWarning()) {
					if(!$this->request->getString('user')) {
						if($account->owner) {
							$account->unlink();
							++$updated;
						}
					} else {
						if(($user = \Aqua\User\Account::get($this->request->getString('user'), 'display_name')) &&
						   $account->owner !== $user->id) {
							$account->link($user);
							++$updated;
						}
					}
				}
				if($updated) {
					$message = __('ragnarok', 'admin-account-updated');
				}
			} catch(\Exception $exception) {
				ErrorLog::logSql($exception);
				$error = true;
				$message = __('application', 'unexpected-error');
			}
			if($this->request->ajax) {
				$this->theme = new Theme;
				$this->response->setHeader('Content-Type', 'application/json');
				$response = array( 'message' => $message, 'error' => $error, 'data' => array(), 'warning' => array() );
				foreach($frm->content as $key => $field) {
					if($field instanceof Form\FieldInterface && ($warning = $field->getWarning())) {
						$response['warning'][$key] = $warning;
					}
				}
				$response['data'] = array(
					'username'       => $account->username,
					'user'           => $account->owner ? $account->user()->displayName : null,
				    'birthday'       => date('Y-m-d', $account->birthday),
				    'email'          => $account->email,
				    'slots'          => $account->slots,
				    'pincode'        => $account->pinCode,
				    'group-id'       => $account->groupId,
				    'sex'            => $account->gender ? 'F' : 'M',
				    'password'       => $account->password
				);
				echo json_encode($response);
			} else {
				$this->response->status(302)->redirect(App::request()->uri->url());
				if($message) {
					App::user()->addFlash($error ? 'error' : 'success', null, $message);
				}
				foreach($frm->content as $field) {
					if($field instanceof Form\FieldInterface && ($warning = $field->getWarning())) {
						App::user()->addFlash('warning', null, $warning);
					}
				}
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function banaccount_action($id = null)
	{
		try {
			if((int)$id < Server\Login::MIN_ACCOUNT_ID || !($account = $this->server->login->get($id))) {
				$this->error(404);

				return;
			}
			$frm = new Form($this->request);
			if($account->isBanned()) {
				$frm->textarea('reason');
				$frm->submit(__('ragnarok-account', 'unban'));
			} else {
				$frm->input('unban')
				    ->type('text')
				    ->setLabel(__('ragnarok-account', 'unban-date'));
				$frm->textarea('reason');
				$frm->submit(__('ragnarok-account', 'ban'));
			}
			$frm->validate(function (Form $frm) use ($account) {
				if($account->isBanned() &&
				   ($unban = $frm->request->getString('unban')) &&
				   (!($d = \DateTime::createFromFormat('Y-m-d H:i:s', $unban)) || $d->getTimestamp() < time())) {
					$frm->field('unban')->setWarning(__('form', 'invalid-date'));
				}

				return true;
			});
			if($frm->status !== Form::VALIDATION_SUCCESS) {
				$tpl = new Template;
				$tpl->set('account', $account)
				    ->set('page', $this);
				echo $tpl->render('admin/ragnarok/account/ban');

				return;
			}
			try {
				if($account->isBanned()) {
					if($account->unban(App::user()->account, $this->request->getString('reason'))) {
						App::user()->addFlash('success', null, __('ragnarok-account', 'ubanned'));
					}
				} else {
					if(($unban = $this->request->getString('unban')) &&
					   ($date = \DateTime::createFromFormat('Y-m-d H:i:s', $unban))) {
						$unban = $date->getTimestamp();
					} else {
						$unban = null;
					}
					if($account->ban(App::user()->account, $unban, $this->request->getString('reason'))) {
						if($unban) {
							App::user()
							   ->addFlash('success', null, __('ragnarok-account', 'banned-temporarily',
							              strftime(App::settings()->get('datetime_format', $unban))));
						}
						else App::user()->addFlash('success', null, __('ragnarok-account', 'banned-permanently'));
					}
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
}
