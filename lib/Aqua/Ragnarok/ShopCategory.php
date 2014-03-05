<?php
namespace Aqua\Ragnarok;

use Aqua\Event\Event;

class ShopCategory
{
	/**
	 * @var \Aqua\Ragnarok\Server\CharMap
	 */
	public $charmap;
	/**
	 * @var int
	 */
	public $id;
	/**
	 * @var string
	 */
	public $slug;
	/**
	 * @var string
	 */
	public $name;
	/**
	 * @var string
	 */
	public $description;
	/**
	 * @var int
	 */
	public $order;

	public function search()
	{
		return $this->charmap->itemShopSearch()
			->where(array( 'shop_category' => $this->id ));
	}

	public function update(array $data)
	{

	}

	public function delete()
	{
		$sth = $this->charmap->connection()->prepare("
		UPDATE {$this->charmap->table('ac_cash_shop')}
		SET category_id = NULL
		WHERE category_id = :id;
		DELETE FROM {$this->charmap->table('ac_cash_shop_categories')}
		WHERE id = :id;
		");
		$sth->bindValue(':id', $this->id, \PDO::PARAM_INT);
		if(!$sth->execute() || !$sth->rowCount()) {
			return false;
		}
		$feedback = array( $this );
		$this->charmap->fetchCashShopCategories(true);
		Event::fire('item-shop.delete', $feedback);
		return true;
	}

	public function url()
	{
		return $this->charmap->url(array(
			'base_dir'  => \Aqua\DIR,
			'path'      => array( 'item' ),
		    'action'    => 'shop',
		    'arguments' => array( $this->slug )
		));
	}
}
