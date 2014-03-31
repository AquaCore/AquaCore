<?php
namespace Aqua\Ragnarok;

use Aqua\Event\Event;
use Aqua\Ragnarok\Server\CharMap;
use Aqua\SQL\Search;

class Cart
{
	/**
	 * @var \Aqua\Ragnarok\Server\Charmap
	 */
	public $charmap;
	/**
	 * @var string
	 */
	public $serverKey;
	/**
	 * @var string
	 */
	public $charmapKey;
	/**
	 * Associative array of items: itemId => array ( ["name"], ["price"], ["amount"] )
	 *
	 * @var array
	 */
	public $items = array();
	/**
	 * Amount of items in the cart
	 *
	 * @var int
	 */
	public $itemCount;
	/**
	 * Total price
	 *
	 * @var int
	 */
	public $total = 0;

	public function __construct(CharMap $charmap)
	{
		$this->charmap    = $charmap;
		$this->charmapKey = $charmap->key;
		$this->serverKey  = $charmap->server->key;
	}

	public function hasItem($itemId)
	{
		return array_key_exists($itemId, $this->items);
	}

	public function add($itemId, $amount)
	{
		if(!($item = $this->charmap()->item($itemId))) {
			return $this;
		}
		if($this->hasItem($itemId)) {
			$this->items[$itemId]['amount'] += $amount;
		} else {
			$this->items[$itemId] = array(
				'name'   => $item->jpName,
				'amount' => $amount,
				'price'  => $item->shopPrice
			);
		}
		$this->itemCount += $amount;
		$this->total += ($item->shopPrice * $amount);
		$feedback = array( $this, $itemId, $amount );
		Event::fire('cart.add-item', $feedback);
		return $this;
	}

	public function remove($itemId, $amount = null)
	{
		if(!$this->hasItem($itemId)) {
			return $this;
		}
		$price = $this->items[$itemId]['price'];
		if(!$amount || $this->items[$itemId]['amount'] <= $amount) {
			$amount = $this->count($itemId);
			unset($this->items[$itemId]);
		} else {
			$this->items[$itemId]['amount'] -= $amount;
		}
		$this->itemCount -= $amount;
		$this->total     -= ($amount * $price);
		$feedback = array( $this, $itemId, $amount );
		Event::fire('cart.remove-item', $feedback);
		return $this;
	}

	public function count($itemId)
	{
		return (isset($this->items[$itemId]) ? $this->items[$itemId]['amount'] : 0);
	}

	public function update()
	{
		if(empty($this->items)) {
			return $this;
		}
		$in = array_keys($this->items);
		array_unshift($in, Search::SEARCH_IN);
		$search = $this->charmap()->itemShopSearch()
			->columns(array(
				'id'    => 'cs.item_id',
				'price' => 'cs.price',
				'name' => 'tmp_tbl.`name`',
			), false)
			->setColumnType(array( 'price' => 'integer' ))
			->where(array( 'id' => $in ))
			->parser(null)
			->query();
		$items           = array();
		$this->itemCount = 0;
		$this->total     = 0;
		foreach($search as $row) {
			$amount = $this->count($row['id']);
			$items[$row['id']] = array(
				'name'   => $row['name'],
				'price'  => $row['price'],
			    'amount' => $amount
			);
			$this->itemCount += $amount;
			$this->total     += ($amount * $row['price']);
		}
		$this->items = $items;
		$feedback = array( $this );
		Event::fire('cart.update', $feedback);
		return $this;
	}

	public function checkout(Account $account)
	{
		$in = array_keys($this->items);
		array_unshift($in, Search::SEARCH_IN);
		$items = $this->charmap()->itemSearch()
			->where(array( 'id' => $in ))
			->query()
			->results;
		$insertedIds = array();
		$addItem = $this->charmap()->connection()->prepare("
		INSERT INTO {$this->charmap->table('storage')} (account_id, nameid, amount, identify)
		VALUES (:account, :item, :amount, '1')
		");
		$soldCount = $this->charmap()->connection()->prepare("
		UPDATE {$this->charmap->table('ac_cash_shop')}
		SET sold = sold + :amount
		WHERE item_id = :item
		");
		foreach($items as $item) {
			switch($item->type) {
				case 4:
				case 5:
				case 7:
				case 8:
					for($i = 0; $i < $this->count($item->id); ++$i) {
						$addItem->bindValue(':account', $account->id, \PDO::PARAM_INT);
						$addItem->bindValue(':item', $item->id, \PDO::PARAM_INT);
						$addItem->bindValue(':amount', 1, \PDO::PARAM_INT);
						$addItem->execute();
						$insertedIds[$item->id][] = $this->charmap()->connection()->lastInsertId();
						$addItem->closeCursor();
					}
					break;
				default:
					$addItem->bindValue(':account', $account->id, \PDO::PARAM_INT);
					$addItem->bindValue(':item', $item->id, \PDO::PARAM_INT);
					$addItem->bindValue(':amount', $this->count($item->id), \PDO::PARAM_INT);
					$addItem->execute();
					$insertedIds[$item->id][] = $this->charmap()->connection()->lastInsertId();
					$addItem->closeCursor();
					break;
			}
			$soldCount->bindValue(':amount', $this->count($item->id), \PDO::PARAM_INT);
			$soldCount->bindValue(':item', $item->id, \PDO::PARAM_INT);
			$soldCount->execute();
			$soldCount->closeCursor();
		}
		$this->charmap()->log->logCashShopPurchase($account, $this);
		$feedback = array( $this, $insertedIds );
		Event::fire('cart.checkout', $feedback);
		return true;
	}

	public function clear()
	{
		$this->items     = array();
		$this->total     = 0;
		$this->itemCount = 0;
		$feedback = array( $this );
		Event::fire('cart.clear', $feedback);
		return $this;
	}

	public function charmap()
	{
		if($this->charmap === null) {
			$this->charmap = Server::get($this->serverKey)->charmap($this->charmapKey);
		}
		return $this->charmap;
	}

	public function __sleep()
	{
		return array(
			'serverKey',
			'charmapKey',
			'total',
			'itemCount',
			'items'
		);
	}
}
