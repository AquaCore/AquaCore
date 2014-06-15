<?php
namespace Aqua\Log;

use Aqua\Core\App;
use Aqua\SQL\Query;
use Aqua\User\Account;

class TransferLog
{
	/**
	 * @var int
	 */
	public $id;
	/**
	 * @var int
	 */
	public $senderId;
	/**
	 * @var int
	 */
	public $receiverId;
	/**
	 * @var int
	 */
	public $amount;
	/**
	 * @var int
	 */
	public $date;

	/**
	 * @var array|null
	 */
	public static $cache;

	const CACHE_KEY              = 'general_cache.transfer';
	const CACHE_TTL              = 86400;
	const CACHE_RECENT_TRANSFERS = 4;

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
	 * @return \Aqua\User\Account
	 */
	public function sender()
	{
		return Account::get($this->senderId);
	}

	/**
	 * @return \Aqua\User\Account
	 */
	public function receiver()
	{
		return Account::get($this->receiverId);
	}

	/**
	 * @return \Aqua\SQL\Search
	 */
	public static function search()
	{
		return Query::search(App::connection())
			->columns(array(
				'id'       => 'tl.id',
				'sender'   => 'tl._sender_id',
				'receiver' => 'tl._receiver_id',
				'amount'   => 'tl._amount',
				'date'     => 'UNIX_TIMESTAMP(tl._date)'
			))
			->whereOptions(array(
				'id'       => 'tl.id',
				'sender'   => 'tl._sender_id',
				'receiver' => 'tl._receiver_id',
				'amount'   => 'tl._amount',
				'date'     => 'tl._date'
			))
			->from(ac_table('transfer_log'), 'tl')
			->groupBy('tl.id')
			->parser(array( __CLASS__, 'parseTransferSql' ));
	}

	/**
	 * @param int $id
	 * @return \Aqua\Log\TransferLog|null
	 */
	public static function get($id)
	{
		$select = Query::select(App::connection())
			->columns(array(
				'id'       => 'tl.id',
				'sender'   => 'tl._sender_id',
				'receiver' => 'tl._receiver_id',
				'amount'   => 'tl._amount',
				'date'     => 'UNIX_TIMESTAMP(tl._date)'
			))
			->where(array( 'tl.id' => $id ))
			->from(ac_table('transfer_log'), 'tl')
			->limit(1)
			->parser(array( __CLASS__, 'parseTransferSql' ))
			->query();

		return ($select->valid() ? $select->current() : null);
	}

	/**
	 * @param \Aqua\User\Account $sender
	 * @param \Aqua\User\Account $receiver
	 * @param  int               $amount
	 * @return \Aqua\Log\TransferLog
	 */
	public static function logSql(Account $sender, Account $receiver, $amount)
	{
		$transfer             = new self;
		$transfer->senderId   = $sender->id;
		$transfer->receiverId = $receiver->id;
		$transfer->amount     = $amount;
		$transfer->date       = time();
		$sth                  = App::connection()->prepare(sprintf('
		INSERT INTO %s (_sender_id, _receiver_id, _amount, _date)
		VALUES (:sender, :receiver, :amount, NOW())
		', ac_table('transfer_log')));
		$sth->bindValue(':sender', $sender->id, \PDO::PARAM_INT);
		$sth->bindValue(':receiver', $receiver->id, \PDO::PARAM_INT);
		$sth->bindValue(':amount', $amount, \PDO::PARAM_INT);
		$sth->execute();
		$transfer->id = (int)App::connection()->lastInsertId();
		self::$cache !== null or self::fetchCache(null, true);
		if(!empty(self::$cache) && array_key_exists('last_transfer', self::$cache)) {
			array_unshift(self::$cache['last_transfer'], array(
					'id'                    => $transfer->id,
					'sender'                => $transfer->senderId,
					'receiver'              => $transfer->receiverId,
					'amount'                => $transfer->amount,
					'date'                  => $transfer->date,
					'sender_display_name'   => $transfer->sender()->displayName,
					'sender_role_id'        => $transfer->sender()->roleId,
					'receiver_display_name' => $transfer->receiver()->displayName,
					'receiver_role_id'      => $transfer->receiver()->roleId
				));
			if(count(self::$cache['last_transfer']) > self::CACHE_RECENT_TRANSFERS) {
				self::$cache['last_transfer'] = array_slice(
					self::$cache['last_transfer'],
					0,
					self::CACHE_RECENT_TRANSFERS,
					false
				);
			}
			App::cache()->store(self::CACHE_KEY, self::$cache, self::CACHE_TTL);
		}

		return $transfer;
	}

	/**
	 * @param string|null $name
	 * @param bool        $internal
	 * @return mixed
	 */
	public static function fetchCache($name = null, $internal = false)
	{
		self::$cache !== null or (self::$cache = App::cache()->fetch(self::CACHE_KEY, array()));
		if($internal) {
			return null;
		}
		if(empty(self::$cache)) {
			self::rebuildCache();
		}
		if($name === null) {
			return self::$cache;
		} else if(isset(self::$cache[$name])) {
			return self::$cache[$name];
		} else {
			return null;
		}
	}

	/**
	 * @param string|null $name
	 */
	public static function rebuildCache($name = null)
	{
		if(!$name || $name === 'last_transfer') {
			self::$cache['last_transfer'] = Query::select(App::connection())
				->columns(array(
					'id'                    => 'tl.id',
					'sender'                => 'tl._sender_id',
					'receiver'              => 'tl._receiver_id',
					'amount'                => 'tl._amount',
					'date'                  => 'UNIX_TIMESTAMP(tl._date)',
					'sender_display_name'   => 'su._display_name',
					'sender_role_id'        => 'su._role_id',
					'receiver_display_name' => 'ru._display_name',
					'receiver_role_id'      => 'ru._role_id',
				))
				->setColumnType(array(
					'id'               => 'integer',
					'sender'           => 'integer',
					'receiver'         => 'integer',
					'sender_role_id'   => 'integer',
					'receiver_role_id' => 'integer',
					'date'             => 'timestamp',
				))
				->from(ac_table('transfer_log'), 'tl')
				->innerJoin(ac_table('users'), 'su.id = tl._sender_id', 'su')
				->innerJoin(ac_table('users'), 'ru.id = tl._receiver_id', 'ru')
				->order(array( 'tl._date' => 'DESC' ))
				->limit(self::CACHE_RECENT_TRANSFERS)
				->query()
				->results;
		}
		App::cache()->store(self::CACHE_KEY, self::$cache, self::CACHE_TTL);
	}

	/**
	 * @param array $data
	 * @return \Aqua\Log\TransferLog
	 */
	public static function parseTransferSql(array $data)
	{
		$log             = new self;
		$log->id         = (int)$data['id'];
		$log->amount     = (int)$data['amount'];
		$log->senderId   = (int)$data['sender'];
		$log->receiverId = (int)$data['receiver'];
		$log->date       = (int)$data['date'];

		return $log;
	}
}
