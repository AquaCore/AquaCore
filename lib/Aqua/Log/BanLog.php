<?php
namespace Aqua\Log;

use Aqua\Core\App;
use Aqua\SQL\Query;
use Aqua\User\Account;

class BanLog
{
	/**
	 * @var int
	 */
	public $id;
	/**
	 * @var int
	 */
	public $type;
	/**
	 * @var int
	 */
	public $userId;
	/**
	 * @var int
	 */
	public $bannedId;
	/**
	 * @var int
	 */
	public $reason;
	/**
	 * @var int
	 */
	public $banDate;
	/**
	 * @var int
	 */
	public $unbanDate;

	const TYPE_BAN_TEMPORARILY = 1;
	const TYPE_BAN_PERMANENTLY = 2;
	const TYPE_UNBAN           = 3;

	protected function __construct() { }

	/**
	 * @return \Aqua\User\Account
	 */
	public function account()
	{
		return Account::get($this->userId);
	}

	/**
	 * @return \Aqua\User\Account
	 */
	public function bannedAccount()
	{
		return Account::get($this->bannedId);
	}

	/**
	 * @param string $format
	 * @return string
	 */
	public function banDate($format)
	{
		return strftime($format, $this->banDate);
	}

	/**
	 * @param string $format
	 * @return string
	 */
	public function unbanDate($format)
	{
		return strftime($format, $this->unbanDate);
	}

	/**
	 * @return string
	 */
	public function type()
	{
		return __('ban-type', $this->type);
	}

	/**
	 * @return \Aqua\SQL\Search
	 */
	public static function search()
	{
		return Query::search(App::connection())
			->columns(array(
				'id'             => 'b.id',
				'user_id'        => 'b._user_id',
				'banned_user_id' => 'b._banned_id',
				'type'           => '(b._type + 0)',
				'ban_date'       => 'UNIX_TIMESTAMP(b._ban_date)',
				'unban_date'     => 'UNIX_TIMESTAMP(b._unban_date)',
				'reason'         => 'b._reason'
			))
			->whereOptions(array(
				'id'             => 'b.id',
				'user_id'        => 'b._user_id',
				'banned_user_id' => 'b._banned_id',
				'type'           => 'b._type',
				'ban_date'       => 'b._ban_date',
				'unban_date'     => 'b._unban_date'
			))
			->from(ac_table('ban_log'), 'b')
			->groupBy('b.id')
			->parser(array( __CLASS__, 'parseLogSql' ));
	}

	/**
	 * @param int $id
	 * @return \Aqua\Log\BanLog|null
	 */
	public static function get($id)
	{
		$select = Query::select(App::connection())
			->columns(array(
				'id'             => 'b.id',
				'user_id'        => 'b._user_id',
				'banned_user_id' => 'b._banned_id',
				'type'           => '(b._type + 0)',
				'ban_date'       => 'UNIX_TIMESTAMP(b._ban_date)',
				'unban_date'     => 'UNIX_TIMESTAMP(b._unban_date)',
				'reason'         => 'b._reason'
			))
			->from(ac_table('ban_log'), 'b')
			->where(array( 'id' => $id ))
			->limit(1)
			->parser(array( __CLASS__, 'parseLogSql' ))
			->query();

		return ($select->valid() ? $select->current() : null);
	}

	/**
	 * @param \Aqua\User\Account $account
	 * @param \Aqua\User\Account $banned_account
	 * @param int|null           $unban_time
	 * @param int                $type
	 * @param string             $reason
	 * @return \Aqua\Log\BanLog|null
	 */
	public static function logSql(Account $account, Account $banned_account, $unban_time, $type, $reason)
	{
		$sth = App::connection()->prepare(sprintf('
		INSERT INTO %s (_user_id, _banned_id, _type, _reason, _unban_date, _ban_date)
		VALUES (:user, :banned, :type, :reason, :unban, NOW())
		', ac_table('ban_log')));
		$sth->bindValue(':user', $account->id, \PDO::PARAM_INT);
		$sth->bindValue(':banned', $banned_account->id, \PDO::PARAM_INT);
		$sth->bindValue(':type', $type, \PDO::PARAM_INT);
		$sth->bindValue(':reason', $reason, \PDO::PARAM_LOB);
		if($unban_time === null) {
			$sth->bindValue(':unban', null, \PDO::PARAM_NULL);
		} else {
			$sth->bindValue(':unban', date('Y-m-d', $unban_time), \PDO::PARAM_STR);
		}
		$sth->execute();

		return self::get(App::connection()->lastInsertId());
	}

	/**
	 * @param array $data
	 * @return \Aqua\Log\BanLog
	 */
	public static function parseLogSql(array $data)
	{
		$log            = new self;
		$log->id        = (int)$data['id'];
		$log->userId    = (int)$data['user_id'];
		$log->bannedId  = (int)$data['banned_user_id'];
		$log->type      = (int)$data['type'];
		$log->banDate   = (int)$data['ban_date'];
		$log->unbanDate = (int)$data['unban_date'];
		$log->reason    = $data['reason'];

		return $log;
	}
}
