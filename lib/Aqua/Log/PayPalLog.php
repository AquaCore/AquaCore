<?php
namespace Aqua\Log;

use Aqua\Core\App;
use Aqua\Http\Request;
use Aqua\SQL\Query;
use Aqua\SQL\Search;
use Aqua\User\Account;
use Aqua\User\Role;

class PayPalLog
{
	/**
	 * @var int
	 */
	public $id;
	/**
	 * @var bool
	 */
	public $sandbox;
	/**
	 * @var int
	 */
	public $processDate;
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
	public $credits;
	/**
	 * @var float
	 */
	public $creditExchangeRate;
	/**
	 * @var string
	 */
	public $itemName;
	/**
	 * @var int
	 */
	public $itemNumber;
	/**
	 * @var int
	 */
	public $quantity;
	/**
	 * @var float
	 */
	public $deposited;
	/**
	 * @var float
	 */
	public $gross;
	/**
	 * @var float
	 */
	public $fee;
	/**
	 * @var string
	 */
	public $currency;
	/**
	 * @var int
	 */
	public $paymentDate;
	/**
	 * @var string
	 */
	public $paymentType;
	/**
	 * @var string
	 */
	public $transactionType;
	/**
	 * @var string
	 */
	public $transactionId;
	/**
	 * @var string
	 */
	public $parentTransactionId;
	/**
	 * @var string
	 */
	public $payerStatus;
	/**
	 * @var string
	 */
	public $payerId;
	/**
	 * @var string
	 */
	public $payerEmail;
	/**
	 * @var string
	 */
	public $firstName;
	/**
	 * @var string
	 */
	public $lastName;
	/**
	 * @var string
	 */
	public $receiverId;
	/**
	 * @var string
	 */
	public $receiverEmail;
	/**
	 * @var string
	 */
	public $requestData;

	/**
	 * @var array|null
	 */
	public static $cache;

	const CACHE_KEY              = 'general_cache.donation';
	const CACHE_TTL              = 86400;
	const CACHE_RECENT_DONATIONS = 3;

	protected function __construct() { }

	/**
	 * @return \Aqua\User\Account|null
	 */
	public function account()
	{
		return ($this->userId ? Account::get($this->userId) : null);
	}

	/**
	 * @return \Aqua\UI\Tag
	 */
	public function display()
	{
		if(!$this->userId) {
			$role = Role::get(Role::ROLE_GUEST);

			return $role->display($role->name, 'ac-guest');
		} else {
			return $this->account()->display();
		}
	}

	/**
	 * @param string $format
	 * @return string
	 */
	public function processDate($format)
	{
		return strftime($format, $this->processDate);
	}

	/**
	 * @param string $format
	 * @return string
	 */
	public function paymentDate($format)
	{
		return strftime($format, $this->paymentDate);
	}

	/**
	 * @return string
	 */
	public function transactionType()
	{
		return __('pp-transaction-type', $this->transactionType);
	}

	/**
	 * @return string
	 */
	public function paymentType()
	{
		return __('pp-payment-type', $this->paymentType);
	}

	/**
	 * @return \Aqua\Log\PayPalLog|null
	 */
	public function parentTransaction()
	{
		if(!$this->parentTransactionId || !($txn = self::get($this->parentTransactionId, 'txn_id'))) {
			return null;
		} else {
			return $txn;
		}
	}

