<?php
namespace Aqua\Ragnarok\Server;

use Aqua\Core\App;
use Aqua\Ragnarok\Account as RagnarokAccount;
use Aqua\Ragnarok\Server\Logs;
use Aqua\SQL\Query;
use Aqua\User\Account as UserAccount;

class LoginLog
{
	/**
	 * @var \Aqua\Ragnarok\Server\Login
	 */
	public $login;
	/**
	 * @var \PDO
	 */
	public $dbh;
	/**
	 * @var array
	 */
	public $dbSettings;
	/**
	 * @var string
	 */
	public $db;
	/**
	 * @var array
	 */
	public $tables = array();
	/**
	 * @var array
	 */
	public $cache;

	const CACHE_COUNT = 5;

	/**
	 * @param Login  $login
	 * @param string $database
	 * @param array  $db_options
	 * @param array  $tables
	 */
	public function __construct(Login $login, $database, array $db_options, array $tables)
	{
		$this->login      = $login;
		$this->db         = $database;
		$this->dbSettings = $db_options;
		$this->tables     = $tables;
	}

	/**
	 * @param \Aqua\Ragnarok\Account $acc
	 * @param \Aqua\User\Account     $user
	 * @param int                    $time
	 * @param string                 $reason
	 */
	public function logBan(RagnarokAccount $acc, UserAccount $user, $time, $reason)
	{
		$sth = $this->connection()->prepare("
		INSERT INTO {$this->table('ac_ban_log')} (user_id, banned_account, type, unban_date, `date`, reason)
		VALUES (:user, :acc, :type, :time, NOW(), :reason)
		");
		$sth->bindValue(':user', $user->id, \PDO::PARAM_INT);
		$sth->bindValue(':acc', $acc->id, \PDO::PARAM_INT);
		if($time) {
			$sth->bindValue(':time', date('Y-m-d H:i:s', $time), \PDO::PARAM_INT);
			$sth->bindValue(':type', 'temporary', \PDO::PARAM_STR);
		} else {
			$sth->bindValue(':time', null, \PDO::PARAM_NULL);
			$sth->bindValue(':type', 'permanent', \PDO::PARAM_STR);
		}
		$sth->bindValue(':reason', $reason, \PDO::PARAM_LOB);
		$sth->execute();
	}

	/**
	 * @param \Aqua\Ragnarok\Account $acc
	 * @param \Aqua\User\Account     $user
	 * @param string                 $reason
	 */
	public function logUnban(RagnarokAccount $acc, UserAccount $user, $reason)
	{
		$sth = $this->connection()->prepare("
		INSERT INTO {$this->table('ac_ban_log')} (user_id, banned_account, type, unban_time, `date`, reason)
		VALUES (:user, :acc, :type, NULL, NOW(), :reason)
		");
		$sth->bindValue(':user', $user->id, \PDO::PARAM_INT);
		$sth->bindValue(':acc', $acc->id, \PDO::PARAM_INT);
		$sth->bindValue(':type', 'unban', \PDO::PARAM_STR);
		$sth->bindValue(':reason', $reason, \PDO::PARAM_LOB);
		$sth->execute();
		$this->fetchCache(null, true);
	}

	/**
	 * @param \Aqua\Ragnarok\Account $acc
	 * @param string                 $key
	 */
	public function logPasswordResetRequest(RagnarokAccount $acc, $key)
	{
		$sth = $this->connection()->prepare("
		INSERT INTO {$this->table('ac_password_reset_log')} (account_id, ip_address, reset_key, request_date, reset_date)
		VALUES (:account, :ip, :key, NOW(), NULL)
		");
		$sth->bindValue(':account', $acc->id, \PDO::PARAM_INT);
		$sth->bindValue(':ip', App::request()->ipString, \PDO::PARAM_STR);
		$sth->bindValue(':key', $key, \PDO::PARAM_STR);
		$sth->execute();
		$this->fetchCache(null, true);
	}

	/**
	 * @param \Aqua\Ragnarok\Account $acc
	 * @param  string                $key
	 * @return bool
	 */
	public function logPasswordReset(RagnarokAccount $acc, $key)
	{
		$sth = $this->connection()->prepare("
		UPDATE {$this->table('ac_password_reset_log')}
		SET reset_date = NOW()
		WHERE account_id = ? AND reset_key = ?
		LIMIT 1
		");
		$sth->bindValue(1, $acc->id, \PDO::PARAM_INT);
		$sth->bindValue(2, $key, \PDO::PARAM_STR);
		$sth->execute();
		if(!$sth->rowCount()) {
			return false;
		}
		$this->fetchCache(null, true);

		return true;
	}

	/**
	 * @return \Aqua\SQL\Search
	 */
	public function searchLogin()
	{
		return Query::search($this->connection())
			->columns(array(
				'ip_address' => 'll.`ip`',
				'username'   => 'll.`user`',
				'code'       => 'll.`rcode`',
				'message'    => 'll.`log`',
				'date'       => 'UNIX_TIMESTAMP(ll.`time`)'
			))
			->whereOptions(array(
				'ip_address' => 'll.`ip`',
				'username'   => 'll.`user`',
				'code'       => 'll.`rcode`',
				'message'    => 'll.`log`',
				'date'       => 'll.`time`'
			))
			->from($this->table('loginlog'), 'll')
			->parser(array( $this, 'parseLoginLogSql' ));
	}

	/**
	 * @return \Aqua\SQL\Search
	 */
	public function searchBan()
	{
		return Query::search($this->connection())
			->columns(array(
				'id'         => 'bl.`id`',
				'account_id' => 'bl.`user_id`',
				'banned_id'  => 'bl.`banned_account`',
				'type'       => '(bl.`type` + 0)',
				'ban_date'   => 'UNIX_TIMESTAMP(bl.`date`)',
				'unban_date' => 'UNIX_TIMESTAMP(bl.`unban_date`)',
			    'reason'     => 'bl.reason'
			))
			->whereOptions(array(
				'id'         => 'bl.`id`',
				'account_id' => 'bl.`user_id`',
				'banned_id'  => 'bl.`banned_account`',
				'type'       => 'bl.`type`',
				'ban_date'   => 'bl.`date`',
				'unban_date' => 'bl.`unban_date`',
			))
			->from($this->table('ac_ban_log'), 'bl')
			->groupBy('bl.id')
			->parser(array( $this, 'parseBanLogSql' ));
	}

	/**
	 * @return \Aqua\SQL\Search
	 */
	public function searchPasswordReset()
	{
		return Query::search($this->connection())
			->columns(array(
				'id'           => 'pw.id',
				'account_id'   => 'pw.account_id',
				'ip_address'   => 'pw.ip_address',
				'key'          => 'pw.reset_key',
				'request_date' => 'UNIX_TIMESTAMP(pw.request_date)',
				'reset_date'   => 'UNIX_TIMESTAMP(pw.reset_date)',
			))
			->whereOptions(array(
				'id'           => 'pw.id',
				'account_id'   => 'pw.account_id',
				'ip_address'   => 'pw.ip_address',
				'key'          => 'pw.reset_key',
				'request_date' => 'pw.request_date',
				'reset_date'   => 'pw.reset_date',
			))
			->from($this->table('ac_password_reset_log'), 'pw')
			->groupBy('pw.id')
			->parser(array( $this, 'parsePasswordResetLogSql' ));
	}

	public function fetchCache($name = null, $rebuild = false, $internal = false)
	{
		if($rebuild || (!$this->cache && !($this->cache = $this->cache = App::cache()->fetch("ro.{$this->login->server->key}.log-cache")))) {
			if($internal) {
				return false;
			}
			$this->cache = array();
			$sth         = $this->connection()->prepare("
			SELECT account_id,
			       ip_address,
			       UNIX_TIMESTAMP(request_date) AS request_date,
			       UNIX_TIMESTAMP(reset_date) AS reset_date
			FROM {$this->table('ac_password_reset_log')}
			ORDER BY COALESCE(reset_date, request_date) DESC
			LIMIT :limit
			");
			$sth->bindValue(':limit', self::CACHE_COUNT, \PDO::PARAM_INT);
			$sth->execute();
			$this->cache['last_reset'] = array();
			while($data = $sth->fetch(\PDO::FETCH_ASSOC)) {
				$data['account_id']          = (int)$data['account_id'];
				$data['request_date']        = (int)$data['request_date'];
				$data['reset_date']          = (int)$data['reset_date'];
				$acc                         = $this->login->get($data['account_id']);
				$data['username']            = $acc->username;
				$this->cache['last_reset'][] = $data;
			}
			$sth = $this->connection()->prepare("
			SELECT user_id,
			       banned_account,
			       `type`,
			       UNIX_TIMESTAMP(`date`) AS `ban_date`,
			       UNIX_TIMESTAMP(`unban_date`) AS `unban_date`,
			       reason
			FROM {$this->table('ac_ban_log')}
			ORDER BY ban_date DESC
			LIMIT :limit
			");
			$sth->bindValue(':limit', self::CACHE_COUNT, \PDO::PARAM_INT);
			$sth->execute();
			$this->cache['last_ban'] = array();
			while($data = $sth->fetch(\PDO::FETCH_ASSOC)) {
				$data['user_id']           = (int)$data['user_id'];
				$data['banned_account']    = (int)$data['banned_account'];
				$data['ban_date']          = (int)$data['ban_date'];
				$data['unban_date']        = (int)$data['unban_date'];
				$acc                       = $this->login->get($data['account_id']);
				$user                      = UserAccount::get($data['user_id']);
				$data['role_id']           = $user->roleId;
				$data['display_name']      = $user->displayName;
				$data['username']          = $acc->username;
				$this->cache['last_ban'][] = $data;
			}
			App::cache()->store("ro.{$this->login->server->key}.log-cache", $this->cache);
		}
		if(empty($name)) {
			return $this->cache;
		} else if(array_key_exists($name, $this->cache)) {
			return $this->cache[$name];
		} else {
			return null;
		}
	}

	public function flushCache()
	{
		App::cache()->delete("ro.{$this->login->server->key}.log-cache");
	}

	/**
	 * @returns \PDO
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
		$tbl = (isset($this->tables[$table]) ? $this->tables[$table] : $table);

		return $this->db ? "`{$this->db}`.`$tbl`" : "`$tbl`";
	}

	public function parseLoginLogSql(array $data)
	{
		$log            = new Logs\LoginLog;
		$log->login     = & $this->login;
		$log->username  = $data['username'];
		$log->date      = (int)$data['date'];
		$log->code      = (int)$data['code'];
		$log->message   = $data['message'];
		$log->ipAddress = $data['ip_address'];

		return $log;
	}

	public function parseBanLogSql(array $data)
	{
		$log            = new Logs\BanLog();
		$log->login     = & $this->login;
		$log->id        = (int)$data['id'];
		$log->accountId = (int)$data['account_id'];
		$log->bannedId  = (int)$data['banned_id'];
		$log->date      = (int)$data['ban_date'];
		$log->type      = (int)$data['type'];
		$log->reason    = $data['reason'];
		if(!$data['unban_date']) {
			$log->unbanDate = null;
		} else {
			$log->unbanDate = (int)$data['unban_date'];
		}

		return $log;
	}

	public function parsePasswordResetLogSql(array $data)
	{
		$log              = new Logs\PasswordResetLog();
		$log->login       = & $this->login;
		$log->id          = (int)$data['id'];
		$log->accountId   = (int)$data['account_id'];
		$log->requestDate = (int)$data['request_date'];
		$log->ipAddress   = $data['ip_address'];
		$log->key         = $data['key'];
		if(!$data['reset_date']) {
			$log->resetDate = null;
		} else {
			$log->resetDate = (int)$data['reset_date'];
		}

		return $log;
	}
}
