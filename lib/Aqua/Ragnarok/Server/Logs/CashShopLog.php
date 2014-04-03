<?php
namespace Aqua\Ragnarok\Server\Logs;

class CashShopLog
{
	/**
	 * @var \Aqua\Ragnarok\Server\CharMap
	 */
	public $charmap;
	/**
	 * Log ID
	 *
	 * @var int
	 */
	public $id;
	/**
	 * Request IP address
	 *
	 * @var string
	 */
	public $ipAddress;
	/**
	 * Target server account ID
	 *
	 * @var int
	 */
	public $accountId;
	/**
	 * Purchase timestamp
	 *
	 * @var int
	 */
	public $date;
	/**
	 * Total price
	 *
	 * @var int
	 */
	public $total;
	/**
	 * Amount of distinct items purchased
	 *
	 * @var int
	 */
	public $items;
	/**
	 * Amount of items purchased
	 *
	 * @var int
	 */
	public $amount;
	/**
	 * @var array
	 */
	protected $_cart;

	/**
	 * Format purchase date
	 *
	 * @param string $format
	 * @return string
	 */
	public function date($format)
	{
		return strftime($format, $this->date);
	}

	public function account()
	{
		return $this->charmap->server->login->get($this->accountId, 'id');
	}

	public function cart()
	{
		if(!$this->_cart) {
			$sth = $this->charmap->log->connection()->prepare("
			SELECT `item_id`, `amount`, `item_price` AS `price`
			FROM {$this->charmap->log->table('ac_cash_shop_items')}
			WHERE id = ?
			");
			$sth->bindValue(1, $this->id, \PDO::PARAM_INT);
			$sth->execute();
			$this->_cart = array();
			while($data = $sth->fetch(\PDO::FETCH_ASSOC)) {
				$this->_cart[$data['item_id']] = $data;
			}
		}
		return $this->_cart;
	}
}
