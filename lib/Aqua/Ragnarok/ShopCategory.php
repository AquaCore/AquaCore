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
		$values = array();
		$update = '';
		if(array_key_exists('slug', $data) && $data['slug'] !== $this->slug) {
			$values['slug'] = $data['slug'];
			$update.= '`slug` = ?, ';
		}
		if(array_key_exists('name', $data) && $data['name'] !== $this->name) {
			$values['name'] = $data['name'];
			$update.= '`name` = ?, ';
			if(!array_key_exists('slug', $data) &&
			   !array_key_exists('slug', $values)) {
				$values['slug'] = $this->charmap->shopCategorySlug($data['name'], $this->id);
				$update.= '`slug` = ?, ';
			}
		}
		if(array_key_exists('description', $data) && $data['description'] !== $this->description) {
			$values['description'] = $data['description'];
			$update.= '`description` = ?, ';
		}
		if(array_key_exists('order', $data) && (int)$data['order'] !== $this->order) {
			$values['order'] = $data['order'];
			$update.= '`order` = ?, ';
		}
		if(empty($values)) {
			return false;
		}
		$values[] = $this->id;
		$update = substr($update, 0, -2);
		$sth = $this->charmap->connection()->prepare("
		UPDATE {$this->charmap->table('ac_cash_shop_categories')}
		SET {$update}
		WHERE id = ?
		");
		$sth->execute(array_values($values));
		if(!$sth->rowCount()) {
			return false;
		}
		$feedback = array( $this, $values );
		Event::fire('ragnarok.update-shop-category', $feedback);
		foreach($values as $key => $val) {
			$this->{$key} = $val;
		}
		return true;
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
		), false);
	}
}
