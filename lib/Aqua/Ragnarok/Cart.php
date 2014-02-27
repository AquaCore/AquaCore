<?php
namespace Aqua\Ragnarok;

use Aqua\Ragnarok\Server\CharMap;

class Cart
implements \Serializable
{
	public $key;

	/**
	 * @var \Aqua\Ragnarok\Server\Charmap
	 */
	public $charmap;

	/**
	 * @var int
	 */
	public $total = 0;

	/**
	 * @var array
	 */
	public $items = array();

	public function __construct()
	{
		$this->key = bin2hex(secure_random_bytes(32));
	}

	public function add(ItemData $item, $amount)
	{
		if(isset($this->items[$item->id])) {
			$this->items[$item->id]['amount'] += $amount;
		} else {
			$this->items[$item->id] = array(
				'name'   => $item->jpName,
				'amount' => $amount,
				'price'  => $item->shopPrice
			);
		}
		return true;
	}

	public function remove(ItemData $item, $amount)
	{
		if(!isset($this->items[$item->id])) {
			return true;
		}
		if(!$amount || $this->items[$item->id]['amount'] <= $amount) {
			unset($this->items[$item->id]);
		} else {
			$this->items[$item->id]['amount'] -= $amount;
		}
		return true;
	}

	public function clear()
	{
		$this->items = array();
		$this->total = 0;
	}

	public function serialize()
	{
		return serialize(array( $this->charmap->key, $this->total, $this->items ));
	}

	public function unserialize($str)
	{
		list($charmap, $this->total, $this->items) = unserialize($str);
		$this->charmap = Server::get($charmap);
	}
}
