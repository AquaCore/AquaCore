<?php
namespace Aqua\Ragnarok\Server;

use Aqua\Core\App;
use Aqua\Event\Event;
use Aqua\Ragnarok\Account as RagnarokAccount;
use Aqua\Ragnarok\Server;
use Aqua\SQL\Query;
use Aqua\SQL\Search;
use Aqua\User\Account as UserAccount;

class Login
{
	/**
	 * @var \Aqua\Ragnarok\Server
	 */
	public $server;
	/**
	 * @var \Aqua\Ragnarok\Server\LoginLog
	 */
	public $log;
	/**
	 * @var array
	 */
	public $settings = null;
	/**
	 * @var array
	 */
	public $groups = null;
	/**
	 * @var array
	 */
	public $cache = null;
	/**
	 * @var \PDO
	 */
	public $dbh;
	/**
	 * @var string
	 */
	public $db;
	/**
	 * @var array
	 */
	public $dbSettings = array();
	/**
	 * @var array
	 */
	public $tables = array();
	/**
	 * @var \Aqua\Ragnarok\Account[]
	 */
	public $accounts = array();

	const REGISTRATION_USERNAME_TAKEN = 1;
	const REGISTRATION_ACCOUNT_LIMIT  = 2;

	const MIN_ACCOUNT_ID = 2000000;

	const FIELD_OK                 = 0;
	const FIELD_INVALID_SIZE       = 1;
	const FIELD_INVALID_CHARACTERS = 2;

	const CACHE_RECENT_ACCOUNTS = 5;
	const CACHE_TTL             = 0;

	/**
	 * @param \Aqua\Ragnarok\Server $server
	 * @param array                 $settings
	 */
	public function __construct(Server &$server, array $settings)
	{
		$this->server     = & $server;
		$this->dbSettings = $settings['db'];
		if(isset($settings['tables'])) {
			$this->tables = $settings['tables'] + $this->tables;
		}
		if(isset($settings['database_name'])) {
			$this->db = $settings['database_name'];
		}
		$this->log = new LoginLog(
			$this,
			isset($settings['log_database_name']) ? $settings['log_database_name'] : null,
			$settings['log_db'],
			isset($settings['log_tables']) ? $settings['log_tables'] : array()
		);
		$login     = $this;
		Event::bind('account.update', function ($event, UserAccount $account, $update) use ($login) {
			$update = array_intersect_key($update, array( 'email' => '', 'birthday' => '' ));
			if(empty($update)) {
				return;
			}
			foreach($login->getAccounts($account) as $acc) {
				$acc->update($update);
			}
		});
	}