	/**
	 * @return \Aqua\SQL\Search
	 */
	public static function search()
	{
		return Query::search(App::connection())
			->columns(array(
				'id'                   => 'pp.id',
				'sandbox'              => 'pp._sandbox',
				'ip_address'           => 'pp._ip_address',
				'user_id'              => 'pp._user_id',
				'process_date'         => 'UNIX_TIMESTAMP(pp._process_date)',
				'credits'              => 'pp._credits',
				'credit_exchange_rate' => 'pp._credit_rate',
				'exchanged'            => 'pp._exchanged',
				'item_name'            => 'pp._item_name',
				'item_number'          => 'pp._item_number',
				'quantity'             => 'pp._quantity',
				'deposited'            => 'pp._deposited',
				'gross'                => 'pp._gross',
				'currency'             => 'pp._currency',
				'fee'                  => 'pp._fee',
				'payment_date'         => 'UNIX_TIMESTAMP(pp._payment_date)',
				'txn_type'             => 'pp._txn_type',
				'txn_id'               => 'pp._txn_id',
				'parent_txn_id'        => 'pp._parent_txn_id',
				'payer_status'         => 'pp._payer_status',
				'payer_id'             => 'pp._payer_id',
				'payer_email'          => 'pp._payer_email',
				'payment_type'         => 'pp._payment_type',
				'first_name'           => 'pp._first_name',
				'last_name'            => 'pp._last_name',
				'receiver_id'          => 'pp._receiver_id',
				'receiver_email'       => 'pp._receiver_email',
				'request'              => 'pp._request'
			))
			->whereOptions(array(
				'id'                   => 'pp.id',
				'sandbox'              => 'pp._sandbox',
				'ip_address'           => 'pp._ip_address',
				'user_id'              => 'pp._user_id',
				'process_date'         => 'pp._process_date',
				'credits'              => 'pp._credits',
				'credit_exchange_rate' => 'pp._credit_rate',
				'exchanged'            => 'pp._exchanged',
				'item_name'            => 'pp._item_name',
				'item_number'          => 'pp._item_number',
				'quantity'             => 'pp._quantity',
				'deposited'            => 'pp._deposited',
				'gross'                => 'pp._gross',
				'currency'             => 'pp._currency',
				'fee'                  => 'pp._fee',
				'payment_date'         => 'pp._payment_date',
				'txn_type'             => 'pp._txn_type',
				'txn_id'               => 'pp._txn_id',
				'parent_txn_id'        => 'pp._parent_txn_id',
				'payer_status'         => 'pp._payer_status',
				'payer_id'             => 'pp._payer_id',
				'payer_email'          => 'pp._payer_email',
				'payment_type'         => 'pp._payment_type',
				'first_name'           => 'pp._first_name',
				'last_name'            => 'pp._last_name',
				'receiver_id'          => 'pp._receiver_id',
				'receiver_email'       => 'pp._receiver_email'
			))
			->from(ac_table('paypal_txn'), 'pp')
			->groupBy('pp.id')
			->parser(array( __CLASS__, 'parseTxnSql' ));
	}

	/**
	 * @param int    $id
	 * @param string $type
	 * @return \Aqua\Log\PayPalLog|null
	 */
	public static function get($id, $type = 'id')
	{
		$select = Query::select(App::connection())
			->columns(array(
				'id'                   => 'pp.id',
				'sandbox'              => 'pp._sandbox',
				'ip_address'           => 'pp._ip_address',
				'user_id'              => 'pp._user_id',
				'process_date'         => 'UNIX_TIMESTAMP(pp._process_date)',
				'credits'              => 'pp._credits',
				'credit_exchange_rate' => 'pp._credit_rate',
				'exchanged'            => 'pp._exchanged',
				'item_name'            => 'pp._item_name',
				'item_number'          => 'pp._item_number',
				'quantity'             => 'pp._quantity',
				'deposited'            => 'pp._deposited',
				'gross'                => 'pp._gross',
				'currency'             => 'pp._currency',
				'fee'                  => 'pp._fee',
				'payment_date'         => 'UNIX_TIMESTAMP(pp._payment_date)',
				'payment_type'         => 'pp._payment_type',
				'txn_type'             => 'pp._txn_type',
				'txn_id'               => 'pp._txn_id',
				'parent_txn_id'        => 'pp._parent_txn_id',
				'payer_status'         => 'pp._payer_status',
				'payer_id'             => 'pp._payer_id',
				'payer_email'          => 'pp._payer_email',
				'first_name'           => 'pp._first_name',
				'last_name'            => 'pp._last_name',
				'receiver_id'          => 'pp._receiver_id',
				'receiver_email'       => 'pp._receiver_email',
				'request'              => 'pp._request'
			))
			->from(ac_table('paypal_txn'), 'pp')
			->limit(1)
			->parser(array( __CLASS__, 'parseTxnSql' ));
		switch($type) {
			case 'id':
				$select->where(array( 'pp.id' => $id ));
				break;
			case 'txn_id':
				$select->where(array( 'pp._txn_id' => $id ));
				break;
			default:
				return null;
		}
		$select->query();

		return ($select->valid() ? $select->current() : null);
	}

