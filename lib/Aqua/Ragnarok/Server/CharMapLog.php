<?php
namespace Aqua\Ragnarok\Server;

use Aqua\Core\App;
use Aqua\Core\Log as ACLog;
use Aqua\Log\ErrorLog;
use Aqua\Ragnarok\Account;
use Aqua\Ragnarok\Cart;
use Aqua\Ragnarok\Character;
use Aqua\Ragnarok\Item;
use Aqua\Ragnarok\Server\Logs\CashShopLog;
use Aqua\Ragnarok\Server\Logs\PickLog;
use Aqua\SQL\Query;

class CharMapLog
{
	/**
	 * @var \Aqua\Ragnarok\Server\CharMap
	 */
	public $charmap;

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
	 * @param \Aqua\Ragnarok\Server\CharMap $charmap
	 * @param string        $database
	 * @param array   $db_settings
	 * @param array   $tables
	 */
	public function __construct(CharMap $charmap, $database, array $db_settings, array $tables)
	{
		$this->charmap = $charmap;
		$this->db = $database;
		$this->dbSettings = $db_settings;
		$this->tables = $tables + $this->tables;
	}

	public function logItemTransfer(Character $from, Character $to, Item $item)
	{

	}

	/**
	 * @param Character $char
	 * @param bool $keep_child
	 */
	public function logDivorce(Character $char, $keep_child)
	{
		$sth = $this->connection()->prepare("
		INSERT INTO {$this->table('ac_divorce_log')} (char_id, partner_id, child_id, keep_child, `date`)
		VALUES (?, ?, ?, NOW())
		");
		$sth->bindValue(1, $char->id, \PDO::PARAM_INT);
		$sth->bindValue(2, $char->partnerId, \PDO::PARAM_INT);
		$sth->bindValue(3, $char->childId, \PDO::PARAM_INT);
		$sth->bindValue(4, ($keep_child ? 'y' : 'n'), \PDO::PARAM_STR);
		$sth->execute();
	}

	/**
	 * @return \Aqua\SQL\Search
	 */
	public function searchCashShopLog()
	{
		$search = Query::search($this->connection());
		return $search->columns(array(
				'id' => 'c.id',
				'ip_address' => 'c.ip_address',
				'account_id' => 'c.account_id',
				'total' => 'c.total',
				'date' => 'c.date',
				'items' => 'COUNT(i.item_id)',
				'amount' => 'SUM(i.amount)'
			))
			->whereOptions(array(
				'id' => 'c.id',
				'ip_address' => 'c.ip_address',
				'account_id' => 'c.account_id',
				'total' => 'c.total',
				'date' => 'c.date',
				'items' => 'COUNT(i.item_id)',
				'amount' => 'SUM(i.amount)',
				'item_id' => 'i.item_id',
			))
			->from($this->table('ac_cash_shop_log'), 'c')
			->leftJoin($this->table('ac_cash_shop_items'), 'i.id = c.id', 'i')
			->parser(array( $this, 'parseCashShopLog' ));
	}

	/**
	 * @param int $id
	 * @return \Aqua\Ragnarok\Server\Logs\CashShopLog
	 */
	public function getCashShopLog($id)
	{
		$search = $this->searchCashShopLog()
			->where(array( 'id' => $id ))
			->query();

		return ($search->valid() ? $search->current() : null);
	}

	/**
	 * @param int     $accountId
	 * @param \Aqua\Ragnarok\Cart $cart
	 * @return bool
	 */
	public function logCashShopPurchase($accountId, Cart $cart)
	{
		$sth = $this->connection()->prepare("
		INSERT INTO {$this->table('ac_cash_shop_log')} (ip_address, account_id, total, items, `date`)
		VALUES (?, ?, ?, ?, NOW())
		");
		$sth->bindValue(1, App::request()->ipString, \PDO::PARAM_STR);
		$sth->bindValue(2, $accountId, \PDO::PARAM_INT);
		$sth->bindValue(3, $cart->total, \PDO::PARAM_INT);
		$sth->bindValue(4, $cart->itemCount, \PDO::PARAM_INT);
		$sth->execute();
		$id = $this->connection()->lastInsertId();
		$sth = $this->connection()->prepare("
		INSERT INTO {$this->table('ac_cash_shop_items')} (id, item_id, amount, item_price)
		VALUES (:id, :item, :amount, :price)
		");
		foreach($cart->items as $itemId => $item) {
			$sth->bindValue(':id', $id, \PDO::PARAM_INT);
			$sth->bindValue(':item', $itemId, \PDO::PARAM_INT);
			$sth->bindValue(':amount', $item['amount'], \PDO::PARAM_INT);
			$sth->bindValue(':price', $item['price'], \PDO::PARAM_INT);
			$sth->execute();
			$sth->closeCursor();
		}
		return true;
	}

	/**
	 * @return \Aqua\SQL\Search
	 */
	public function searchPickLog()
	{
		return Query::search($this->connection())
			->columns(array(
				'id' => 'pl.id',
			    'char_id' => 'pl.char_id',
			    'type' => '(pl.type + 0)',
			    'item_id' => 'pl.nameid',
			    'amount' => 'pl.amount',
			    'refine' => 'pl.refine',
			    'card0' => 'pl.card0',
			    'card1' => 'pl.card1',
			    'card2' => 'pl.card2',
			    'card3' => 'pl.card3',
			    'unique_id' => 'pl.unique_id',
			    'map' => 'pl.map',
			    'date' => 'UNIX_TIMESTAMP(pl.time)'
			))
			->whereOptions(array(
	           'id' => 'pl.id',
	           'char_id' => 'pl.char_id',
	           'type' => 'pl.type',
	           'item_id' => 'pl.nameid',
	           'amount' => 'pl.amount',
	           'refine' => 'pl.refine',
	           'card0' => 'pl.card0',
	           'card1' => 'pl.card1',
	           'card2' => 'pl.card2',
	           'card3' => 'pl.card3',
	           'unique_id' => 'pl.unique_id',
	           'map' => 'pl.map',
	           'date' => 'pl.time'
			))
			->from($this->table('picklog'), 'pl')
			->parser(array( $this, 'parsePickLogSql' ));
	}

	/**
	 * @param int $id
	 * @return \Aqua\Ragnarok\Server\Logs\PickLog|bool
	 */
	public function getPickLog($id)
	{
		$search = $this->searchPickLog()
			->where(array( 'id' => $id ))
			->query();

		return ($search->valid() ? $search->current() : null);
	}

	public function parseCashShopLog(array $data)
	{
		$log = new CashShopLog;
		$log->charmap = &$this;
		$log->id = (int)$data['id'];
		$log->accountId = (int)$data['account_id'];
		return $log;
	}

	/**
	 * @param array $data
	 * @return \Aqua\Ragnarok\Server\Logs\PickLog
	 */
	public function parsePickLogSql(array $data)
	{
		$log = new PickLog;
		$log->id        = (int)$data['id'];
		$log->date      = (int)$data['date'];
		$log->charId    = (int)$data['char_id'];
		$log->type      = (int)$data['type'];
		$log->itemId    = (int)$data['item_id'];
		$log->amount    = (int)$data['amount'];
		$log->refine    = (int)$data['refine'];
		$log->cards[0]  = (int)$data['card0'];
		$log->cards[1]  = (int)$data['card1'];
		$log->cards[2]  = (int)$data['card2'];
		$log->cards[3]  = (int)$data['card3'];
		$log->uniqueId  = $data['unique_id'];
		$log->map       = $data['map'];
		return $log;
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
}