	/**
	 * @param string $key
	 * @param mixed  $val
	 */
	public function setOption($key, $val = null)
	{
		if(!is_array($key)) {
			$key = array( $key => $val );
		}
		$sth = $this->connection()->prepare("
		REPLACE INTO {$this->table('ac_login_settings')} (`key`, val)
		VALUES (? , ?)
		");
		foreach($key as $k => $v) {
			$sth->bindValue(1, $k, \PDO::PARAM_STR);
			$sth->bindValue(2, (string)$v, \PDO::PARAM_STR);
			$sth->execute();
			$this->settings[$k] = $v;
		}
		$this->fetchSettings();
		App::cache()->store("ro.{$this->server->key}.settings", $this->settings);
	}

	/**
	 * @param string $key
	 * @param mixed  $default
	 * @return mixed
	 */
	public function getOption($key, $default = null)
	{
		$this->settings !== null or $this->fetchSettings();

		return (isset($this->settings[$key]) ? $this->settings[$key] : $default);
	}

	/**
	 * @param int $id
	 * @return array|string
	 */
	public function group($id)
	{
		$this->groups !== null or $this->fetchGroups();

		return (isset($this->groups[$id]) ? $this->groups[$id] : null);
	}

	/**
	 * @param string      $username
	 * @param string      $password
	 * @param string|null $pincode
	 * @return int|null
	 */
	public function checkCredentials($username, $password, $pincode = null)
	{
		$select = Query::select($this->connection())
			->columns(array( 'id' => 'account_id' ))
			->setColumnType(array( 'id' => 'integer' ))
			->from($this->table('login'))
			->limit(1)
			->where(array(
				'userid'     => $username,
				'user_pass'  => $password,
				'account_id' => array(
					Search::SEARCH_LOWER | Search::SEARCH_DIFFERENT,
					self::MIN_ACCOUNT_ID
				)
			));
		if($this->getOption('use-pincode') && $pincode !== null) {
			$select->where(array( 'pincode' => $pincode ));
		}
		if($this->getOption('case-sensitive-username')) {
			$select->having(array( 'BINARY userid' => $username ));
		}
		$select->query();

		return $select->get('id');
	}

	/**
	 * @param UserAccount $user
	 * @return \Aqua\Ragnarok\Account[]
	 * @throws \Aqua\Ragnarok\Exception\LoginServerException
	 */
	public function getAccounts(UserAccount $user)
	{
		return $this->search()
			->where(array( 'user_id' => $user->id ))
			->query()
			->results;
	}

	/**
	 * @return \Aqua\SQL\Search
	 */
	public function search()
	{
		$search = Query::search($this->connection())
			->columns(array(
				'id'              => 'l.account_id',
				'user_id'         => 'l.ac_user_id',
				'cp_options'      => 'l.ac_options',
				'username'        => 'l.userid',
				'password'        => 'l.user_pass',
				'sex'             => '(l.sex + 0)',
				'group_id'        => 'l.group_id',
				'state'           => 'l.state',
				'email'           => 'l.email',
				'unban_time'      => 'l.unban_time',
				'last_ip_address' => 'l.last_ip',
				'login_count'     => 'l.logincount',
				'last_login'      => 'UNIX_TIMESTAMP(l.lastlogin)',
				'birthday'        => 'UNIX_TIMESTAMP(l.birthdate)',
			))->whereOptions(array(
				'id'              => 'l.account_id',
				'user_id'         => 'l.ac_user_id',
				'cp_options'      => 'l.ac_options',
				'username'        => 'l.userid',
				'password'        => 'l.user_pass',
				'sex'             => 'l.sex',
				'group_id'        => 'l.group_id',
				'state'           => 'l.state',
				'email'           => 'l.email',
				'unban_time'      => 'l.unban_time',
				'last_ip_address' => 'l.last_ip',
				'login_count'     => 'l.logincount',
				'last_login'      => 'l.lastlogin',
				'birthday'        => 'l.birthdate',
			))
			->from($this->table('login'), 'l')
			->groupBy('l.account_id')
			->parser(array( $this, 'parseAccountSql' ));
		if((int)$this->getOption('default-slots')) {
			$search
				->columns(array( 'slots' => 'l.character_slots' ))
				->whereOptions(array( 'slots' => 'l.character_slots' ));
		}
		if($this->getOption('use-pincode')) {
			$search
				->columns(array( 'pincode' => 'l.pincode', 'pincode_change' => 'l.pincode_change' ))
				->whereOptions(array( 'pincode' => 'l.pincode', 'pincode_change' => 'l.pincode_change' ));
		}
		if($this->getOption('case-sensitive-username')) {
			$search->havingOptions(array( 'username' => 'BINARY l.userid' ));
		}

		return $search;
	}

	/**
	 * @param int|string $id
	 * @param string     $type
	 * @return \Aqua\Ragnarok\Account|null
	 */
	public function get($id, $type = 'id')
	{
		if($type === 'id' && isset($this->accounts[$id])) {
			return $this->accounts[$id];
		}
		$select = Query::select($this->connection())
			->columns(array(
				'id'              => 'l.account_id',
				'user_id'         => 'l.ac_user_id',
				'cp_options'      => 'l.ac_options',
				'username'        => 'l.userid',
				'password'        => 'l.user_pass',
				'sex'             => '(l.sex + 0)',
				'group_id'        => 'l.group_id',
				'state'           => 'l.state',
				'email'           => 'l.email',
				'unban_time'      => 'l.unban_time',
				'last_ip_address' => 'l.last_ip',
				'login_count'     => 'l.logincount',
				'last_login'      => 'UNIX_TIMESTAMP(l.lastlogin)',
				'birthday'        => 'UNIX_TIMESTAMP(l.birthdate)',
			))
			->from($this->table('login'), 'l')
			->limit(1)
			->parser(array( $this, 'parseAccountSql' ));
		if((int)$this->getOption('default-slots')) {
			$select->columns(array( 'slots' => 'l.character_slots' ));
		}
		if($this->getOption('use-pincode')) {
			$select->columns(array( 'pincode' => 'l.pincode', 'pincode_change' => 'l.pincode_change' ));
		}
		switch($type) {
			case 'id':
				$select->where(array( 'l.account_id' => $id ));
				break;
			case 'username':
				$select->where(array( 'l.userid' => $id ));
				if($this->getOption('case-sensitive-username')) {
					$select->having(array( 'BINARY l.userid' => $id ));
				}
				break;
			default:
				return null;
		}
		$select->query();

		return ($select->valid() ? $select->current() : null);
	}

	/**
	 * @param string $username
	 * @param        $message
	 * @return int
	 */
	public function checkValidUsername($username, &$message)
	{
		$error_id = self::FIELD_OK;
		$feedback = array( $username, &$error_id, &$message );
		if(Event::fire('ragnarok.validate-username', $feedback) === false) return $error_id;
		$len     = strlen($username);
		$min_len = $this->getOption('username-min-len', 4);
		$max_len = min($this->getOption('username-max-len', 23), 23);
		$regex   = $this->getOption('username-regex');
		if($len < $min_len || $len > $max_len) {
			$message = __('ragnarok', 'username-len', $min_len, $max_len);

			return self::FIELD_INVALID_SIZE;
		}
		if($regex && preg_match_all($regex, $username, $match)) {
			$message = __('ragnarok', 'username-invalid-character', implode(', ', array_unique($match[0])));

			return self::FIELD_INVALID_CHARACTERS;
		}

		return self::FIELD_OK;
	}

	/**
	 * @param string $password
	 * @param        $message
	 * @return int
	 */
	public function checkValidPassword($password, &$message)
	{
		$error_id = self::FIELD_OK;
		$feedback = array( $password, &$error_id, &$message );
		if(Event::fire('ragnarok.validate-password', $feedback) === false) return $error_id;
		$len     = strlen($password);
		$min_len = $this->getOption('password-min-len', 4);
		$max_len = $this->getOption('password-max-len', 32);
		$regex   = $this->getOption('password-regex');
		if(!$this->getOption('use-md5')) {
			$max_len = min($max_len, 32);
		}
		if($len < $min_len || $len > $max_len) {
			$message = __('ragnarok', 'password-len', $min_len, $max_len);

			return self::FIELD_INVALID_SIZE;
		}
		if($regex && preg_match($regex, $password, $match)) {
			$message = __('ragnarok', 'password-invalid-character', implode(', ', $match[0]));

			return self::FIELD_INVALID_CHARACTERS;
		}

		return self::FIELD_OK;
	}

	public function checkValidPincode($pincode, &$message)
	{
		if(!$pincode) {
			return self::FIELD_OK;
		}
		$len = strlen($pincode);
		$min = App::settings()->get('ragnarok')->get('pincode_min_len', 4);
		$max = App::settings()->get('ragnarok')->get('pincode_max_len', 4);
		if(!ctype_digit($pincode)) {
			$message = __('ragnarok', 'pincode-digit');

			return self::FIELD_INVALID_CHARACTERS;
		}
		if($min && $len < $min || $max && $len > $max) {
			$message = ($min ? __('ragnarok', 'pincode-len', $min, $max) :
				               __('ragnarok', 'pincode-len2', $max));

			return self::FIELD_INVALID_SIZE;
		}

		return self::FIELD_OK;
	}

	/**
	 * @param string $username
	 * @return bool
	 */
	public function exists($username)
	{
		$select = Query::select($this->connection())
		               ->columns(array( 'id' => 'account_id' ))
		               ->setColumnType(array( 'id' => 'integer' ))
		               ->from($this->table('login'))
		               ->where(array( 'userid' => $username ))
		               ->limit(1);
		if($this->getOption('case-sensitive-username')) {
			$select->having(array( 'BINARY userid' => $username ));
		}
		$select->query();

		return (bool)$select->get('id');
	}

	/**
	 * @param string             $username
	 * @param string             $password
	 * @param string             $email
	 * @param string             $gender
	 * @param int                $birth_date
	 * @param int                $group_id
	 * @param int                $state
	 * @param \Aqua\User\Account $user
	 * @return \Aqua\Ragnarok\Account
	 */
	public function register(
		$username,
		$password,
		$email,
		$gender,
		$birth_date = null,
		$group_id = null,
		$state = RagnarokAccount::STATE_NORMAL,
		UserAccount $user = null
	)
	{
		$query = 'INSERT INTO ' .
		         $this->table('login') . ' ( userid, user_pass, sex, email, group_id, state, birthdate, ac_user_id ';
		if((int)$this->getOption('default-slots')) {
			$query .= ', character_slots ) VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ? )';
		} else {
			$query .= ' ) VALUES ( ?, ?, ?, ?, ?, ?, ?, ? )';
		}
		if(!$birth_date) {
			$birth_date = '0000-00-00';
		} else if($birth_date instanceof \DateTime) {
			$birth_date = $birth_date->format('Y-m-d');
		} else if(is_int($birth_date)) {
			$birth_date = date('Y-m-d', $birth_date);
		}
		if(!$group_id || !is_int($group_id) || $group_id < 0) {
			$group_id = $this->getOption('default-group-id');
		}
		$user_id = ($user === null ? 0 : $user->id);
		$sth     = $this->connection()->prepare($query);
		$sth->bindValue(1, $username, \PDO::PARAM_STR);
		$sth->bindValue(2, ($this->getOption('use-md5') ? md5($password) : $password), \PDO::PARAM_STR);
		$sth->bindValue(3, $gender, \PDO::PARAM_INT);
		$sth->bindValue(4, $email, \PDO::PARAM_STR);
		$sth->bindValue(5, $group_id, \PDO::PARAM_INT);
		$sth->bindValue(6, $state, \PDO::PARAM_INT);
		$sth->bindValue(7, $birth_date, \PDO::PARAM_STR);
		$sth->bindValue(8, $user_id, \PDO::PARAM_INT);
		$slots = (int)$this->getOption('default-slots');
		if($slots) {
			$sth->bindValue(9, $slots, \PDO::PARAM_INT);
		} else {
			$slots = 0;
		}
		$sth->execute();
		$account                      = new RagnarokAccount;
		$account->id                  = (int)$this->connection()->lastInsertId();
		$account->server              = & $this->server;
		$account->owner               = $user_id;
		$account->username            = $username;
		$account->email               = $email;
		$account->state               = (int)$state;
		$account->groupId             = (int)$group_id;
		$account->slots               = $slots;
		$account->gender              = (int)$gender;
		$this->accounts[$account->id] = $account;
		$feedback                     = array( $this, $account, $password );
		Event::fire('ragnarok.register', $feedback);
		$this->cache !== null or $this->fetchCache(null, false, true);
		if(!empty($this->cache)) {
			$this->cache['count']++;
			array_unshift($this->cache['last_registered'], array(
				'id'           => $account->id,
				'username'     => $account->username,
				'owner'        => $account->owner,
				'role_id'      => $account->user()->roleId,
				'display_name' => $account->user()->displayName
			));
			if(count($this->cache['last_registered']) > self::CACHE_RECENT_ACCOUNTS) {
				$this->cache['last_registered'] = array_slice(
					$this->cache['last_registered'],
					0,
					self::CACHE_RECENT_ACCOUNTS,
					false
				);
			}
			App::cache()->store("ro.{$this->server->key}.cache", $this->cache, self::CACHE_TTL);
		}

		return $account;
	}

	/**
	 * @param int $account_id
	 */
	public function deleteAccount($account_id)
	{
		$sth = $this->connection()->prepare("DELETE FROM {$this->table('login')} WHERE account_id = ?");
		$sth->bindValue(1, $account_id, \PDO::PARAM_INT);
	}

	/**
	 * @param int $id
	 * @return int
	 */
	public function countAccounts($id)
	{
		$sth = $this->connection()->prepare("
		SELECT COUNT(1)
		FROM {$this->table('login')}
		WHERE ac_user_id = ?
		");
		$sth->bindValue(1, $id, \PDO::PARAM_INT);
		$sth->execute();

		return (int)$sth->fetch(\PDO::FETCH_COLUMN, 0);
	}

	/**
	 * @return bool
	 */
	public function serverStatus()
	{
		if(($status = App::cache()->fetch("ro.{$this->server->key}.status", null)) !== null) {
			return $status;
		}
		$status = ac_server_status($this->getOption('host'),
		                           (int)$this->getOption('port'),
		                           (int)$this->getOption('status-timeout'));
		App::cache()->store("ro.{$this->server->key}.status", $status, (int)$this->getOption('status-cache'));

		return $status;
	}

	/**
	 * @return \PDO
	 */
	public function connection()
	{
		if(!$this->dbh) {
			$this->dbh = ac_mysql_connection($this->dbSettings);
		}

		return $this->dbh;
	}

	/**
	 * @param string $table
	 * @return string
	 */
	public function table($table)
	{
		$tbl = isset($this->tables[$table]) ? $this->tables[$table] : $table;

		return ($this->db ? "`{$this->db}`.`{$tbl}`" : "`{$tbl}`");
	}

	/**
	 * @param bool $rebuild
	 */
	public function fetchSettings($rebuild = false)
	{
		$this->settings !== null or ($this->settings = App::cache()->fetch("ro.{$this->server->key}.settings", false));
		if($rebuild || !$this->settings) {
			$this->settings = array();
			$sth            = $this->connection()->query("
			SELECT `key`, val
			FROM {$this->table('ac_login_settings')}
			");
			while($data = $sth->fetch(\PDO::FETCH_NUM)) {
				$this->settings[$data[0]] = $data[1];
			}
			App::cache()->store("ro.{$this->server->key}.settings", $this->settings);
		}
	}

	/**
	 * @param bool $rebuild
	 */
	public function fetchGroups($rebuild = false)
	{
		$this->groups !== null or ($this->groups = App::cache()->fetch("ro.{$this->server->key}.groups", false));
		if($rebuild || !$this->groups) {
			$this->groups = array();
			$sth          = $this->connection()->query("
			SELECT id, `name`
			FROM {$this->table('ac_groups')}
			ORDER BY id
			");
			while($data = $sth->fetch(\PDO::FETCH_NUM)) {
				$this->groups[$data[0]] = $data[1];
			}
			App::cache()->store("ro.{$this->server->key}.groups", $this->groups);
		}
	}

	/**
	 * @param string|null $name
	 * @param bool $rebuild
	 * @return mixed
	 */
	public function fetchCache($name = null, $rebuild = false)
	{
		$this->cache !== null or ($this->cache = App::cache()->fetch("ro.{$this->server->key}.cache", false));
		if($rebuild || !$this->cache) {
			$this->cache                    = array();
			$this->cache['count']           = (int)$this->connection()->query("
			SELECT COUNT(1) FROM {$this->table('login')} WHERE sex != 'S'
			")->fetch(\PDO::FETCH_COLUMN, 0);
			$search = $this->search()
				->where(array( 'sex' => array( Search::SEARCH_DIFFERENT, 'S' ) ))
				->order(array( 'id' => 'DESC' ))
				->limit(self::CACHE_RECENT_ACCOUNTS)
				->query();
			$this->cache['last_registered'] = array();
			foreach($search as $account) {
				$cache = array(
					'id'           => $account->id,
					'username'     => $account->username,
					'owner'        => $account->owner,
					'role_id'      => null,
					'display_name' => null
				);
				if($account->owner) {
					$cache['role_id']      = $account->user()->roleId;
					$cache['display_name'] = $account->user()->displayName;
				}
				$this->cache['last_registered'][] = $cache;
			}
			App::cache()->store("ro.{$this->server->key}.cache", $this->cache, self::CACHE_TTL);
		}
		if($name === null) {
			return $this->cache;
		} else if(array_key_exists($name, $this->cache)) {
			return $this->cache[$name];
		} else {
			return null;
		}
	}

	/**
	 * @param string|null $name
	 */
	public function flushCache($name = null)
	{
		if(!$name || $name === 'settings') App::cache()->delete("ro.{$this->server->key}.settings");
		if(!$name || $name === 'groups') App::cache()->delete("ro.{$this->server->key}.groups");
		if(!$name || $name === 'cache') App::cache()->delete("ro.{$this->server->key}.cache");
	}

	/**
	 * @param $data
	 * @return \Aqua\Ragnarok\Account
	 */
	public function parseAccountSql($data)
	{
		if(isset($this->accounts[$data['id']])) {
			$acc = $this->accounts[$data['id']];
		} else {
			$acc = new RagnarokAccount;
		}
		$acc->server     = & $this->server;
		$acc->id         = (int)$data['id'];
		$acc->owner      = (int)$data['user_id'];
		$acc->options    = (int)$data['cp_options'];
		$acc->gender     = (int)$data['sex'];
		$acc->groupId    = (int)$data['group_id'];
		$acc->unbanTime  = (int)$data['unban_time'];
		$acc->lastLogin  = (int)$data['last_login'];
		$acc->loginCount = (int)$data['login_count'];
		$acc->birthday   = (int)$data['birthday'];
		$acc->state      = ($acc->unbanTime > time() ? RagnarokAccount::STATE_BANNED_TEMPORARILY : (int)$data['state']);
		$acc->username   = $data['username'];
		$acc->email      = $data['email'];
		$acc->password   = $data['password'];
		$acc->lastIp     = $data['last_ip_address'];
		if((int)$this->getOption('default-slots')) {
			$acc->slots = (int)$data['slots'];
		}
		if($this->getOption('use-pincode')) {
			$acc->pinCode       = $data['pincode'];
			$acc->pinCodeChange = (int)$data['pincode_change'];
		}

		return $acc;
	}
}
