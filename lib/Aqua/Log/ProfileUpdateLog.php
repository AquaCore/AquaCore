<?php
namespace Aqua\Log;

use Aqua\Core\App;
use Aqua\SQL\Query;
use Aqua\User\Account;

class ProfileUpdateLog
{
	/**
	 * @var int
	 */
	public $id;
	/**
	 * @var int
	 */
	public $userId;
	/**
	 * @var string
	 */
	public $ipAddress;
	/**
	 * @var int
	 */
	public $field;
	/**
	 * @var string
	 */
	public $oldValue;
	/**
	 * @var string
	 */
	public $newValue;
	/**
	 * @var int
	 */
	public $type;
	/**
	 * @var int
	 */
	public $date;

	const FIELD_DISPLAY_NAME = 1;
	const FIELD_EMAIL        = 2;
	const FIELD_PASSWORD     = 3;
	const FIELD_BIRTHDAY     = 4;

	protected function __construct() { }

	/**
	 * @return \Aqua\User\Account
	 */
	public function account()
	{
		return Account::get($this->userId);
	}

	/**
	 * @return string
	 */
	public function field()
	{
		switch($this->field) {
			case self::FIELD_DISPLAY_NAME:
				return __('profile', 'display-name');
			case self::FIELD_EMAIL:
				return __('profile', 'email');
			case self::FIELD_PASSWORD:
				return __('profile', 'password');
			case self::FIELD_BIRTHDAY:
				return __('profile', 'birthday');
			default:
				return '';
		}
	}

	/**
	 * @param string $format
	 * @return string
	 */
	public function date($format)
	{
		return strftime($format, $this->date);
	}

	/**
	 * @return \Aqua\SQL\Search
	 */
	public static function search()
	{
		return Query::search(App::connection())
			->columns(array(
				'id'         => 'pu.id',
				'ip_address' => 'pu._ip_address',
				'field'      => '(pu._field + 0)',
				'user_id'    => 'pu._user_id',
				'date'       => 'UNIX_TIMESTAMP(pu._date)',
				'old_value'  => 'pu._old_value',
				'new_value'  => 'pu._new_value',
			))
			->whereOptions(array(
				'id'         => 'pu.id',
				'ip_address' => 'pu._ip_address',
				'field'      => 'pu._field',
				'user_id'    => 'pu._user_id',
				'date'       => 'pu._date',
				'old_value'  => 'pu._old_value',
				'new_value'  => 'pu._new_value',
			))
			->from(ac_table('profile_update_log'), 'pu')
			->groupBy('pu.id')
			->parser(array( __CLASS__, 'parseLogSql' ));
	}

	/**
	 * @param int $id
	 * @return \Aqua\Log\ProfileUpdateLog|null
	 */
	public static function get($id)
	{
		$select = Query::select(App::connection())
			->columns(array(
				'id'         => 'pu.id',
				'ip_address' => 'pu._ip_address',
				'field'      => '(pu._field + 0)',
				'user_id'    => 'pu._user_id',
				'date'       => 'UNIX_TIMESTAMP(pu._date)',
				'old_value'  => 'pu._old_value',
				'new_value'  => 'pu._new_value',
			))
			->where(array( 'pu.id' => $id ))
			->from(ac_table('profile_update_log'), 'pu')
			->limit(1)
			->parser(array( __CLASS__, 'parseLogSql' ))
			->query();

		return ($select->valid() ? $select->current() : null);
	}

	/**
	 * @param \Aqua\User\Account $account
	 * @param string             $field
	 * @param string             $value
	 * @param string             $old
	 * @return \Aqua\Log\ProfileUpdateLog|null
	 */
	public static function logSql(Account $account, $field, $value, $old)
	{
		$tbl = ac_table('profile_update_log');
		$sth = App::connection()->prepare("
		INSERT INTO `$tbl` (_user_id, _ip_address, _field, _old_value, _new_value, _date)
		VALUES (:id, :ip, :field, :old, :new, NOW())
		");
		$sth->bindValue(':id', $account->id, \PDO::PARAM_INT);
		$sth->bindValue(':ip', App::request()->ipString, \PDO::PARAM_STR);
		$sth->bindValue(':field', $field, \PDO::PARAM_STR);
		$sth->bindValue(':old', $old, \PDO::PARAM_STR);
		$sth->bindValue(':new', $value, \PDO::PARAM_STR);
		$sth->execute();

		return self::get(App::connection()->lastInsertId());
	}

	/**
	 * @param array $data
	 * @return \Aqua\Log\ProfileUpdateLog
	 */
	public static function parseLogSql(array $data)
	{
		$log            = new self;
		$log->id        = (int)$data['id'];
		$log->userId    = (int)$data['user_id'];
		$log->date      = (int)$data['date'];
		$log->field     = (int)$data['field'];
		$log->ipAddress = $data['ip_address'];
		$log->oldValue  = $data['old_value'];
		$log->newValue  = $data['new_value'];

		return $log;
	}
}
