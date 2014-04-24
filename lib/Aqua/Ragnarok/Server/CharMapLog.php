<?php
namespace Aqua\Ragnarok\Server;

use Aqua\Core\App;
use Aqua\Ragnarok\Account;
use Aqua\Ragnarok\Cart;
use Aqua\Ragnarok\Character;
use Aqua\Ragnarok\Item;
use Aqua\Ragnarok\Server\Logs\AtcommandLog;
use Aqua\Ragnarok\Server\Logs\BranchLog;
use Aqua\Ragnarok\Server\Logs\CashShopLog;
use Aqua\Ragnarok\Server\Logs\ChatLog;
use Aqua\Ragnarok\Server\Logs\MVPLog;
use Aqua\Ragnarok\Server\Logs\NPCLog;
use Aqua\Ragnarok\Server\Logs\PickLog;
use Aqua\Ragnarok\Server\Logs\ZenyLog;
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
		$columns = array(
			'id'         => 'c.id',
			'ip_address' => 'c.ip_address',
			'account_id' => 'c.account_id',
			'total'      => 'c.total',
			'date'       => 'UNIX_TIMESTAMP(c.`date`)',
			'items'      => 'c.items',
			'amount'     => 'c.amount'
		);
		return Query::search($this->connection())
			->columns($columns)
			->whereOptions(array( 'date' => 'c.`date`' ) + $columns)
			->groupBy('id')
			->from($this->table('ac_cash_shop_log'), 'c')
			->parser(array( $this, 'parseCashShopLogSql'));
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
	 * Log a shop purchase in the database
	 *
	 * @param \Aqua\Ragnarok\Account $account
	 * @param \Aqua\Ragnarok\Cart    $cart
	 * @return bool
	 */
	public function logCashShopPurchase(Account $account, Cart $cart)
	{
		$sth = $this->connection()->prepare("
		INSERT INTO {$this->table('ac_cash_shop_log')} (ip_address, account_id, total, items, amount, `date`)
		VALUES (:ip, :accid, :total, :items, :amount, NOW())
		");
		$sth->bindValue(':ip', App::request()->ipString, \PDO::PARAM_STR);
		$sth->bindValue(':accid', $account->id, \PDO::PARAM_INT);
		$sth->bindValue(':items', count($cart->items), \PDO::PARAM_INT);
		$sth->bindValue(':amount', $cart->itemCount, \PDO::PARAM_INT);
		$sth->bindValue(':total', $cart->total, \PDO::PARAM_INT);
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
		$columns = array(
			'id'        => 'pl.id',
			'char_id'   => 'pl.char_id',
			'type'      => 'pl.type',
			'item_id'   => 'pl.nameid',
			'amount'    => 'pl.amount',
			'refine'    => 'pl.refine',
			'card0'     => 'pl.card0',
			'card1'     => 'pl.card1',
			'card2'     => 'pl.card2',
			'card3'     => 'pl.card3',
			'unique_id' => 'pl.unique_id',
			'map'       => 'pl.map',
			'date'      => 'UNIX_TIMESTAMP(pl.time)'
		);
		return Query::search($this->connection())
			->columns($columns)
			->whereOptions(array(
				'type' => 'pl.type',
				'date' => 'pl.time'
			) + $columns)
			->groupBy('id')
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

	/**
	 * @return \Aqua\SQL\Search
	 */
	public function searchZenyLog()
	{
		$columns = array(
			'id'        => 'z.id',
			'date'      => 'UNIX_TIMESTAMP(z.`time`)',
			'char_id'   => 'z.char_id',
			'src_id'    => 'z.src_id',
			'type'      => 'z.`type`',
			'amount'    => 'z.amount',
			'map'       => 'z.map'
		);
		return Query::search($this->connection())
			->columns($columns)
			->whereOptions(array(
				'type' => 'z.`type`',
				'date' => 'z.`time`'
			) + $columns)
			->groupBy('id')
			->from($this->table('zenylog'), 'z')
			->parser(array( $this, 'parseZenyLogSql' ));
	}

	/**
	 * @param int $id
	 * @return \Aqua\Ragnarok\Server\Logs\ZenyLog|null
	 */
	public function getZenyLog($id)
	{
		$search = $this->searchZenyLog()
			->where(array( 'id' => $id ))
			->query();

		return ($search->valid() ? $search->current() : null);
	}

	/**
	 * @return \Aqua\SQL\Search
	 */
	public function searchAtcommandLog()
	{
		$columns = array(
			'id'            => 'a.atcommand_id',
			'date'          => 'UNIX_TIMESTAMP(a.atcommand_date)',
			'account_id'    => 'a.account_id',
			'char_id'       => 'a.char_id',
			'char_name'     => 'a.char_name',
			'map'           => 'a.map',
			'command'       => 'a.command',
		);
		return Query::search($this->connection())
			->columns($columns)
			->whereOptions(array( 'date' => 'a.atcommand_date' ) + $columns)
			->groupBy('id')
			->from($this->table('atcommandlog'), 'a')
			->parser(array( $this, 'parseAtcommandLogSql' ));
	}

	/**
	 * @param int $id
	 * @return \Aqua\Ragnarok\Server\Logs\AtcommandLog|null
	 */
	public function getAtcommandLog($id)
	{
		$search = $this->searchAtcommandLog()
			->where(array( 'id' => $id ))
			->query();

		return ($search->valid() ? $search->current() : null);
	}

	/**
	 * @return \Aqua\SQL\Search
	 */
	public function searchMVPLog()
	{
		$columns = array(
			'id'            => 'm.mvp_id',
			'date'          => 'UNIX_TIMESTAMP(m.mvp_date)',
			'char_id'       => 'm.kill_char_id',
			'monster_id'    => 'm.monster_id',
			'prize'         => 'm.prize',
			'experience'    => 'm.mvpexp',
			'map'           => 'm.map'
		);
		return Query::search($this->connection())
			->columns($columns)
			->whereOptions(array( 'date' => 'm.mvp_date' ) + $columns)
			->groupBy('id')
			->from($this->table('mvplog'), 'm')
			->parser(array( $this, 'parseMVPLogSql' ));
	}

	/**
	 * @param int $id
	 * @return \Aqua\Ragnarok\Server\Logs\MVPLog|null
	 */
	public function getMVPLog($id)
	{
		$search = $this->searchMVPLog()
			->where(array( 'id' => $id ))
			->query();

		return ($search->valid() ? $search->current() : null);
	}

	/**
	 * @return \Aqua\SQL\Search
	 */
	public function searchNPCLog()
	{
		$columns = array(
			'id'            => 'n.npc_id',
			'date'          => 'UNIX_TIMESTAMP(n.npc_date)',
			'account_id'    => 'n.account_id',
			'char_id'       => 'n.char_id',
			'char_name'     => 'n.char_name',
			'map'           => 'n.map',
			'message'       => 'n.mes'
		);
		return Query::search($this->connection())
			->columns($columns)
			->whereOptions(array( 'date' => 'n.npc_date' ) + $columns)
			->groupBy('id')
			->from($this->table('npclog'), 'n')
			->parser(array( $this, 'parseNPCLogSql' ));
	}

	/**
	 * @param int $id
	 * @return \Aqua\Ragnarok\Server\Logs\NPCLog|null
	 */
	public function getNPCLog($id)
	{
		$search = $this->searchNPCLog()
			->where(array( 'id' => $id ))
			->query();

		return ($search->valid() ? $search->current() : null);
	}

	/**
	 * @return \Aqua\SQL\Search
	 */
	public function searchChatLog()
	{
		$columns = array(
			'id'                => 'c.id',
			'date'              => 'UNIX_TIMESTAMP(c.`time`)',
			'type'              => '(c.`type` + 0)',
			'type_id'           => 'c.`type_id`',
			'src_account_id'    => 'c.src_accountid',
			'src_char_id'       => 'c.src_charid',
			'dst_char_name'     => 'c.dst_charname',
			'map'               => 'c.src_map',
			'x'                 => 'c.src_map_x',
			'y'                 => 'c.src_map_y',
			'message'           => 'c.message'
		);
		return Query::search($this->connection())
			->columns($columns)
			->whereOptions(array(
				'type' => 'c.`type`',
				'date' => 'c.`time`'
			) + $columns)
			->groupBy('id')
			->from($this->table('chatlog'), 'c')
			->parser(array( $this, 'parseChatLogSql' ));
	}

	/**
	 * @param int $id
	 * @return \Aqua\Ragnarok\Server\Logs\ChatLog|null
	 */
	public function getChatLog($id)
	{
		$search = $this->searchChatLog()
			->where(array( 'id' => $id ))
			->query();

		return ($search->valid() ? $search->current() : null);
	}

	/**
	 * @return \Aqua\SQL\Search
	 */
	public function searchBranchLog()
	{
		$columns = array(
			'id'            => 'b.branch_id',
			'date'          => 'UNIX_TIMESTAMP(b.`branch_date`)',
			'account_id'    => 'b.account_id',
			'char_id'       => 'b.char_id',
			'char_name'     => 'b.char_name',
			'map'           => 'b.map'
		);
		return Query::search($this->connection())
			->columns($columns)
			->whereOptions(array( 'date' => 'b.branch_date' ) + $columns)
			->groupBy('id')
			->from($this->table('branchlog'), 'b')
			->parser(array( $this, 'parseBranchLogSql' ));
	}

	/**
	 * @param int $id
	 * @return \Aqua\Ragnarok\Server\Logs\BranchLog|null
	 */
	public function getBranchLog($id)
	{
		$search = $this->searchBranchLog()
			->where(array( 'id' => $id ))
			->query();

		return ($search->valid() ? $search->current() : null);
	}

	/**
	 * @param array $data
	 * @return \Aqua\Ragnarok\Server\Logs\CashShopLog
	 */
	public function parseCashShopLogSql(array $data)
	{
		$log = new CashShopLog;
		$log->charmap   = &$this->charmap;
		$log->id        = (int)$data['id'];
		$log->accountId = (int)$data['account_id'];
		$log->date      = (int)$data['date'];
		$log->total     = (int)$data['total'];
		$log->items     = (int)$data['items'];
		$log->amount    = (int)$data['amount'];
		$log->ipAddress = $data['ip_address'];

		return $log;
	}

	/**
	 * @param array $data
	 * @return \Aqua\Ragnarok\Server\Logs\PickLog
	 */
	public function parsePickLogSql(array $data)
	{
		$log = new PickLog;
		$log->charmap   = &$this->charmap;
		$log->id        = (int)$data['id'];
		$log->date      = (int)$data['date'];
		$log->charId    = (int)$data['char_id'];
		$log->itemId    = (int)$data['item_id'];
		$log->amount    = (int)$data['amount'];
		$log->refine    = (int)$data['refine'];
		$log->cards[0]  = (int)$data['card0'];
		$log->cards[1]  = (int)$data['card1'];
		$log->cards[2]  = (int)$data['card2'];
		$log->cards[3]  = (int)$data['card3'];
		$log->type      = $data['type'];
		$log->uniqueId  = $data['unique_id'];
		$log->map       = $data['map'];

		return $log;
	}

	/**
	 * @param array $data
	 * @return \Aqua\Ragnarok\Server\Logs\ZenyLog
	 */
	public function parseZenyLogSql(array $data)
	{
		$log = new ZenyLog;
		$log->charmap   = &$this->charmap;
		$log->id        = (int)$data['id'];
		$log->date      = (int)$data['date'];
		$log->charId    = (int)$data['char_id'];
		$log->srcId     = (int)$data['src_id'];
		$log->amount    = (int)$data['amount'];
		$log->type      = $data['type'];
		$log->map       = $data['map'];

		return $log;
	}

	/**
	 * @param array $data
	 * @return \Aqua\Ragnarok\Server\Logs\AtcommandLog
	 */
	public function parseAtcommandLogSql(array $data)
	{
		$log = new AtcommandLog;
		$log->charmap   = &$this->charmap;
		$log->id        = (int)$data['id'];
		$log->date      = (int)$data['date'];
		$log->accountId = (int)$data['account_id'];
		$log->charId    = (int)$data['char_id'];
		$log->charName  = $data['char_name'];
		$log->command   = $data['command'];
		$log->map       = $data['map'];

		return $log;
	}

	/**
	 * @param array $data
	 * @return \Aqua\Ragnarok\Server\Logs\MVPLog
	 */
	public function parseMVPLogSql(array $data)
	{
		$log = new MVPLog;
		$log->charmap    = &$this->charmap;
		$log->id         = (int)$data['id'];
		$log->date       = (int)$data['date'];
		$log->charId     = (int)$data['char_id'];
		$log->mobId      = (int)$data['monster_id'];
		$log->prize      = (int)$data['prize'];
		$log->experience = (int)$data['experience'];
		$log->map        = $data['map'];

		return $log;
	}

	/**
	 * @param array $data
	 * @return \Aqua\Ragnarok\Server\Logs\NPCLog
	 */
	public function parseNPCLogSql(array $data)
	{
		$log = new NPCLog;
		$log->charmap   = &$this->charmap;
		$log->id        = (int)$data['id'];
		$log->date      = (int)$data['date'];
		$log->accountId = (int)$data['account_id'];
		$log->charId    = (int)$data['char_id'];
		$log->charName  = $data['char_name'];
		$log->map       = $data['map'];
		$log->message   = $data['message'];

		return $log;
	}

	/**
	 * @param array $data
	 * @return \Aqua\Ragnarok\Server\Logs\ChatLog
	 */
	public function parseChatLogSql(array $data)
	{
		$log = new ChatLog;
		$log->charmap      = &$this->charmap;
		$log->id           = (int)$data['id'];
		$log->date         = (int)$data['date'];
		$log->x            = (int)$data['x'];
		$log->y            = (int)$data['y'];
		$log->type         = (int)$data['type'];
		$log->typeId       = (int)$data['type_id'];
		$log->srcAccountId = (int)$data['src_account_id'];
		$log->srcCharId    = (int)$data['src_char_id'];
		$log->dstName      = $data['dst_char_name'];
		$log->message      = $data['message'];
		$log->map          = $data['map'];

		return $log;
	}

	/**
	 * @param array $data
	 * @return \Aqua\Ragnarok\Server\Logs\BranchLog
	 */
	public function parseBranchLogSql(array $data)
	{
		$log = new BranchLog;
		$log->charmap   = &$this->charmap;
		$log->id        = (int)$data['id'];
		$log->date      = (int)$data['date'];
		$log->accountId = (int)$data['account_id'];
		$log->charId    = (int)$data['char_id'];
		$log->charName  = $data['char_name'];
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
