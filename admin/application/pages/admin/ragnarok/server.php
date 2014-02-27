<?php
namespace Page\Admin\ragnarok;

use Aqua\Core\App;
use Aqua\Core\Settings;
use Aqua\Log\ErrorLog;
use Aqua\Site\Page;
use Aqua\UI\Form;
use Aqua\UI\Menu;
use Aqua\UI\Template;

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

	public function run()
	{
		$this->server = App::$activeServer;
		$this->charmap = App::$activeCharMapServer;
		if(!$this->server) {
			$this->error(404);
		} else if($this->charmap) {
			$nav = new Menu;
			$base_url = ac_build_url(array(
					'path' => array( 'ro', $this->server->key, 's', $this->charmap->key ),
					'action' => ''
				));
		} else if($this->request->uri->action !== 'index') {
			$this->error(404);
		}
	}

	public function index_action()
	{
		if($this->charmap) {
			$this->server_index();
			return;
		}
		try {
			$frm = new Form($this->request);
			$frm->input('name', true)
		        ->type('text')
		        ->attr('maxlength', 255)
		        ->required()
		        ->setLabel(__('ragnarok-charmap-settings', 'name-label'));
			$frm->input('key', true)
		        ->type('text')
		        ->attr('maxlength', 255)
		        ->required()
		        ->setLabel(__('ragnarok-charmap-settings', 'key-label'))
				->setDescription(__('ragnarok-charmap-settings', 'key-desc'));
			$frm->input('timezone', true)
		        ->type('text')
		        ->setLabel(__('ragnarok-charmap-settings', 'timezone-label'))
				->setDescription(__('ragnarok-charmap-settings', 'timezone-desc'));
			$frm->input('char-host', true)
		        ->type('text')
		        ->required()
		        ->setLabel(__('ragnarok-charmap-settings', 'char-host-label'));
			$frm->input('char-port', true)
		        ->type('number')
				->attr('min', 0)
		        ->required()
				->value(6121, false)
		        ->setLabel(__('ragnarok-charmap-settings', 'char-port-label'));
			$frm->input('map-host', true)
		        ->type('text')
		        ->required()
		        ->setLabel(__('ragnarok-charmap-settings', 'map-host-label'));
			$frm->input('map-port', true)
		        ->type('number')
				->attr('min', 0)
		        ->required()
				->value(5121, false)
		        ->setLabel(__('ragnarok-charmap-settings', 'map-port-label'));
			$frm->input('sql-host', true)
				->type('text')
			    ->required()
			    ->setLabel(__('ragnarok-charmap-settings', 'db-host-label'));
			$frm->input('sql-port', true)
				->type('number')
				->attr('min', 0)
			    ->required()
				->value(3306, false)
			    ->setLabel(__('ragnarok-charmap-settings', 'db-port-label'));
			$frm->input('sql-database', true)
				->type('text')
			    ->required()
			    ->setLabel(__('ragnarok-charmap-settings', 'db-database-label'));
			$frm->input('sql-username', true)
				->type('text')
				->required()
			    ->setLabel(__('ragnarok-charmap-settings', 'db-username-label'));
			$frm->input('sql-password', true)
				->type('password')
			    ->setLabel(__('ragnarok-charmap-settings', 'db-password-label'));
			$frm->input('sql-charset', true)
				->type('text')
				->value('UTF8', false)
			    ->setLabel(__('ragnarok-charmap-settings', 'db-charset-label'));
			$frm->input('sql-timezone', true)
				->type('text')
			    ->setLabel(__('ragnarok-charmap-settings', 'db-timezone-label'))
			    ->setDescription(__('ragnaork-charmap-settings', 'db-timezone-desc'));
			$frm->input('log-host', true)
				->type('text')
			    ->required()
			    ->setLabel(__('ragnarok-charmap-settings', 'db-host-label'));
			$frm->input('log-port', true)
				->type('number')
				->attr('min', 0)
			    ->required()
				->value(3306, false)
			    ->setLabel(__('ragnarok-charmap-settings', 'db-port-label'));
			$frm->input('log-database', true)
				->type('text')
			    ->required()
			    ->setLabel(__('ragnarok-charmap-settings', 'db-database-label'));
			$frm->input('log-username', true)
				->type('text')
				->required()
			    ->setLabel(__('ragnarok-charmap-settings', 'db-username-label'));
			$frm->input('log-password', true)
				->type('password')
			    ->setLabel(__('ragnarok-charmap-settings', 'db-password-label'));
			$frm->input('log-charset', true)
				->type('text')
				->value('UTF8', false)
			    ->setLabel(__('ragnarok-charmap-settings', 'db-charset-label'));
			$frm->input('log-timezone', true)
				->type('text')
			    ->setLabel(__('ragnarok-charmap-settings', 'db-timezone-label'))
			    ->setDescription(__('ragnaork-charmap-settings', 'db-timezone-desc'));
			$frm->checkbox('renewal', true)
		        ->value(array( '1' => '' ))
				->checked(1, false)
		        ->setLabel(__('ragnarok-charmap-settings', 'renewal-label'));
			$frm->input('online-stats', true)
				->type('number')
				->attr('min', 0)
				->value(30, false)
		        ->setLabel(__('ragnarok-charmap-settings', 'online-stats-label'))
		        ->setDescription(__('ragnarok-charmap-settings', 'online-stats-desc'));
			$frm->input('fame')
				->type('number')
				->attr('min', 0)
				->required()
				->value(10, false)
				->setLabel(__('ragnarok-charmap-settings', 'fame-label'))
				->setLabel(__('ragnarok-charmap-settings', 'fame-desc'));
			$frm->input('default-map')
		        ->type('text')
		        ->value('prontera', false)
				->required()
		        ->setLabel(__('ragnarok-charmap-settings', 'default-map-label'))
		        ->setDescription(__('ragnarok-charmap-settings', 'default-map-desc'));
			$frm->input('default-x')
		        ->type('number')
				->attr('min', 0)
				->required()
		        ->value(156, false)
		        ->setLabel(__('ragnarok-charmap-settings', 'default-x-label'));
			$frm->input('default-y')
		        ->type('number')
				->attr('min', 0)
				->required()
		        ->value(191, false)
		        ->setLabel(__('ragnarok-charmap-settings', 'default-y-label'));
			$frm->input('map-restrictions')
				->type('text')
				->value('/^(sec_pri|[^@]+@[0-9]+)(\.gat)?$/i', false)
				->setLabel(__('ragnarok-charmap-settings', 'map-restriction-label'))
				->setDescription(__('ragnarok-charmap-settings', 'map-restriction-desc'));
			$frm->submit();
			$self = $this;
			$dbh = $ldbh = null;
			$frm->validate(function(Form $frm, &$message) use(&$self, &$dbh, &$ldbh) {
					$error = false;
					if($regex = $frm->request->getString('map-restrictions')) {
						@preg_match($regex, '');
						if($mes = $self->pcreErrorStr(preg_last_error())) {
							$frm->field('username-regex')->setWarning($mes);
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
				$this->title = $this->theme->head->section = __('ragnarok-charmap-settings', 'new-server', htmlspecialchars($this->server->name));
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
				try { $dbh->exec(file_get_contents(\Aqua\ROOT . '/schema/charmap/item_db2.description.sql')); } catch(\PDOException $e) { }
				try { $dbh->exec(file_get_contents(\Aqua\ROOT . '/schema/charmap/char.class.sql')); } catch(\PDOException $e) { }
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
				;
				");
				$sth->bindValue(':chost', $this->request->getString('char-host'));
				$sth->bindValue(':cport', $this->request->getInt('char-port'));
				$sth->bindValue(':mhost', $this->request->getInt('map-port'));
				$sth->bindValue(':mport', $this->request->getInt('map-port'));
				$sth->bindValue(':timezone', $this->request->getString('timezone'));
				$sth->bindValue(':timeout', $this->request->getInt('status-timeout'));
				$sth->bindValue(':ttl', $this->request->getInt('status-cache'));
				$sth->bindValue(':online', $this->request->getInt('online-stats'));
				$sth->bindValue(':renewal', $this->request->getInt('renewal') ? '1' : '');
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
				$settings->export($file);
				$this->response->status(302)->redirect(ac_build_url(array( 'path' => array( 'ro', $this->server->key, 's', $key ) )));
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
		var_dump($this->charmap);
	}

	public function rates_action()
	{
		$frm = new Form($this->request);
		$frm->checkbox('logarithmic-drops', true)
			->value(array( '1' => '' ))
			->setLabel(__('ragnarok-charmap-settings', 'logarithmic-drops-label'));
		$frm->input('base-exp', true)
			->type('number')
			->attr('min', 0)
			->required()
			->value(100, false)
			->setLabel(__('ragnarok-charmap-settings', 'base-exp-label'));
		$frm->input('job-exp', true)
			->type('number')
			->attr('min', 0)
			->required()
			->value(100, false)
			->setLabel(__('ragnarok-charmap-settings', 'job-exp-label'));
		$frm->input('quest-exp', true)
			->type('number')
			->attr('min', 0)
			->required()
			->value(100, false)
			->setLabel(__('ragnarok-charmap-settings', 'quest-exp-label'));
		$frm->input('mvp-exp', true)
			->type('number')
			->attr('min', 0)
			->required()
			->value(100, false)
			->setLabel(__('ragnarok-charmap-settings', 'mvp-exp-label'));
		$frm->input('common_rate', true)
			->type('number')
			->attr('min', 0)
			->required()
			->value(100, false)
			->setLabel(__('ragnarok-charmap-settings', 'rate-common-label'));
		$frm->input('common_boss', true)
			->type('number')
			->attr('min', 0)
			->required()
			->value(100, false)
			->setLabel(__('ragnarok-charmap-settings', 'boss-common-label'));
		$frm->input('common_min', true)
			->type('number')
			->attr('min', 0)
			->required()
			->value(1, false)
			->setLabel(__('ragnarok-charmap-settings', 'min-common-label'));
		$frm->input('common_max', true)
			->type('number')
			->attr('min', 0)
			->required()
			->value(1, false)
			->setLabel(__('ragnarok-charmap-settings', 'max-common-label'));
		$frm->input('heal_rate', true)
			->type('number')
			->attr('min', 0)
			->required()
			->value(100, false)
			->setLabel(__('ragnarok-charmap-settings', 'rate-heal-label'));
		$frm->input('heal_boss', true)
			->type('number')
			->attr('min', 0)
			->required()
			->value(100, false)
			->setLabel(__('ragnarok-charmap-settings', 'boss-heal-label'));
		$frm->input('heal_min', true)
			->type('number')
			->attr('min', 0)
			->required()
			->value(1, false)
			->setLabel(__('ragnarok-charmap-settings', 'min-heal-label'));
		$frm->input('heal_max', true)
			->type('number')
			->attr('min', 0)
			->required()
			->value(1, false)
			->setLabel(__('ragnarok-charmap-settings', 'max-heal-label'));
		$frm->input('equip_rate', true)
			->type('number')
			->attr('min', 0)
			->required()
			->value(100, false)
			->setLabel(__('ragnarok-charmap-settings', 'rate-equip-label'));
		$frm->input('equip_boss', true)
			->type('number')
			->required()
			->attr('min', 0)
			->value(100, false)
			->setLabel(__('ragnarok-charmap-settings', 'boss-equip-label'));
		$frm->input('equip_min', true)
			->type('number')
			->attr('min', 0)
			->required()
			->value(1, false)
			->setLabel(__('ragnarok-charmap-settings', 'min-equip-label'));
		$frm->input('equip_max', true)
			->type('number')
			->attr('min', 0)
			->required()
			->value(1, false)
			->setLabel(__('ragnarok-charmap-settings', 'max-equip-label'));
		$frm->input('card_rate', true)
			->type('number')
			->attr('min', 0)
			->required()
			->value(100, false)
			->setLabel(__('ragnarok-charmap-settings', 'rate-card-label'));
		$frm->input('card_boss', true)
			->type('number')
			->attr('min', 0)
			->required()
			->value(100, false)
			->setLabel(__('ragnarok-charmap-settings', 'boss-card-label'));
		$frm->input('card_min', true)
			->type('number')
			->attr('min', 0)
			->required()
			->value(1, false)
			->setLabel(__('ragnarok-charmap-settings', 'min-card-label'));
		$frm->input('card_max', true)
			->type('number')
			->attr('min', 0)
			->required()
			->value(1, false)
			->setLabel(__('ragnarok-charmap-settings', 'max-card-label'));
		$frm->input('mvp_rate', true)
			->type('number')
			->attr('min', 0)
			->required()
			->value(100, false)
			->setLabel(__('ragnarok-charmap-settings', 'rate-mvp-label'));
		$frm->input('mvp_boss', true)
			->type('number')
			->attr('min', 0)
			->required()
			->value(100, false)
			->setLabel(__('ragnarok-charmap-settings', 'boss-mvp-label'));
		$frm->input('mvp_min', true)
			->type('number')
			->attr('min', 0)
			->required()
			->value(1, false)
			->setLabel(__('ragnarok-charmap-settings', 'min-mvp-label'));
		$frm->input('mvp_max', true)
			->type('number')
			->attr('min', 0)
			->required()
			->value(1, false)
			->setLabel(__('ragnarok-charmap-settings', 'max-mvp-label'));

	}

	public function shop_action()
	{
		try {
			$frm = new Form($this->request);
			$frm->input('item')
		        ->type('number')
				->attr('min', 0)
				->required()
		        ->setLabel(__('ragnarok-shop', 'item-id'));
			$frm->input('price')
		        ->type('number')
				->attr('min', 1)
				->required()
		        ->setLabel(__('ragnarok-shop', 'price'));
			$frm->input('category')
		        ->type('text')
		        ->attr('maxlength', 255)
		        ->setLabel(__('ragnarok-shop', 'price'));
			$self = $this;
			$frm->submit(function(Form $frm) use ($self) {
					if(!($item = $self->charmap->item($frm->request->getInt('item')))) {
						$frm->field('item')->setWarning(__('ragnarok-shop', 'item-not-found'));
						return false;
					} else if($item->inCashShop) {
						$frm->field('item')->setWarning(__('ragnarok-shop', 'item-already-exists'));
						return false;
					}
					return true;
				});
			if($frm->status !== Form::VALIDATION_SUCCESS) {

			}
			try {

			} catch(\Exception $exception) {

			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function char_action()
	{
		try {

		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function view_char_action()
	{
		try {

		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function edit_char_action()
	{
		try {

		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function log_action($type = '')
	{
		switch($type) {
			case 'zeny':
				break;
			case 'shop':
				break;
			case 'pick':
				break;
			case 'atcommand':
				break;
			case 'branch':
				break;
			case 'mvp':
				break;
			case 'npc':
				break;
			case 'chat':
				break;
			default: $this->error(404); return;
		}
	}


	public function pcreErrorStr($id)
	{
		switch($id) {
			default: return false;
			case PREG_INTERNAL_ERROR:
				return __('exception', 'internal-pcre-error');
			case PREG_BACKTRACK_LIMIT_ERROR:
				return __('exception', 'pcre-backtrack-limit');
			case PREG_RECURSION_LIMIT_ERROR:
				return __('exception', 'pcre-recursion-limit');
			case PREG_BAD_UTF8_ERROR:
				return __('exception', 'pcre-bad-utf8');
			case PREG_BAD_UTF8_OFFSET_ERROR:
				return __('exception', 'pcre-bad-utf8-offset');
		}
	}
}
