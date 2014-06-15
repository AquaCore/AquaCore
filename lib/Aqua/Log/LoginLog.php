<?php
namespace Aqua\Log;

use Aqua\Core\App;
use Aqua\SQL\Query;
use Aqua\SQL\Search;
use Aqua\User\Account;

class LoginLog
{
	/**
	 * @var string
	 */
	public $username;
	/**
	 * @var string
	 */
	public $ipAddress;
	/**
	 * @var int
	 */
	public $userId;
	/**
	 * @var int
	 */
	public $loginType;
	/**
	 * @var int
	 */
	public $status;
	/**
	 * @var int
	 */
	public $date;

	const TYPE_NORMAL     = 0;
	const TYPE_PERSISTENT = 1;

	const STATUS_OK                  = 0;
	const STATUS_INVALID_CREDENTIALS = 1;
	const STATUS_ACCESS_DENIED       = 2;

	protected function __construct() { }

	/**
	 * @param string $format
	 * @return string
	 */
	public function date($format)
	{
		return strftime($format, $this->date);
	}

	/**
	 * @return string
	 */
	public function loginType()
	{
		return __('login-type', $this->loginType);
	}

	/**
	 * @return string
	 */
	public function status()
	{
		return __('login-status', $this->status);
	}

	/**
	 * @return \Aqua\User\Account
	 */
	public function account()
	{
		return Account::get($this->userId);
	}

	/**
	 * @return \Aqua\SQL\Search
	 */
	public static function search()
	{
		return Query::search(App::connection())
			->columns(array(
				'ip_address' => 'll._ip_address',
				'username'   => 'll._username',
				'user_id'    => 'll._user_id',
				'date'       => 'UNIX_TIMESTAMP(ll._date)',
				'type'       => 'll._type',
				'status'     => 'll._status',
			))
			->whereOptions(array(
				'ip_address' => 'll._ip_address',
				'username'   => 'll._username',
				'user_id'    => 'll._user_id',
				'date'       => 'll._date',
				'type'       => 'll._type',
				'status'     => 'll._status',
			))
			->from(ac_table('login_log'), 'll')
			->parser(array( __CLASS__, 'parseLoginSql' ));
	}

	/**
	 * @param string $username
	 * @param int    $user_id
	 * @param int    $type
	 * @param int    $status
	 * @return \Aqua\Log\LoginLog
	 */
	public static function logSql($username, $user_id, $type, $status)
	{
		$sth = App::connection()->prepare(sprintf('
		INSERT INTO %s (_ip_address, _username, _user_id, _type, _status, _date)
		VALUES (:ip, :username, :id, :type, :status, NOW())
		', ac_table('login_log')));
		$sth->bindValue(':ip', App::request()->ipString, \PDO::PARAM_STR);
		$sth->bindValue(':type', $type, \PDO::PARAM_INT);
		$sth->bindValue(':status', $status, \PDO::PARAM_INT);
		if($user_id !== null) {
			$sth->bindValue(':id', $user_id, \PDO::PARAM_INT);
		} else {
			$sth->bindValue(':id', null, \PDO::PARAM_NULL);
		}
		if($username !== null) {
			$sth->bindValue(':username', $username, \PDO::PARAM_INT);
		} else {
			$sth->bindValue(':username', null, \PDO::PARAM_NULL);
		}
		$sth->execute();
		$log            = new self;
		$log->username  = $username;
		$log->userId    = $user_id;
		$log->loginType = $type;
		$log->status    = $status;
		$log->date      = time();

		return $log;
	}

	/**
	 * @param int    $id
	 * @param int    $interval
	 * @param string $type
	 * @return int
	 */
	public static function attempts($id, $interval, $type)
	{
		if($type !== 'ip_address' && $type !== 'username') {
			return 0;
		}
		return Query::select(App::connection())
			->columns(array( 'count' => 'COUNT(1)' ))
			->setColumnType(array( 'count' => 'integer' ))
			->from(ac_table('login_log'))
			->where(array(
				"_{$type}" => $id,
				'_status'  => array( Search::SEARCH_DIFFERENT, self::STATUS_OK ),
				'_date'    => array(
					Search::SEARCH_LOWER | Search::SEARCH_DIFFERENT,
					date('Y-m-d H:i:s', time() - ( $interval * 60 ))
			)))
			->query()
			->get('count', 0);
	}

	/**
	 * @param array $data
	 * @return \Aqua\Log\LoginLog
	 */
	public static function parseLoginSql(array $data)
	{
		$log            = new self;
		$log->date      = (int)$data['date'];
		$log->userId    = (int)$data['user_id'];
		$log->loginType = (int)$data['type'];
		$log->status    = (int)$data['status'];
		$log->ipAddress = $data['ip_address'];
		$log->username  = $data['username'];

		return $log;
	}
}
