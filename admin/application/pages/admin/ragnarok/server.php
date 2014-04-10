<?php
namespace Page\Admin\ragnarok;

use Aqua\Core\App;
use Aqua\Core\Settings;
use Aqua\Log\ErrorLog;
use Aqua\Ragnarok\ItemData;
use Aqua\Site\Page;
use Aqua\SQL\Search;
use Aqua\UI\Form;
use Aqua\UI\Menu;
use Aqua\UI\Pagination;
use Aqua\UI\Template;
use Aqua\UI\Theme;

class Server
extends Page
{
	/**
	 * @var \Aqua\Ragnarok\Server
	 */
	public $server;
	/**
	 * @var \Aqua\Ragnarok\Server\CharMap
	 */
	public $charmap;

	public static $categoriesPerPage = 20;
	public static $itemsPerPage = 20;
	public static $charactersPerPage = 20;
	public static $guildsPerPage = 20;
	public static $logsPerPage = 20;

	const TIME_PATTERN = '/^(((2[0-4])|([01][0-9]))([\.:][0-5][0-9]){1,2}|((0[0-9])|(1[012]))([\.:][0-5][0-9]){1,2} ?(PM|AM))/i';

	public function run()
	{
		$this->server = App::$activeServer;
		$this->charmap = App::$activeCharMapServer;
		if(!$this->server) {
			$this->error(404);
		} else if($this->charmap) {
			$nav = new Menu;
			$baseUrl = ac_build_url(array(
					'path' => array( 'r', $this->server->key, $this->charmap->key ),
					'action' => ''
				));
			$nav->append('server', array(
				'title' => htmlspecialchars($this->charmap->name),
				'url' => "{$baseUrl}index"
			));
			if(App::user()->role()->hasPermission('edit-server-settings')) {
				$nav->append('settings', array(
					'title' => __('ragnarok-charmap', 'settings'),
					'url' => "{$baseUrl}settings"
				))->append('rates', array(
					'title' => __('ragnarok-charmap', 'rates'),
					'url' => "{$baseUrl}rates"
				))->append('shop', array(
					'title' => __('ragnarok-charmap', 'shop'),
					'url' => "{$baseUrl}shop"
				))->append('shop-categories', array(
					'title' => __('ragnarok-charmap', 'shop-categories'),
					'url' => "{$baseUrl}category"
				))->append('woe', array(
					'title' => __('ragnarok-charmap', 'woe'),
					'url' => "{$baseUrl}woe"
				));
			}
			if(App::user()->role()->hasPermission('view-server-logs')) {
				$nav->append('zenylog', array(
					'title' => __('ragnarok-charmap', 'zeny-log'),
				    'url' => "{$baseUrl}zenylog"
				))->append('shop-log', array(
					'title' => __('ragnarok-charmap', 'shop-log'),
					'url' => "{$baseUrl}shoplog"
				))->append('pick-log', array(
					'title' => __('ragnarok-charmap', 'pick-log'),
					'url' => "{$baseUrl}picklog"
				))->append('atcmd-log', array(
					'title' => __('ragnarok-charmap', 'atcommand-log'),
					'url' => "{$baseUrl}atcmdlog"
				))->append('chat-log', array(
					'title' => __('ragnarok-charmap', 'chat-log'),
					'url' => "{$baseUrl}chatlog"
				));
			}
			$nav->append('characters', array(
				'title' => __('ragnarok-charmap', 'characters'),
			    'url' => "{$baseUrl}char"
			))->append('guilds', array(
				'title' => __('ragnarok-charmap', 'guilds'),
			    'url' => "{$baseUrl}guild"
			));
			$this->theme->set('nav', $nav);
		} else if($this->request->uri->action !== 'index') {
			$this->error(404);
		}
	}

	public function index_action()
	{
		if($this->charmap) {
			$this->server_index();
			return;
		} else if(!App::user()->role()->hasPermission('edit-server-settings')) {
			$this->error(403);
			return;
		}
		try {
			$frm = new Form($this->request);
			$frm->input('name', true)
		        ->type('text')
		        ->attr('maxlength', 255)
		        ->required()
		        ->setLabel(__('ragnarok-charmap', 'name-label'));
			$frm->input('key', true)
		        ->type('text')
		        ->attr('maxlength', 255)
		        ->required()
		        ->setLabel(__('ragnarok-charmap', 'key-label'))
				->setDescription(__('ragnarok-charmap', 'key-desc'));
			$frm->input('timezone', true)
		        ->type('text')
		        ->setLabel(__('ragnarok-charmap', 'timezone-label'))
				->setDescription(__('ragnarok-charmap', 'timezone-desc'));
			if($this->server->charmapCount) {
				$frm->checkbox('default')
					->value(array( '1' => '' ))
					->setLabel(__('ragnarok-charmap', 'default-label'));
			}
			$frm->input('char-host', true)
		        ->type('text')
		        ->required()
		        ->setLabel(__('ragnarok-charmap', 'char-host-label'));
			$frm->input('char-port', true)
		        ->type('number')
				->attr('min', 0)
		        ->required()
				->value(6121, false)
		        ->setLabel(__('ragnarok-charmap', 'char-port-label'));
			$frm->input('map-host', true)
		        ->type('text')
		        ->required()
		        ->setLabel(__('ragnarok-charmap', 'map-host-label'));
			$frm->input('map-port', true)
		        ->type('number')
				->attr('min', 0)
		        ->required()
				->value(5121, false)
		        ->setLabel(__('ragnarok-charmap', 'map-port-label'));
			$frm->input('sql-host', true)
				->type('text')
			    ->required()
			    ->setLabel(__('ragnarok-charmap', 'db-host-label'));
			$frm->input('sql-port', true)
				->type('number')
				->attr('min', 0)
			    ->required()
				->value(3306, false)
			    ->setLabel(__('ragnarok-charmap', 'db-port-label'));
			$frm->input('sql-database', true)
				->type('text')
			    ->required()
			    ->setLabel(__('ragnarok-charmap', 'db-database-label'));
			$frm->input('sql-username', true)
				->type('text')
				->required()
			    ->setLabel(__('ragnarok-charmap', 'db-username-label'));
			$frm->input('sql-password', true)
				->type('password')
			    ->setLabel(__('ragnarok-charmap', 'db-password-label'));
			$frm->input('sql-charset', true)
				->type('text')
				->value('UTF8', false)
			    ->setLabel(__('ragnarok-charmap', 'db-charset-label'));
			$frm->input('sql-timezone', true)
				->type('text')
			    ->setLabel(__('ragnarok-charmap', 'db-timezone-label'))
			    ->setDescription(__('ragnarok-charmap', 'db-timezone-desc'));
			$frm->input('log-host', true)
				->type('text')
			    ->required()
			    ->setLabel(__('ragnarok-charmap', 'db-host-label'));
			$frm->input('log-port', true)
				->type('number')
				->attr('min', 0)
			    ->required()
				->value(3306, false)
			    ->setLabel(__('ragnarok-charmap', 'db-port-label'));
			$frm->input('log-database', true)
				->type('text')
			    ->required()
			    ->setLabel(__('ragnarok-charmap', 'db-database-label'));
			$frm->input('log-username', true)
				->type('text')
				->required()
			    ->setLabel(__('ragnarok-charmap', 'db-username-label'));
			$frm->input('log-password', true)
				->type('password')
			    ->setLabel(__('ragnarok-charmap', 'db-password-label'));
			$frm->input('log-charset', true)
				->type('text')
				->value('UTF8', false)
			    ->setLabel(__('ragnarok-charmap', 'db-charset-label'));
			$frm->input('log-timezone', true)
				->type('text')
			    ->setLabel(__('ragnarok-charmap', 'db-timezone-label'))
			    ->setDescription(__('ragnarok-charmap', 'db-timezone-desc'));
			$frm->checkbox('renewal', true)
		        ->value(array( '1' => '' ))
				->checked(1, false)
		        ->setLabel(__('ragnarok-charmap', 'renewal-label'));
			$frm->input('online-stats', true)
				->type('number')
				->attr('min', 0)
				->value(0, false)
		        ->setLabel(__('ragnarok-charmap', 'online-stats-label'))
		        ->setDescription(__('ragnarok-charmap', 'online-stats-desc'));
			$frm->input('fame', true)
				->type('number')
				->attr('min', 0)
				->required()
				->value(10, false)
				->setLabel(__('ragnarok-charmap', 'fame-label'));
			$frm->input('default-map', true)
		        ->type('text')
		        ->value('prontera', false)
				->required()
		        ->setLabel(__('ragnarok-charmap', 'default-map-label'));
			$frm->input('default-x', true)
		        ->type('number')
				->attr('min', 0)
				->required()
		        ->value(156, false)
		        ->setLabel(__('ragnarok-charmap', 'default-x-label'));
			$frm->input('default-y', true)
		        ->type('number')
				->attr('min', 0)
				->required()
		        ->value(191, false)
		        ->setLabel(__('ragnarok-charmap', 'default-y-label'));
			$frm->input('map-restrictions', true)
				->type('text')
				->value('/^(sec_pri|[^@]+@[0-9]+)(\.gat)?$/i', false)
				->setLabel(__('ragnarok-charmap', 'map-restriction-label'))
				->setDescription(__('ragnarok-charmap', 'map-restriction-desc'));
			$frm->submit();
			$self = $this;
			$dbh = $ldbh = null;
			$frm->validate(function(Form $frm, &$message) use(&$self, &$dbh, &$ldbh) {
					$error = false;
					if($regex = $frm->request->getString('map-restrictions')) {
						@preg_match($regex, '');
						if($mes = ac_pcre_error_str()) {
							$frm->field('map-restrictions')->setWarning($mes);
							$error = true;
						}
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
						$error = true;
					}
					try {
						$dbh = ac_mysql_connection(array(
								'host'     => $frm->request->getString('sql-host'),
								'port'     => $frm->request->getInt('sql-port'),
								'username' => $frm->request->getString('sql-username'),
								'password' => $frm->request->getString('sql-password'),
								'database' => $frm->request->getString('sql-database'),
								'timezone' => $frm->request->getString('sql-timezone'),
								'options'  => array( \PDO::ATTR_TIMEOUT => 5 )
							));
					} catch(\PDOException $exception) {
						if(($info = $exception->errorInfo) && count($info) === 3) {
							$code = $info[1];
						} else {
							$code = $exception->getCode();
						}
						switch($code) {
							case 2002:
								$frm->field('sql-host')->setWarning(__('exception', 'pdo-connection-failed', $exception->getCode(), $exception->getMessage()));
								break;
							case 1298:
								$frm->field('sql-timezone')->setWarning(__('exception', 'pdo-invalid-timezone', $exception->getCode(), $exception->getMessage()));
								break;
							case 1045:
								$frm->field('sql-password')->setWarning(__('exception', 'pdo-access-denied', $exception->getCode(), $exception->getMessage()));
								break;
							case 1049:
								$frm->field('sql-database')->setWarning(__('exception', 'pdo-unknown-db', $exception->getCode(), $exception->getMessage()));
								break;
							default:
								$message = __('exception', 'pdo-exception', $exception->getCode(), $exception->getMessage());
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
								'options'  => array( \PDO::ATTR_TIMEOUT => 5 )
							));
					} catch(\PDOException $exception) {
						if(($info = $exception->errorInfo) && count($info) === 3) {
							$code = $info[1];
						} else {
							$code = $exception->getCode();
						}
						switch($code) {
							case 2002:
								$frm->field('log-host')->setWarning(__('exception', 'pdo-connection-failed', $exception->getCode(), $exception->getMessage()));
								break;
							case 1298:
								$frm->field('log-timezone')->setWarning(__('exception', 'pdo-invalid-timezone', $exception->getCode(), $exception->getMessage()));
								break;
							case 1045:
								$frm->field('log-password')->setWarning(__('exception', 'pdo-access-denied', $exception->getCode(), $exception->getMessage()));
								break;
							case 1049:
								$frm->field('log-database')->setWarning(__('exception', 'pdo-unknown-db', $exception->getCode(), $exception->getMessage()));
								break;
							default:
								$message = __('exception', 'pdo-exception', $exception->getCode(), $exception->getMessage());
								break;
						}
						return false;
					}
					return !$error;
				});
			if($frm->status !== Form::VALIDATION_SUCCESS) {
				$dbh = null;
				$ldbh = null;
				$this->title = $this->theme->head->section = __('ragnarok-charmap', 'new-server', htmlspecialchars($this->server->name));
				$tpl = new Template;
				$tpl->set('form', $frm)
			        ->set('page', $this);
				echo $tpl->render('admin/ragnarok/new-charmap');
				return;
			}
			try {
				/**
				 * @var $dbh  \PDO
				 * @var $ldbh \PDO
				 */
				$dbh->exec(file_get_contents(\Aqua\ROOT . '/schema/charmap/ac_cash_shop.sql'));
				$dbh->exec(file_get_contents(\Aqua\ROOT . '/schema/charmap/ac_charmap_settings.sql'));
				$dbh->exec(file_get_contents(\Aqua\ROOT . '/schema/charmap/ac_woe_schedule.sql'));
				$dbh->exec(file_get_contents(\Aqua\ROOT . '/schema/charmap/ac_online_stats.sql'));
				try { $dbh->exec(file_get_contents(\Aqua\ROOT . '/schema/charmap/item_db.description.sql')); } catch(\PDOException $e) { }
				try { $dbh->exec(file_get_contents(\Aqua\ROOT . '/schema/charmap/item_db_re.description.sql')); } catch(\PDOException $e) { }
				try { $dbh->exec(file_get_contents(\Aqua\ROOT . '/schema/charmap/item_db2.description.sql')); } catch(\PDOException $e) { }
				try { $dbh->exec(file_get_contents(\Aqua\ROOT . '/schema/charmap/item_db2_re.description.sql')); } catch(\PDOException $e) { }
				try { $dbh->exec(file_get_contents(\Aqua\ROOT . '/schema/charmap/char.ac_options.sql')); } catch(\PDOException $e) { }
				$ldbh->exec(file_get_contents(\Aqua\ROOT . '/schema/charmap-log/ac_cash_shop_log.sql'));
				$ldbh->exec(file_get_contents(\Aqua\ROOT . '/schema/charmap-log/ac_cash_shop_items.sql'));
				$ldbh->exec(file_get_contents(\Aqua\ROOT . '/schema/charmap-log/ac_divorce_log.sql'));
				$sth = $dbh->prepare("
				REPLACE INTO `ac_char_map_settings` VALUES
				 ('char-ip', :chost)
				,('char-port', :cport)
				,('map-ip', :mhost)
				,('map-port', :mport)
				,('timezone', :timezone)
				,('status-timeout', :timeout)
				,('status-ttl', :ttl)
				,('online-stats', :online)
				,('renewal', :renewal)
				,('default-map', :map)
				,('default-map-x', :x)
				,('default-map-y', :y)
				,('map-restriction', :restriction)
				,('fame-ladder', :fame)
				;
				");
				$sth->bindValue(':chost', $this->request->getString('char-host'));
				$sth->bindValue(':cport', $this->request->getInt('char-port'));
				$sth->bindValue(':mhost', $this->request->getString('map-host'));
				$sth->bindValue(':mport', $this->request->getInt('map-port'));
				$sth->bindValue(':timezone', $this->request->getString('timezone'));
				$sth->bindValue(':timeout', $this->request->getInt('status-timeout', 3));
				$sth->bindValue(':ttl', $this->request->getInt('status-cache', 300));
				$sth->bindValue(':online', $this->request->getInt('online-stats'));
				$sth->bindValue(':renewal', $this->request->getInt('renewal') ? '1' : '');
				$sth->bindValue(':fame', $this->request->getInt('fame'));
				$sth->bindValue(':map', $this->request->getString('default-map'));
				$sth->bindValue(':x', $this->request->getInt('default-x'));
				$sth->bindValue(':y', $this->request->getInt('default-y'));
				$sth->bindValue(':restriction', $this->request->getString('map-restrictions'));
				$sth->execute();
				$sth->closeCursor();
				try {
					if($interval = $this->request->getInt('online-stats')) {
						$sth = $dbh->prepare('
						ALTER EVENT ac_online_stats_event
						ON SCHEDULE
						EVERY :interval MINUTE
						ENABLE
						');
						$sth->bindValue(':interval', $interval, \PDO::PARAM_INT);
						$sth->execute();
					} else {
						$dbh->exec('ALTER EVENT ac_online_stats_event DISABLE');
					}
				} catch(\PDOException $e) { }
				$key = strtolower($this->request->getString('key'));
				$file = \Aqua\ROOT . '/settings/ragnarok.php';
				$settings = new Settings();
				$settings->import($file);
				$settings->get($this->server->key)->get('charmap')->set($key, array(
						'name' => $this->request->getString('name'),
						'database_name' => $this->request->getString('sql-database'),
						'log_database_name' => $this->request->getString('log-database'),
						'db' => array(
							'host' => $this->request->getString('sql-host'),
							'port' => $this->request->getString('sql-port'),
							'username' => $this->request->getString('sql-username'),
							'password' => $this->request->getString('sql-password'),
							'timezone' => $this->request->getString('sql-timezone'),
							'charset' => $this->request->getString('sql-charset'),
							'persistent' => false
						),
						'log_db' => array(
							'host' => $this->request->getString('log-host'),
							'port' => $this->request->getString('log-port'),
							'username' => $this->request->getString('log-username'),
							'password' => $this->request->getString('log-password'),
							'timezone' => $this->request->getString('log-timezone'),
							'charset' => $this->request->getString('log-charset'),
							'persistent' => false
						),
						'tables' => array(),
						'log_tables' => array()
					));
				if(!$this->server->charmapCount || $this->request->getInt('default')) {
					$settings->get($this->server->key)->set('default_server', $key);
				}
				$settings->export($file);
				$this->response->status(302)->redirect(ac_build_url(array( 'path' => array( 'r', $this->server->key, $key ) )));
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
			$this->theme->set('return', ac_build_url(array( 'path' => array( 'r', $this->server->key ) )));
			$this->title = htmlspecialchars($this->charmap->name);
			$this->theme->head->section = htmlspecialchars(sprintf('%s / %s',
			                                                       $this->server->name,
			                                                       $this->charmap->name));
			$tpl = new Template;
			$tpl->set('charmap', $this->charmap)
				->set('page', $this);
			echo $tpl->render('admin/ragnarok/charmap');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function settings_action() {
		try {
			$frm = new Form($this->request);
			$frm->input('name', true)
			    ->type('text')
			    ->attr('maxlength', 255)
			    ->required()
				->value(htmlspecialchars($this->charmap->name), false)
			    ->setLabel(__('ragnarok-charmap', 'name-label'));
			$frm->input('key', true)
			    ->type('text')
			    ->attr('maxlength', 255)
			    ->required()
				->value(htmlspecialchars($this->charmap->key), false)
			    ->setLabel(__('ragnarok-charmap', 'key-label'))
			    ->setDescription(__('ragnarok-charmap', 'key-desc'));
			$frm->input('timezone', true)
			    ->type('text')
				->value(htmlspecialchars($this->charmap->getOption('timezone')), false)
			    ->setLabel(__('ragnarok-charmap', 'timezone-label'))
			    ->setDescription(__('ragnarok-charmap', 'timezone-desc'));
			if($this->server->charmapCount > 1) {
				$frm->checkbox('default')
				    ->value(array( '1' => '' ))
					->checked($this->server->defaultServer === $this->charmap->key ? '1' : null)
				    ->setLabel(__('ragnarok-charmap', 'default-label'));
			}
			$frm->input('char-host', true)
			    ->type('text')
			    ->required()
				->value(htmlspecialchars($this->charmap->getOption('char-ip')), false)
			    ->setLabel(__('ragnarok-charmap', 'char-host-label'));
			$frm->input('char-port', true)
			    ->type('number')
			    ->attr('min', 0)
			    ->required()
			    ->value($this->charmap->getOption('char-port'), false)
			    ->setLabel(__('ragnarok-charmap', 'char-port-label'));
			$frm->input('map-host', true)
			    ->type('text')
			    ->required()
				->value(htmlspecialchars($this->charmap->getOption('map-ip')), false)
			    ->setLabel(__('ragnarok-charmap', 'map-host-label'));
			$frm->input('map-port', true)
			    ->type('number')
			    ->attr('min', 0)
			    ->required()
				->value($this->charmap->getOption('map-port'), false)
			    ->setLabel(__('ragnarok-charmap', 'map-port-label'));
			$frm->checkbox('renewal', true)
			    ->value(array( '1' => '' ))
			    ->checked($this->charmap->getOption('renewal', true) ? '1' : null, false)
			    ->setLabel(__('ragnarok-charmap', 'renewal-label'));
			$frm->input('online-stats', true)
			    ->type('number')
			    ->attr('min', 0)
			    ->value(30, false)
			    ->setLabel(__('ragnarok-charmap', 'online-stats-label'))
			    ->setDescription(__('ragnarok-charmap', 'online-stats-desc'));
			$frm->input('fame', true)
			    ->type('number')
			    ->attr('min', 0)
			    ->required()
			    ->value($this->charmap->getOption('fame-ladder', 10), false)
			    ->setLabel(__('ragnarok-charmap', 'fame-label'));
			$frm->input('default-map', true)
			    ->type('text')
			    ->value($this->charmap->getOption('default-map'), false)
			    ->required()
			    ->setLabel(__('ragnarok-charmap', 'default-map-label'));
			$frm->input('default-x', true)
			    ->type('number')
			    ->attr('min', 0)
			    ->required()
			    ->value($this->charmap->getOption('default-map-x'), false)
			    ->setLabel(__('ragnarok-charmap', 'default-x-label'));
			$frm->input('default-y', true)
			    ->type('number')
			    ->attr('min', 0)
			    ->required()
			    ->value($this->charmap->getOption('default-map-y'), false)
			    ->setLabel(__('ragnarok-charmap', 'default-y-label'));
			$frm->input('map-restrictions', true)
			    ->type('text')
			    ->value($this->charmap->getOption('map-restriction'), false)
			    ->setLabel(__('ragnarok-charmap', 'map-restriction-label'))
			    ->setDescription(__('ragnarok-charmap', 'map-restriction-desc'));
			$frm->submit();
			$frm->input('delete')
				->type('submit')
				->value(__('ragnarok-charmap', 'delete-server'));
			$frm->validate(function(Form $frm) {
				if($regex = $frm->request->getString('map-restrictions')) {
					@preg_match($regex, '');
					if($mes = ac_pcre_error_str()) {
						$frm->field('map-restrictions')->setWarning($mes);
						return false;
					}
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
				return true;
			});
			if($frm->status !== Form::VALIDATION_SUCCESS) {
				$this->title = $this->theme->head->section = __('ragnarok-charmap', 'settings');
				$tpl = new Template;
				$tpl->set('form', $frm)
				    ->set('page', $this);
				echo $tpl->render('admin/ragnarok/charmap-settings');
				return;
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
			return;
		}
		$this->response->status(302);
		try {
			$file     = \Aqua\ROOT . '/settings/ragnarok.php';
			$settings = new Settings;
			$settings->import($file);
			$charmapSettings = $settings->get($this->server->key)->get('charmap');
			if(!empty($this->request->data['delete'])) {
				$this->charmap->flushCache();
				$charmapSettings->delete($this->charmap->key);
				$settings->export($file);
				App::user()->addFlash('success', null, __('ragnarok-charmap', 'server-deleted', htmlspecialchars($this->charmap->name)));
				$this->response->redirect(ac_build_url(array( 'path' => array( 'r', $this->server->key ) )));
			} else {
				$charmapSettings->get($this->charmap->key)->set('name', $this->request->getString('name'));
				if($this->request->getString('key') !== $this->charmap->key) {
					$copy = clone $charmapSettings->get($this->charmap->key);
					$charmapSettings->delete($this->charmap->key);
					$charmapSettings->set($this->request->getString('key'), $copy);
				}
				$this->charmap->key = $this->request->getString('key');
				$this->charmap->name = $this->request->getString('name');
				if($this->server->charmapCount > 1 && $this->request->getInt('default')) {
					$settings->get($this->server->key)->set('default_server', $this->charmap->key);
				}
				$settings->export($file);
				$this->charmap->setOption(array(
					'char-ip' => $this->request->getString('char-host'),
					'char-port' => $this->request->getInt('char-port'),
					'map-ip' => $this->request->getString('map-host'),
					'map-port' => $this->request->getInt('map-port'),
					'timezone' => $this->request->getString('timezone'),
					'status-timeout' => $this->request->getInt('status-timeout', 3),
					'status-ttl' => $this->request->getInt('status-cache', 300),
					'online-stats' => $this->request->getInt('online-stats'),
					'renewal' => $this->request->getInt('renewal') ? '1' : '',
					'fame-ladder' => $this->request->getInt('fame'),
					'default-map' => $this->request->getInt('default-map'),
					'default-map-y' => $this->request->getInt('default-y'),
					'default-map-x' => $this->request->getInt('default-x'),
					'map-restriction' => $this->request->getInt('map-restrictions'),
				));
				$evt = $this->charmap->table('ac_online_stats_event');
				if(!$this->request->getInt('online-stats')) {
					$this->charmap->connection()->exec("ALTER EVENT $evt DISABLE");
				} else {
					$sth = $this->charmap->connection()->prepare("
					ALTER EVENT $evt
					ON SCHEDULE
					EVERY :interval MINUTE
					ENABLE
					");
					$sth->bindValue(':interval', $this->request->getInt('online-stats'), \PDO::PARAM_INT);
					$sth->execute();
				}
				App::user()->addFlash('success', null, __('ragnarok-charmap', 'settings-saved'));
				$this->response->redirect(ac_build_url(array(
						'path' => array( 'r', $this->server->key, $this->charmap->key ),
						'action' => 'settings'
					)));
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			App::user()->addFlash('error', null, __('application', 'unexpected-error'));
			$this->response->redirect(ac_build_url(array(
					'path' => array( 'r', $this->server->key, $this->charmap->key ),
					'action' => 'settings'
				)));
		}
	}

	public function rates_action()
	{
		try {
			$frm = new Form($this->request);
			$frm->checkbox('logarithmic_drops', true)
				->value(array( '1' => '' ))
				->checked($this->charmap->getOption('logarithmic-drops') ? '1' : null, false)
				->setLabel(__('ragnarok-charmap', 'logarithmic-drops-label'));
			$frm->input('base_exp', true)
				->type('number')
				->attr('min', 1)
				->required()
				->value($this->charmap->getOption('rate.base-exp', 100), false)
				->setLabel(__('ragnarok-charmap', 'base-exp-label'));
			$frm->input('job_exp', true)
				->type('number')
				->attr('min', 1)
				->required()
				->value($this->charmap->getOption('rate.job-exp', 100), false)
				->setLabel(__('ragnarok-charmap', 'job-exp-label'));
			$frm->input('quest_exp', true)
				->type('number')
				->attr('min', 1)
				->required()
				->value($this->charmap->getOption('rate.quest-exp', 100), false)
				->setLabel(__('ragnarok-charmap', 'quest-exp-label'));
			$frm->input('mvp_exp', true)
				->type('number')
				->attr('min', 1)
				->required()
				->value($this->charmap->getOption('rate.mvp-exp', 100), false)
				->setLabel(__('ragnarok-charmap', 'mvp-exp-label'));
			$frm->input('common_rate', true)
				->type('number')
				->attr('min', 1)
				->required()
				->value($this->charmap->getOption('rate.item-common', 100), false)
				->setLabel(__('ragnarok-charmap', 'rate-common-label'));
			$frm->input('common_boss', true)
				->type('number')
				->attr('min', 1)
				->value($this->charmap->getOption('rate.item-common-boss', 100), false)
				->required()
				->value(100, false)
				->setLabel(__('ragnarok-charmap', 'boss-common-label'));
			$frm->input('common_min', true)
				->type('number')
				->attr('min', 1)
				->required()
				->value($this->charmap->getOption('rate.item-common-min', 1), false)
				->setLabel(__('ragnarok-charmap', 'min-common-label'));
			$frm->input('common_max', true)
				->type('number')
				->attr('min', 1)
				->required()
				->value($this->charmap->getOption('rate.item-common-max', 1000), false)
				->setLabel(__('ragnarok-charmap', 'max-common-label'));
			$frm->input('heal_rate', true)
				->type('number')
				->attr('min', 1)
				->required()
				->value($this->charmap->getOption('rate.item-heal', 100), false)
				->setLabel(__('ragnarok-charmap', 'rate-heal-label'));
			$frm->input('heal_boss', true)
				->type('number')
				->attr('min', 1)
				->required()
				->value($this->charmap->getOption('rate.item-heal-boss', 100), false)
				->setLabel(__('ragnarok-charmap', 'boss-heal-label'));
			$frm->input('heal_min', true)
				->type('number')
				->attr('min', 1)
				->required()
				->value($this->charmap->getOption('rate.item-heal-min', 1), false)
				->setLabel(__('ragnarok-charmap', 'min-heal-label'));
			$frm->input('heal_max', true)
				->type('number')
				->attr('min', 1)
				->required()
				->value($this->charmap->getOption('rate.item-common-max', 1000), false)
				->setLabel(__('ragnarok-charmap', 'max-heal-label'));
			$frm->input('equip_rate', true)
				->type('number')
				->attr('min', 1)
				->required()
				->value($this->charmap->getOption('rate.item-equip', 100), false)
				->setLabel(__('ragnarok-charmap', 'rate-equip-label'));
			$frm->input('equip_boss', true)
				->type('number')
				->required()
				->attr('min', 1)
				->value($this->charmap->getOption('rate.item-equip-boss', 100), false)
				->setLabel(__('ragnarok-charmap', 'boss-equip-label'));
			$frm->input('equip_min', true)
				->type('number')
				->attr('min', 1)
				->required()
				->value($this->charmap->getOption('rate.item-equip-min', 1), false)
				->setLabel(__('ragnarok-charmap', 'min-equip-label'));
			$frm->input('equip_max', true)
				->type('number')
				->attr('min', 1)
				->required()
				->value($this->charmap->getOption('rate.item-equip-max', 1000), false)
				->setLabel(__('ragnarok-charmap', 'max-equip-label'));
			$frm->input('card_rate', true)
				->type('number')
				->attr('min', 1)
				->required()
				->value($this->charmap->getOption('rate.item-card', 100), false)
				->setLabel(__('ragnarok-charmap', 'rate-card-label'));
			$frm->input('card_boss', true)
				->type('number')
				->attr('min', 1)
				->required()
				->value($this->charmap->getOption('rate.item-card-boss', 100), false)
				->setLabel(__('ragnarok-charmap', 'boss-card-label'));
			$frm->input('card_min', true)
				->type('number')
				->attr('min', 1)
				->required()
				->value($this->charmap->getOption('rate.item-card-min', 1), false)
				->setLabel(__('ragnarok-charmap', 'min-card-label'));
			$frm->input('card_max', true)
				->type('number')
				->attr('min', 1)
				->required()
				->value($this->charmap->getOption('rate.item-card-max', 1000), false)
				->setLabel(__('ragnarok-charmap', 'max-card-label'));
			$frm->input('mvp_rate', true)
				->type('number')
				->attr('min', 1)
				->required()
				->value($this->charmap->getOption('rate.item-mvp', 100), false)
				->setLabel(__('ragnarok-charmap', 'rate-mvp-label'));
			$frm->input('mvp_min', true)
				->type('number')
				->attr('min', 1)
				->required()
				->value($this->charmap->getOption('rate.item-mvp-min', 1), false)
				->setLabel(__('ragnarok-charmap', 'min-mvp-label'));
			$frm->input('mvp_max', true)
				->type('number')
				->attr('min', 1)
				->required()
				->value($this->charmap->getOption('rate.item-mvp-max', 1000), false)
				->setLabel(__('ragnarok-charmap', 'max-mvp-label'));
			$frm->submit();
			$frm->validate();
			if($frm->status !== Form::VALIDATION_SUCCESS) {
				$this->title = $this->theme->head->section = __('ragnarok-charmap', 'rates');
				$tpl = new Template;
				$tpl->set('form', $frm)
					->set('page', $this);
				echo $tpl->render('admin/ragnarok/rates');
				return;
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));

			return;
		}
		$this->response->status(302)->redirect(App::request()->uri->url());
		try {
			if($this->charmap->setOption(array(
					'logarithmic-drops' => $this->request->getInt('logarithmic_drops') ? '1' : '',
					'rate.base-exp' => $this->request->getInt('base_exp'),
					'rate.job-exp' => $this->request->getInt('job_exp'),
					'rate.quest-exp' => $this->request->getInt('quest_exp'),
					'rate.mvp-exp' => $this->request->getInt('mvp_exp'),
					'rate.item-common' => $this->request->getInt('common_rate'),
					'rate.item-common-boss' => $this->request->getInt('common_boss'),
					'rate.item-common-min' => $this->request->getInt('common_min'),
					'rate.item-common-max' => $this->request->getInt('common_max'),
					'rate.item-heal' => $this->request->getInt('heal_rate'),
					'rate.item-heal-boss' => $this->request->getInt('heal_boss'),
					'rate.item-heal-min' => $this->request->getInt('heal_min'),
					'rate.item-heal-max' => $this->request->getInt('heal_max'),
					'rate.item-equip' => $this->request->getInt('equip_rate'),
					'rate.item-equip-boss' => $this->request->getInt('equip_boss'),
					'rate.item-equip-min' => $this->request->getInt('equip_min'),
					'rate.item-card' => $this->request->getInt('card_rate'),
					'rate.item-card-boss' => $this->request->getInt('card_boss'),
					'rate.item-card-min' => $this->request->getInt('card_min'),
					'rate.item-card-max' => $this->request->getInt('card_max'),
					'rate.item-mvp' => $this->request->getInt('mvp_rate'),
					'rate.item-mvp-min' => $this->request->getInt('mvp_min'),
					'rate.item-mvp-max' => $this->request->getInt('mvp_max'),
				))) {
				App::user()->addFlash('success', null, '');
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			App::user()->addFlash('error', null, __('application', 'unexpected-error'));
		}
	}

	public function woe_action()
	{
		try {
			$castlesForm = new Form($this->request);
			$castlesForm->input('setcastles')
				->type('submit')
				->required()
				->value(__('application', 'submit'));
			$castlesForm->validate(function($frm, &$message) {
				$ids = $frm->request->getArray('casid', null);
				$names = $frm->request->getArray('casname', null);
				if(!$ids || !$names || count($ids) !== count($names)) {
					return Form::VALIDATION_INCOMPLETE;
				}
				$count = count($ids);
				for($i = 0; $i < $count; ++$i) {
					if(!isset($ids[$i]) || !isset($names[$i])) {
						return Form::VALIDATION_INCOMPLETE;
					}
					if($ids[$i] === '' && $names[$i] === '') {
						continue;
					}
					if(!ctype_digit($ids[$i]) || (int)$ids[$i] < 0) {
						$message = __('ragnarok-charmap', 'castle-id-number');
						return Form::VALIDATION_FAIL;
					} else if($names[$i] === '') {
						$message =  __('ragnarok-charmap', 'castle-name-empty');
						return Form::VALIDATION_FAIL;
					}
				}
				return true;
			});
			if($castlesForm->status === Form::VALIDATION_SUCCESS) {
				$this->response->status(302)->redirect(App::request()->uri->url());
				try {
					$castles = array();
					$ids = $this->request->getArray('casid', null);
					$names = $this->request->getArray('casname', null);
					$count = count($ids);
					for($i = 0; $i < $count; ++$i) {
						if($ids[$i] === '' && $names[$i] === '') {
							continue;
						}
						$castles[$ids[$i]] = $names[$i];
					}
					if($this->charmap->setCastles($castles)) {
						App::user()->addFlash('success', null, __('ragnarok-charmap', 'castles-updated'));
					}
				} catch(\Exception $exception) {
					ErrorLog::logSql($exception);
					App::user()->addFlash('error', null, __('application', 'unexpected-error'));
				}
				return;
			}
			$scheduleForm = new Form($this->request);
			$scheduleForm->input('name')
				->type('text')
				->required()
				->setLabel(__('ragnarok-charmap', 'schedule-name'));
			$scheduleForm->input('starttime')
				->type('text')
				->required()
				->attr('pattern', self::TIME_PATTERN)
				->placeholder('HH:MM:SS')
				->setLabel(__('ragnarok-charmap', 'schedule-starttime'));
			$scheduleForm->input('endtime')
				->type('text')
				->required()
				->attr('pattern', self::TIME_PATTERN)
				->placeholder('HH:MM:SS')
				->setLabel(__('ragnarok-charmap', 'schedule-endtime'));
			$scheduleForm->select('startday')
				->value(array(
					0 => __('week', 0),
					1 => __('week', 1),
					2 => __('week', 2),
					3 => __('week', 3),
					4 => __('week', 4),
					5 => __('week', 5),
					6 => __('week', 6)
				))
				->required()
				->setLabel(__('ragnarok-charmap', 'schedule-startday'));
			$scheduleForm->select('endday')
				->value(array(
					0 => __('week', 0),
					1 => __('week', 1),
					2 => __('week', 2),
					3 => __('week', 3),
					4 => __('week', 4),
					5 => __('week', 5),
					6 => __('week', 6)
				))
				->required()
				->setLabel(__('ragnarok-charmap', 'schedule-endday'));
			$scheduleForm->input('castles')
				->type('text')
				->required()
				->setLabel(__('ragnarok-charmap', 'schedule-castles'))
				->setDescription(__('ragnarok-charmap', 'schedule-castles-desc'));
			$scheduleForm->validate(function(Form $frm) {
				$count   = 0;
				$castles = preg_split('/\s*,\s*/', $frm->request->getString('castles'));
				foreach($castles as $castle) {
					if(!ctype_digit(trim($castle))) {
						$frm->input('castles')->setWarning(__('form', 'invalid-number'));
						return false;
					}
					++$count;
				}
				if(!$count) {
					$frm->input('castles')->setWarning(__('form', 'field-required'));
					return false;
				}
				return true;
			});
			if($scheduleForm->status !== Form::VALIDATION_SUCCESS) {
				$this->title = $this->theme->head->section = __('ragnarok-charmap', 'woe');
				$tpl = new Template;
				$tpl->set('schedule', $this->charmap->woeSchedule())
					->set('castles', $this->charmap->castles())
					->set('schedule_form', $scheduleForm)
					->set('castles_form', $castlesForm)
					->set('page', $this);
				echo $tpl->render('admin/ragnarok/woe');
				return;
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
			return;
		}
		$this->response->status(302)->redirect(App::request()->uri->url());
		try {
			$startTime = date('H:i:s', strtotime($this->request->getString('starttime')));
			$endTime   = date('H:i:s', strtotime($this->request->getString('endtime')));
			$castles   = array_map(function($x) {
				return intval(trim($x));
			}, preg_split('/\s*,\s*/', $this->request->getString('castles')));
			if($this->charmap->addWoeTime($this->request->getString('name'),
			                              $castles,
			                              $this->request->getString('endday'),
			                              $endTime,
			                              $this->request->getString('startday'),
			                              $startTime)) {
				App::user()->addFlash('success', null, __('ragnarok-charmap', 'added-schedule'));
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			App::user()->addFlash('error', null, __('application', 'unexpected-error'));
		}
	}

	public function schedule_action($id)
	{
		$schedule = $this->charmap->woeSchedule();
		if(!isset($schedule[$id])) {
			$this->error(404);
			return;
		}
		$schedule = $schedule[$id];
		$frm = new Form($this->request);
		$frm->input('name')
			->type('text')
			->required()
			->value(htmlspecialchars($schedule['name']))
			->setLabel(__('ragnarok-charmap', 'schedule-name'));
		$frm->input('castles')
			->type('text')
			->required()
			->value(implode(', ', $schedule['castles']))
			->setLabel(__('ragnarok-charmap', 'schedule-castles'))
			->setDescription(__('ragnarok-charmap', 'schedule-castles-desc'));
		$frm->input('starttime')
			->type('text')
			->required()
			->attr('pattern', self::TIME_PATTERN)
			->value($schedule['start_time'])
			->placeholder('HH:MM:SS')
			->setLabel(__('ragnarok-charmap', 'schedule-starttime'));
		$frm->select('startday')
		    ->value(array(
				0 => __('week', 0),
				1 => __('week', 1),
				2 => __('week', 2),
				3 => __('week', 3),
				4 => __('week', 4),
				5 => __('week', 5),
				6 => __('week', 6)
			))
		    ->required()
			->selected($schedule['start_day'])
		    ->setLabel(__('ragnarok-charmap', 'schedule-startday'));
		$frm->input('endtime')
			->type('text')
			->required()
			->attr('pattern', self::TIME_PATTERN)
			->value($schedule['end_time'])
			->placeholder('HH:MM:SS')
			->setLabel(__('ragnarok-charmap', 'schedule-endtime'));
		$frm->select('endday')
			->value(array(
				0 => __('week', 0),
				1 => __('week', 1),
				2 => __('week', 2),
				3 => __('week', 3),
				4 => __('week', 4),
				5 => __('week', 5),
				6 => __('week', 6)
			))
			->required()
			->selected($schedule['end_day'])
			->setLabel(__('ragnarok-charmap', 'schedule-endday'));
		$frm->submit();
		$frm->validate(function(Form $frm) {

		});
	}

	public function category_action()
	{
		if($this->request->method === 'POST' && $this->request->data('x-bulk')) {
			$this->response->status(302)->redirect(App::request()->uri->url());
			try {
				switch($this->request->getString('action')) {
					case 'delete':
						$deleted = array();
						foreach($this->request->getArray('categories') as $id) {
							if(($category = $this->charmap->shopCategory($id)) && $category->delete()) {
								$deleted[] = htmlspecialchars($category->name);
							}
						}
						$count = count($deleted);
						if($count === 1) {
							App::user()->addFlash('success', null, __('ragnarok-shop',
							                                          'category-deleted-s',
							                                          $deleted[0]));
						} else if($count) {
							App::user()->addFlash('success', null, __('ragnarok-shop',
							                                          'category-deleted-p',
							                                          $count));
						}
						break;
					case 'order':
						$order = array_flip(array_map('intval', $this->request->getArray('order')));
						if($this->charmap->setShopCategoryOrder($order)) {
							App::user()->addFlash('success', null, __('ragnarok-shop', 'order-saved'));
						}
						break;
				}
			} catch(\Exception $exception) {
				ErrorLog::logSql($exception);
				App::user()->addFlash('error', null, __('application', 'unexpected-error'));
			}
			return;
		}
		try {
			$frm = new Form($this->request);
			$frm->input('name')
				->type('text')
				->required()
				->setLabel(__('ragnarok-shop', 'category-name'));
			$frm->textarea('description')
				->setLabel(__('ragnarok-shop', 'category-desc'));
			$frm->submit();
			$frm->validate();
			if($frm->status !== Form::VALIDATION_SUCCESS) {
				$this->title = $this->theme->head->section = __('ragnarok-charmap', 'shop-categories');
				$this->theme->set('return', ac_build_url(array(
					'path' => array( 'r', $this->server->key, $this->charmap->key )
				)));
				$search = $this->charmap->shopCategorySearch()
					->calcRows(true)
					->order(array( 'order' => 'ASC' ))
					->query();
				$tpl = new Template;
				$tpl->set('form', $frm)
					->set('categories', $search->results)
					->set('category_count', $search->rowsFound)
					->set('page', $this);
				echo $tpl->render('admin/ragnarok/shop-category');
				return;
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));

			return;
		}
		$this->response->status(302)->redirect(App::request()->uri->url());
		try {
			if($category = $this->charmap->addShopCategory($this->request->getString('name'),
			                                            $this->request->getString('description'))) {
				App::user()->addFlash('success', null, __('ragnarok-shop',
				                                          'category-created',
				                                          htmlspecialchars($category->name)));
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			App::user()->addFlash('error', null, __('application', 'unexpected-error'));
		}
	}

	public function editcategory_action($id = null)
	{
		try {
			if(!$id || !($category = $this->charmap->shopCategory($id, 'id'))) {
				$this->error(404);
				return;
			}
			$frm = new Form($this->request);
			$frm->input('name', true)
				->type('text')
				->value(htmlspecialchars($category->name), false)
				->setLabel(__('ragnarok-shop', 'category-name'));
			$frm->textarea('description', true)
				->append(htmlspecialchars($category->name), false)
				->setLabel(__('ragnarok-shop', 'category-desc'));
			$frm->submit();
			$frm->validate();
			if($frm->status !== Form::VALIDATION_SUCCESS) {
				if($this->request->ajax) {
					$this->error(204);
					return;
				}
				$this->theme->set('return', ac_build_url(array(
					'path' => array( 'r', $this->server->key, $this->charmap->key ),
				    'action' => 'category'
				)));
				return;
			}
			$error = false;
			$message = '';
			try {
				$update = array();
				if(!$frm->field('name')->getWarning()) {
					$update['name'] = $this->request->getString('name');
				}
				if(!$frm->field('description')->getWarning()) {
					$update['description'] = $this->request->getString('description');
				}
				if($category->update($update)) {
					$message = __('ragnarok-shop', 'category-updated', htmlspecialchars($category->name));
				} else {
					$message = __('ragnarok-shop', 'category-not-updated');
				}
			} catch(\Exception $exception) {
				ErrorLog::logSql($exception);
				$error = true;
				$message = __('application', 'unexpected-error');
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function shop_action()
	{
		try {
			$frm = new Form($this->request);
			$frm->input('item')
		        ->type('text')
				->required()
		        ->setLabel(__('ragnarok', 'item-id'));
			$frm->input('price')
		        ->type('number')
				->attr('min', 1)
				->required()
		        ->setLabel(__('ragnarok', 'price'));
			$categories = array( '' => __('application', 'none') );
			foreach($this->charmap
				        ->shopCategorySearch()
				        ->order('`name`')
				        ->query() as $category) {
				$categories[$category->id] = htmlspecialchars($category->name);
			}
			$frm->select('category')
				->value($categories)
		        ->setLabel(__('ragnarok', 'shop-category'));
			$frm->submit();
			$self = $this;
			$item = null;
			$frm->validate(function(Form $frm) use ($self, &$item) {
					$itemName = $frm->request->getString('item');
					if($itemName) {
						if(ctype_digit($itemName)) {
							$item = $self->charmap->item($itemName);
						} else {
							$item = $self->charmap->item($itemName, 'name');
						}
					}
					if(!$item) {
						$frm->field('item')->setWarning(__('ragnarok', 'item-not-found'));
						return false;
					} else if($item->inCashShop) {
						$frm->field('item')->setWarning(__('ragnarok', 'shop-item-already-exists'));
						return false;
					}
					return true;
				});
			if($frm->status !== Form::VALIDATION_SUCCESS || !$item instanceof ItemData) {
				$this->title = $this->theme->head->section = __('ragnarok-charmap', 'shop');
				$tpl = new Template;
				$tpl->set('form', $frm)
					->set('items', $this->charmap
						->itemShopSearch()
						->order('shop_order')
						->query()
						->results)
					->set('page', $this);
				echo $tpl->render('admin/ragnarok/shop');
				return;
			}
			$this->response->status(302)->redirect(App::request()->uri->url());
			try {
				/**
				 * @var $item \Aqua\Ragnarok\ItemData
				 */
				if($item->setShopData($this->request->getInt('price'),
				                      $this->request->getInt('category') ?: null)) {
					App::user()->addFlash('success', null, __('ragnarok-shop', 'item-added', htmlspecialchars($item->jpName)));
				} else {
					App::user()->addFlash('success', null, __('ragnarok-shop', 'item-not-added', htmlspecialchars($item->jpName)));
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

	public function item_action($id = null)
	{
		try {
			if(!$id || !($item = $this->charmap->item($id)) || !$item->inCashShop) {
				$this->error(404);
				return;
			}
			$frm = new Form($this->request);
		} catch(\Exception $exception) {

		}
	}

	public function char_action()
	{
		try {
			$currentPage = $this->request->uri->getInt('page', 1, 1);
			$where = array();
			if($x = $this->request->getString('n')) {
				$where['name'] = array( Search::SEARCH_LIKE, '%' . addcslashes($x, '%_\\') . '%' );
			}
			if($x = $this->request->getString('m')) {
				$where['map'] = array( Search::SEARCH_LIKE, '%' . addcslashes($x, '%_\\') . '%' );
			}
			if(($x = $this->request->getArray('class', null)) && !empty($x)) {
				array_unshift($x, Search::SEARCH_IN);
				$where['class'] = $x;
			}
			$search = $this->charmap->charSearch()
				->calcRows(true)
				->where($where)
				->limit(($currentPage - 1) * self::$charactersPerPage, self::$charactersPerPage)
				->query();
			$pgn = new Pagination(App::request()->uri,
			                      ceil($search->rowsFound / self::$logsPerPage),
			                      $currentPage);
			$tpl = new Template;
			$tpl->set('characters', $search->results)
				->set('character_count', $search->rowsFound)
				->set('paginator', $pgn)
				->set('page', $this);
			echo $tpl->render('admin/ragnarok/char/search');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function viewchar_action($id = null)
	{
		try {
			if(!$id || !($char = $this->charmap->character($id, 'id'))) {
				$this->error(404);
				return;
			}
			$this->theme->set('return', ac_build_url(array(
				'path' => array( 'r', $this->server->key, $this->charmap->key ),
			    'action' => 'char'
			)));
			$tpl = new Template;
			$tpl->set('char', $char)
			    ->set('page', $this);
			echo $tpl->render('admin/ragnarok/view-char');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function inventory_action($id = null)
	{
		try {
			if(!$id || !($char = $this->charmap->character($id, 'id'))) {
				$this->error(404);
				return;
			}
			$this->theme->set('return', ac_build_url(array(
				'path' => array( 'r', $this->server->key, $this->charmap->key ),
				'action' => 'viewchar',
			    'arguments' => array( $char->id )
			)));
			$currentPage = $this->request->uri->getInt('page', 1, 1);
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function cart_action($id = null)
	{
		try {
			if(!$id || !($char = $this->charmap->character($id, 'id'))) {
				$this->error(404);
				return;
			}
			$this->theme->set('return', ac_build_url(array(
				'path' => array( 'r', $this->server->key, $this->charmap->key ),
				'action' => 'viewchar',
				'arguments' => array( $char->id )
			)));
			$currentPage = $this->request->uri->getInt('page', 1, 1);
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function editchar_action($id = null)
	{
		try {
			if(!$id || !($char = $this->charmap->character($id, 'id'))) {
				$this->error(404);
				return;
			}
			$this->theme->set('return', ac_build_url(array(
				'path' => array( 'r', $this->server->key, $this->charmap->key ),
				'action' => 'viewchar',
				'arguments' => array( $char->id )
			)));

		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function guild_action()
	{
		try {
			$this->title = $this->theme->head->section = __('ragnarok-charmap', 'guilds');
			$currentPage = $this->request->uri->getInt('page', 1, 1);
			$where = array();
			if($x = $this->request->uri->getString('n')) {
				$where['name'] = array( Search::SEARCH_LIKE, '%' . addcslashes($x, '%_\\') . '%');
			}
			if($x = $this->request->uri->getString('m')) {
				$where['master'] = array( Search::SEARCH_LIKE, '%' . addcslashes($x, '%_\\') . '%');
			}
			$search = $this->charmap->guildSearch()
				->calcRows(true)
				->where($where)
				->limit(($currentPage - 1) * self::$guildsPerPage, self::$guildsPerPage)
				->query();
			if($search->rowsFound) {
				$characters = $search->getColumn('master_id');
				array_unshift($characters, Search::SEARCH_IN);
				$this->charmap->charSearch()->where(array( 'id' => $characters ))->query();
			}
			$pgn = new Pagination(App::request()->uri,
			                      ceil($search->rowsFound / self::$guildsPerPage),
			                      $currentPage);
			$tpl = new Template;
			$tpl->set('guilds', $search->results)
			    ->set('guild_count', $search->rowsFound)
				->set('pagination', $pgn)
				->set('page', $this);
			echo $tpl->render('admin/ragnarok/guild/main');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function viewguild_action($id = null)
	{
		try {
			if(!$id || !($guild = $this->charmap->guild($id, 'id'))) {
				$this->error(404);
				return;
			}
			$this->theme->set('return', ac_build_url(array(
				'path' => array( 'r', $this->server->key, $this->charmap->key ),
				'action' => 'guild',
			)));
			$tpl = new Template;
			$tpl->set('guild', $guild)
				->set('page', $this);
			echo $tpl->render('admin/ragnarok/guild/view');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function gstorage_action($id = null)
	{

	}

	public function zenylog_action()
	{
		try {
			$this->title = $this->theme->head->section = __('ragnarok-charmap', 'zeny-log');
			$currentPage = $this->request->uri->getInt('page', 1, 1);
			$search = $this->charmap->log->searchZenyLog()
				->calcRows(true)
				->limit(($currentPage - 1) * self::$logsPerPage, self::$logsPerPage)
				->query();
			$pgn = new Pagination(App::request()->uri,
			                      ceil($search->rowsFound / self::$logsPerPage),
			                      $currentPage);
			if($search->rowsFound) {
				$characters = array_unique(array_merge($search->getColumn('char_id'), $search->getColumn('src_id')));
				array_unshift($characters, Search::SEARCH_IN);
				$this->charmap->charSearch()->where(array( 'id' => $characters ))->query();
			}
			$tpl = new Template;
			$tpl->set('log', $search->results)
			    ->set('count', $search->rowsFound)
			    ->set('paginator', $pgn)
			    ->set('page', $this);
			echo $tpl->render('admin/ragnarok/log/zeny-log');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function shoplog_action()
	{
		try {
			$this->title = $this->theme->head->section = __('ragnarok-charmap', 'shop-log');
			$currentPage = $this->request->uri->getInt('page', 1, 1);
			$search = $this->charmap->log->searchCashShopLog()
				->calcRows(true)
				->limit(($currentPage - 1) * self::$logsPerPage, self::$logsPerPage)
				->query();
			if($search->rowsFound !== 0) {
				$accounts = $search->getColumn('account_id');
				foreach($accounts as $id) {
					if(!array_key_exists($id, $this->charmap->server->login->accounts)) {
						$this->charmap->server->login->accounts[$id] = null;
					}
				}
				array_unshift($accounts, Search::SEARCH_IN);
				$this->charmap->server->login->search()->where(array( 'id' => $accounts ))->query();
			}
			$pgn = new Pagination(App::request()->uri,
			                      ceil($search->rowsFound / self::$logsPerPage),
			                      $currentPage);
			$tpl = new Template;
			$tpl->set('log', $search->results)
				->set('count', $search->rowsFound)
				->set('paginator', $pgn)
				->set('page', $this);
			echo $tpl->render('admin/ragnarok/log/shop-log');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function viewshoplog_action($id = null)
	{
		try {
			if(!$id || !($log = $this->charmap->log->getCashShopLog($id))) {
				$this->error(404);
				return;
			}
			$this->title = $this->theme->head->section = __('ragnarok-charmap', 'view-shop-log');
			$tpl = new Template;
			$tpl->set('log', $log)
				->set('page', $this);
			echo $tpl->render('admin/ragnarok/log/view-shop-log');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function atcmdlog_action()
	{
		try {
			$this->title = $this->theme->head->section = __('ragnarok-charmap', 'atcommand-log');
			$currentPage = $this->request->uri->getInt('page', 1, 1);
			$search = $this->charmap->log->searchAtcommandLog()
				->calcRows(true)
				->limit(($currentPage - 1) * self::$logsPerPage, self::$logsPerPage)
				->query();
			$pgn = new Pagination(App::request()->uri,
			                      ceil($search->rowsFound / self::$logsPerPage),
			                      $currentPage);
			$tpl = new Template;
			$tpl->set('log', $search->results)
			    ->set('count', $search->rowsFound)
			    ->set('paginator', $pgn)
			    ->set('page', $this);
			echo $tpl->render('admin/ragnarok/log/atcommand-log');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function picklog_action()
	{
		try {
			$this->title = $this->theme->head->section = __('ragnarok-charmap', 'pick-log');
			$currentPage = $this->request->uri->getInt('page', 1, 1);
			$search = $this->charmap->log->searchPickLog()
				->calcRows(true)
				->limit(($currentPage - 1) * self::$logsPerPage, self::$logsPerPage)
				->query();
			if($search->rowsFound !== 0) {
				$characters = $search->getColumn('char_id');
				foreach($characters as $id) {
					if(!array_key_exists($id, $this->charmap->characters)) {
						$this->charmap->characters[$id] = null;
					}
				}
				$items      = $search->getColumn('item_id');
				array_unshift($characters, Search::SEARCH_IN);
				array_unshift($items, Search::SEARCH_IN);
				$this->charmap->charSearch()->where(array( 'id' => $characters ))->query();
				$this->charmap->itemSearch()->where(array( 'id' => $items ))->query();
			}
			$pgn = new Pagination(App::request()->uri,
			                      ceil($search->rowsFound / self::$logsPerPage),
			                      $currentPage);
			$tpl = new Template;
			$tpl->set('log', $search->results)
			    ->set('count', $search->rowsFound)
			    ->set('paginator', $pgn)
			    ->set('page', $this);
			echo $tpl->render('admin/ragnarok/log/pick-log');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function chatlog_action()
	{
		try {
			$this->title = $this->theme->head->section = __('ragnarok-charmap', 'chat-log');
			$currentPage = $this->request->uri->getInt('page', 1, 1);
			$search = $this->charmap->log->searchChatLog()
				->calcRows(true)
				->limit(($currentPage - 1) * self::$logsPerPage, self::$logsPerPage)
				->query();
			if($search->rowsFound !== 0) {
				$characters = $search->getColumn('src_char_id');
				$accounts   = $search->getColumn('src_account_id');
				foreach($characters as $id) {
					if(!array_key_exists($id, $this->charmap->characters)) {
						$this->charmap->characters[$id] = null;
					}
				}
				foreach($accounts as $id) {
					if(!array_key_exists($id, $this->charmap->server->login->accounts)) {
						$this->charmap->server->login->accounts[$id] = null;
					}
				}
				array_unshift($characters, Search::SEARCH_IN);
				array_unshift($accounts, Search::SEARCH_IN);
				$this->charmap->charSearch()->where(array( 'id' => $characters ))->query();
				$this->charmap->server->login->search()->where(array( 'id' => $accounts ))->query();
			}
			$pgn = new Pagination(App::request()->uri,
			                      ceil($search->rowsFound / self::$logsPerPage),
			                      $currentPage);
			$tpl = new Template;
			$tpl->set('log', $search->results)
			    ->set('count', $search->rowsFound)
			    ->set('paginator', $pgn)
			    ->set('page', $this);
			echo $tpl->render('admin/ragnarok/log/chat-log');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}
}