	/**
	 * @param \Aqua\Http\Request $request
	 * @param int                $user_id
	 * @param int                $credits
	 * @param int                $exchange_rate
	 * @param bool               $exchanged
	 * @return \Aqua\Log\PayPalLog|null
	 */
	public static function logSql(Request $request, $user_id, $credits, $exchange_rate, $exchanged)
	{
		$sth = App::connection()->prepare(sprintf('
		INSERT INTO %s
		(
		  _sandbox,
		  _process_date,
		  _ip_address,
		  _user_id,
		  _credits,
		  _credit_rate,
		  _exchanged,
		  _item_name,
		  _item_number,
		  _quantity,
		  _deposited,
		  _gross,
		  _fee,
		  _currency,
		  _payment_date,
		  _payment_type,
		  _txn_type,
		  _txn_id,
		  _parent_txn_id,
		  _payer_id,
		  _payer_status,
		  _payer_email,
		  _first_name,
		  _last_name,
		  _receiver_id,
		  _receiver_email,
		  _request
		)
		VALUES
		(
		  :sandbox,
		  NOW(),
		  :ip,
		  :user,
		  :credits,
		  :credit_exchange,
		  :exchanged,
		  :item_name,
		  :item_number,
		  :quantity,
		  :deposited,
		  :gross,
		  :fee,
		  :currency,
		  :payment_date,
		  :payment_type,
		  :txn_type,
		  :txn_id,
		  :parent_id,
		  :payer_id,
		  :payer_status,
		  :payer_email,
		  :first_name,
		  :last_name,
		  :receiver_id,
		  :receiver_email,
		  :request
		)
		', ac_table('paypal_txn')));
		$mc_fee        = $request->getFloat('mc_fee');
		$mc_gross      = $request->getFloat('mc_gross');
		$settle_amount = $request->getFloat('settle_amount');
		$deposited     = ($settle_amount ? $settle_amount : $mc_gross - $mc_fee);
		$sth->bindValue(':sandbox', ($request->getInt('test_ipn', 0) ? 'y' : 'n'), \PDO::PARAM_STR);
		$sth->bindValue(':ip', $request->ipString, \PDO::PARAM_STR);
		if($user_id) {
			$sth->bindValue(':user', $user_id, \PDO::PARAM_STR);
		} else {
			$sth->bindValue(':user', null, \PDO::PARAM_NULL);
		}
		$sth->bindValue(':credits', $credits, \PDO::PARAM_INT);
		$sth->bindValue(':credit_exchange', strval($exchange_rate), \PDO::PARAM_STR);
		$sth->bindValue(':exchanged', ($exchanged ? 'y' : 'n'), \PDO::PARAM_STR);
		$sth->bindValue(':item_name', $request->getString('item_name'), \PDO::PARAM_STR);
		$sth->bindValue(':item_number', $request->getString('item_number'), \PDO::PARAM_STR);
		$sth->bindValue(':quantity', $request->getInt('quantity'), \PDO::PARAM_INT);
		$sth->bindValue(':deposited', strval($deposited), \PDO::PARAM_STR);
		$sth->bindValue(':gross', strval($mc_gross), \PDO::PARAM_STR);
		$sth->bindValue(':fee', strval($mc_fee), \PDO::PARAM_STR);
		$sth->bindValue(':currency', $request->getString('mc_currency'), \PDO::PARAM_STR);
		$sth->bindValue(':payment_date', date('Y-m-d H:i:s', strtotime($request->getString('payment_date'))),
		                \PDO::PARAM_STR);
		$sth->bindValue(':payment_type', $request->getString('payment_type'), \PDO::PARAM_STR);
		$sth->bindValue(':txn_type', $request->getString('txn_type'), \PDO::PARAM_STR);
		$sth->bindValue(':txn_id', $request->getString('txn_id'), \PDO::PARAM_STR);
		$sth->bindValue(':parent_id', $request->getString('parent_txn_id'), \PDO::PARAM_STR);
		$sth->bindValue(':payer_email', $request->getString('payer_email'), \PDO::PARAM_STR);
		$sth->bindValue(':payer_id', $request->getString('payer_id'), \PDO::PARAM_STR);
		$sth->bindValue(':payer_status', $request->getString('payer_status'), \PDO::PARAM_STR);
		$sth->bindValue(':first_name', $request->getString('first_name'), \PDO::PARAM_STR);
		$sth->bindValue(':last_name', $request->getString('last_name'), \PDO::PARAM_STR);
		$sth->bindValue(':receiver_id', $request->getString('receiver_id'), \PDO::PARAM_STR);
		$sth->bindValue(':receiver_email', $request->getString('receiver_email'), \PDO::PARAM_STR);
		$sth->bindValue(':request', serialize($request->data), \PDO::PARAM_LOB);
		$sth->execute();
		$pp = self::get(App::connection()->lastInsertId());
		self::$cache !== null or self::fetchCache(null, true);
		if(!empty(self::$cache)) {
			$settings = App::settings()->get('donation');
			if($settings->get('goal', 0) && !$pp->sandbox && $pp->currency === $settings->get('currency')) {
				if(!isset(self::$cache['goal_expire']) || self::$cache['goal_expire'] < time()) {
					self::rebuildCache('goal');
				} else {
					self::$cache['goal']['total'] += $pp->deposited;
					self::$cache['goal']['fee'] += $pp->fee;
				}
			}
			$data = array(
				'id'           => $pp->id,
				'user_id'      => $pp->userId,
				'credits'      => $pp->credits,
				'deposited'    => $pp->deposited,
				'gross'        => $pp->gross,
				'currency'     => $pp->currency,
				'fee'          => $pp->fee,
				'process_date' => $pp->processDate,
				'display_name' => null,
				'role_id'      => null
			);
			if($acc = $pp->account()) {
				$data['display_name'] = $acc->displayName;
				$data['role_id']      = $acc->roleId;
			}
			array_unshift(self::$cache['last_donation'], $data);
			if(count(self::$cache['last_donation']) > self::CACHE_RECENT_DONATIONS) {
				self::$cache['last_donation'] = array_slice(
					self::$cache['last_donation'],
					0,
					self::CACHE_RECENT_DONATIONS,
					false
				);
			}
			App::cache()->store(self::CACHE_KEY, self::$cache, self::CACHE_TTL);
		}

		return $pp;
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
		$settings = App::settings()->get('donation');
		if($settings->get('goal', 0) && ($name === null || $name === 'goal') &&
		   (!isset(self::$cache['goal_expire']) || self::$cache['goal_expire'] < time())
		) {
			self::rebuildCache('goal');
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
		$settings = App::settings()->get('donation');
		if(!$name || $name === 'last_donation') {
			self::$cache['last_donation'] = Query::select(App::connection())
				->columns(array(
					'id'           => 'pp.id',
					'user_id'      => 'pp._user_id',
					'credits'      => 'pp._credits',
					'currency'     => 'pp._currency',
					'deposited'    => 'pp._deposited',
					'gross'        => 'pp._gross',
					'fee'          => 'pp._fee',
					'process_date' => 'UNIX_TIMESTAMP(pp._process_date)',
					'display_name' => 'u._display_name',
					'role_id'      => 'u._role_id'
				))
				->setColumnType(array(
					'id'           => 'integer',
					'user_id'      => 'integer',
					'credits'      => 'integer',
					'deposited'    => 'float',
					'gross'        => 'float',
					'fee'          => 'float',
					'process_date' => 'integer',
					'role_id'      => 'integer',
				))
				->from(ac_table('paypal_txn'), 'pp')
				->leftJoin(ac_table('users'), 'u.id = pp._user_id', 'u')
				->order(array( 'pp._process_date' => 'DESC' ))
				->limit(self::CACHE_RECENT_DONATIONS)
				->query()
				->results;
		}
		if($settings->get('goal', 0) && (!$name || $name === 'goal')) {
			switch($settings->get('goal_interval')) {
				default:
				case 'monthly':
					$now  = strtotime('first day of this month midnight');
					$then = strtotime('first day of next month midnight');
					break;
				case 'weekly':
					$now  = strtotime('last sunday midnight');
					$then = strtotime('next sunday midnight');
					break;
				case 'daily':
					$now  = strtotime('midnight');
					$then = strtotime('tomorrow midnight');
					break;
			}
			self::$cache['goal'] = Query::select(App::connection())
				->columns(array( 'total' => 'SUM(_deposited)', 'fee' => 'SUM(_fee)' ))
				->setColumnType(array( 'total' => 'float', 'fee' => 'float' ))
				->from(ac_table('paypal_txn'))
				->where(array(
					'_sandbox'      => 'n',
					'_currency'     => $settings->get('currency', 'USD'),
					'_process_date' => array( Search::SEARCH_HIGHER, date('Y-m-d H:i:s', $now) )
				))
				->query()
				->current();
			self::$cache['goal_expire'] = $then;
		}
		App::cache()->store(self::CACHE_KEY, self::$cache, self::CACHE_TTL);
	}

	/**
	 * @param array $data
	 * @return \Aqua\Log\PayPalLog
	 */
	public static function parseTxnSql(array $data)
	{
		$pp                      = new self;
		$pp->id                  = (int)$data['id'];
		$pp->userId              = (int)$data['user_id'];
		$pp->credits             = (int)$data['credits'];
		$pp->quantity            = (int)$data['quantity'];
		$pp->processDate         = (int)$data['process_date'];
		$pp->paymentDate         = (int)$data['payment_date'];
		$pp->sandbox             = ($data['sandbox'] === 'y');
		$pp->creditExchangeRate  = (float)$data['credit_exchange_rate'];
		$pp->deposited           = (float)$data['deposited'];
		$pp->gross               = (float)$data['gross'];
		$pp->fee                 = (float)$data['fee'];
		$pp->currency            = $data['currency'];
		$pp->itemName            = $data['item_name'];
		$pp->itemNumber          = $data['item_number'];
		$pp->ipAddress           = $data['ip_address'];
		$pp->paymentType         = $data['payment_type'];
		$pp->transactionType     = $data['txn_type'];
		$pp->transactionId       = $data['txn_id'];
		$pp->parentTransactionId = $data['parent_txn_id'];
		$pp->payerStatus         = $data['payer_status'];
		$pp->payerId             = $data['payer_id'];
		$pp->payerEmail          = $data['payer_email'];
		$pp->firstName           = $data['first_name'];
		$pp->lastName            = $data['last_name'];
		$pp->receiverId          = $data['receiver_id'];
		$pp->receiverEmail       = $data['receiver_email'];
		$pp->requestData         = $data['request'];

		return $pp;
	}
}