<?php
namespace Page\Main\Ragnarok\Server;

use Aqua\Core\App;
use Aqua\Log\ErrorLog;
use Aqua\Ragnarok\Ragnarok;
use Aqua\Ragnarok\Server\Login;
use Aqua\Site\Page;
use Aqua\SQL\Search;
use Aqua\UI\Form;
use Aqua\UI\Menu;
use Aqua\UI\Pagination;
use Aqua\UI\Template;

class Item
extends Page
{
	/**
	 * @var \Aqua\Ragnarok\Server
	 */
	public $server;
	/**
	 * @var \Aqua\Ragnarok\Server\CharMap
	 */
	public $charmap;

	const DB_ITEMS_PER_PAGE = 10;
	const SHOP_ITEMS_PER_PAGE = 8;

	public function run()
	{
		$this->server  = &App::$activeServer;
		$this->charmap = &App::$activeCharMapServer;
		$base_url = $this->charmap->url(array( 'path' => array( 'item' ), 'action' => '' ));
		$menu = new Menu;
		$menu->append('item db', array(
			'title' => __('ragnarok', 'item-db'),
			'url'   => "{$base_url}index"
			))->append('cash shop', array(
			'title' => __('ragnarok', 'cash-shop'),
			'url'   => "{$base_url}shop"
			))
		;
		$this->theme->set('menu', $menu);
		if(App::user()->loggedIn()) {
			$this->theme->set('cart', array(
				'cart' => App::user()->cart($this->charmap),
				'charmap' => $this->charmap,
				'server' => $this->server
			));
		}
	}

	public function index_action()
	{
		$this->theme->head->section = $this->title = __('ragnarok', 'item-db');
		try {
			$current_page = $this->request->uri->getInt('page', 1, 1);
			$options = array();
			if($x = $this->request->uri->getInt('id', false, 500)) {
				if($items = $this->charmap->item($x)) {
					$items = array( $items );
					$rows = 1;
				} else {
					$items = array();
					$rows = 0;
				}
			} else {
				$search = $this->charmap->itemSearch();
				// n : Name
				if($x = $this->request->uri->getString('n', false)) {
					$search->where(array( 'name' => array( Search::SEARCH_LIKE, '%' . addcslashes($x, '%_\\') . '%' ) ));
				}
				// j : Job
				if(($x = $this->request->uri->get('j')) && ($x = ac_bitmask($x))) {
					$search->where(array( 'job' => array( Search::SEARCH_AND, $x ) ));
				}
				// u : Upper
				if(($x = $this->request->uri->get('u')) && ($x = ac_bitmask($x))) {
					$search->where(array( 'upper' => array( Search::SEARCH_AND, $x ) ));
				}
				// l : Location
				if(($x = $this->request->uri->get('l')) && ($x = ac_bitmask($x))) {
					$search->where(array( 'job' => array( Search::SEARCH_AND, $x ) ));
				}
				// c : Custom
				if($this->request->uri->get('c')) {
					$search->where(array( 'custom' => 1 ));
				}
				// t : Type
				if(($x = $this->request->uri->getInt('t', false, 0, 11)) !== false) {
					$search->where(array( 'type' => $x ));
				}
				// v : Equipment Type
				if(($x = $this->request->uri->getInt('v', false)) !== false) {
					$search->where(array( 'view' => 1 ));
				}
				// lv & lv2 : Equip Level
				$x = $this->request->uri->getInt('lv', null, 0);
				$y = $this->request->uri->getInt('lv2', null, 0);
				if(($x || $y) && ($x = ac_between($x, $y))) {
					$search->where(array( 'equip_level_max' => $x ));
				}
				// lw & lw2 : Weapon Level
				$x = $this->request->uri->getInt('lw', null, 0, 4);
				$y = $this->request->uri->getInt('lw2', null, 0, 4);
				if(($x || $y) && ($x = ac_between($x, $y))) {
					$search->where(array( 'weapon_level' => $x ));
				}
				$search
					->order(array( 'id' => 'DESC' ))
				    ->limit(($current_page - 1) * self::DB_ITEMS_PER_PAGE, self::DB_ITEMS_PER_PAGE)
			        ->calcRows(true)
			        ->query();
				$rows = $search->rowsFound;
				$items = $search->results;
			}
			$pgn = new Pagination(App::user()->request->uri, ceil($rows / self::DB_ITEMS_PER_PAGE), $current_page);
			$tpl = new Template;
			$tpl->set('items', $items)
				->set('item_count', $rows)
				->set('paginator', $pgn)
				->set('page', $this);
			echo $tpl->render('ragnarok/item/database');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(1, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function sortMobDrops($x, $y)
	{
		return ($y['max_rate'] - $x['max_rate']) * 100;
	}

	public function view_action($id = null)
	{
		$this->theme->head->section = $this->title = __('ragnarok', 'viewing-item');
		try {
			if(!($item = $this->charmap->item((int)$id))) {
				$this->error(404);
				return;
			} else {
				$base_mob_url = $this->server->charMapUri($this->charmap->key())->url(array(
					'path' => array( 'mob' ),
					'action' => 'view',
					'arguments' => array( '' )
				));
				$who_drops = $this->charmap->whoDrops($item->id, 3);
				usort($who_drops, array($this, 'sortMobDrops'));
				foreach($who_drops as &$mob) {
					$mob['mob_url'] = $base_mob_url . $mob['id'];
				}
			}
			$tpl = new Template;
			$tpl->set('item', $item)
				->set('who_drops', $who_drops)
				->set('page', $this);
			echo $tpl->render('ragnarok/item/view');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			echo __('application', 'unexpected-error');
		}
	}

	public function shop_action($category = null)
	{
		$this->theme->head->section = $this->title = __('ragnarok', 'cash-shop');
		try {
			$current_page = $this->request->uri->getInt('page', 1, 1);
			$categories = $this->charmap->cashShopCategories();
			if($category !== null) {
				if(!in_array($category, $categories)) {
					$this->error(404);
				}
				$options['shop_category_id'] = (int)$category;
			}
			$options = array( 'cash_shop' => 1 );
			$items = $this->charmap->itemShopSearch(
				$options,
				array(
					array( AC_ORDER_ASC, 'shop_category_id' ),
					array( AC_ORDER_ASC, 'shop_order' )
				),
				array(($current_page - 1) * self::SHOP_ITEMS_PER_PAGE, self::SHOP_ITEMS_PER_PAGE),
				$rows
			);
			$base_url = $this->server->charMapUri($this->charmap->key())->url(array(
				'path'      => array( 'item' ),
				'action'    => 'shop',
				'arguments' => array( '' )
			));
			foreach($categories as $id => &$category) {
				$category['url'] = $base_url . $id;
			}
			$pgn = new Pagination(App::user()->request->uri, ceil($rows / self::SHOP_ITEMS_PER_PAGE), $current_page);
			$tpl = new Template;
			$tpl->set('items', $items)
				->set('item_count', $rows)
				->set('categories', $categories)
				->set('paginator', $pgn)
				->set('page', $this);
			echo $tpl->render('ragnarok/item/shop');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(1, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function cart_action()
	{
		$user = App::user();
		$this->response->status(302)->redirect(App::request()->previousUrl());
		try {
			$id     = $this->request->uri->getInt('id', false);
			$action = $this->request->uri->getString('x', 'add');
			$amount = $this->request->uri->getInt('a', 0, 0, Ragnarok::$shop_max_amount);
			$cart   = &$user->cart($this->charmap);
			if($action === 'clear') {
				$cart->clear();
				$user->addFlash('success', null, __('application', 'cart-clear'));
				return;
			}
			if(!$id || !($item = $this->charmap->item((int)$id)) || !$item->inCashShop) {
				return;
			}
			if($action === 'set') {
				$amount -= (isset($cart->items[$item->id]) ? $cart->items[$item->id]['amount'] : 0);
				if($amount < 0) {
					$action = 'remove';
					$amount = abs($amount);
				} else {
					$action = 'add';
				}
			}
			switch($action) {
				case 'remove':
					$cart->remove($item, $amount);
					if(!isset($cart->items[$item->id])) {
						$user->addFlash('success', null, __('application', 'cart-remove', htmlspecialchars($item->jpName)));
					} else {
						$user->addFlash('success', null, __('application', 'cart-remove-m', $amount, htmlspecialchars($item->jpName)));
					}
					return;
				case 'add':
					if(isset($cart->items[$item->id])) {
						$amount = min($cart->items[$item->id]['amount'] + $amount, Ragnarok::$shop_max_amount);
						$amount -= $cart->items[$item->id]['amount'];
					}
					if($amount === 0) {
						return;
					}
					$cart->add($item, $amount);
					$user->addFlash('success', null, __('application', 'cart-add-m', $amount, htmlspecialchars($item->jpName)));
					break;
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$user->addFlash('error', null, __('application', 'unexpected-error'));
		}
	}

	public function buy_action()
	{
		$user = App::user();
		$cart = $user->cart($this->charmap);
		if(!empty($cart->items) && ($account_id = $this->request->getInt('account_id', false)) !== false && ($key = $this->request->getString('cart_key')) && $key === $cart->key) {
			$this->response->status(302)->redirect(App::request()->previousUrl());
			$credits = $user->account->credits;
			foreach($cart->items as $item) {
				$credits -= $item['amount'] * $item['price'];
			}
			if($credits < 0) {
				$user->addFlash('warning', null, __('ragnarok', 'not-enough-credits'));
				return;
			}
			try {
				$account = $this->server->login->get($account_id);
				if(!$account || $account->owner !== $user->account->id) {
					$user->addFlash('warning', null, __('ragnarok', 'invalid-account'));
					return;
				}
				$dbh = App::connection();
				$dbh->beginTransaction();
				$user->account->update(array( 'credits' => $credits ));
				$this->charmap->cashShopPurchase($account_id, $cart);
				$dbh->commit();
				$cart->clear();
				$user->addFlash('success', null, __('ragnarok', 'purchase-complete'));
			} catch(\Exception $exception) {
				if(isset($dbh) && $dbh->inTransaction()) {
					$dbh->rollBack();
				}
				ErrorLog::logSql($exception);
				$user->addFlash('error', null, __('application', 'unexpected-error'));
			}
			return;
		}
		$this->theme->head->section = $this->title = __('ragnarok', 'checkout');
		try {
			if(empty($cart->items)) {
				$items = array();
			} else {
				$ids = array_keys($cart->items);
				array_unshift($ids, Search::SEARCH_IN);
				$items = $this->charmap->itemSearch()
					->where(array( 'id' => $ids ))
					->having(array( 'cash_shop' => 1 ))
					->query()
					->results;
			}
			$accounts = $this->server->login->getAccounts($user->account);
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(1, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
			return;
		}
		$tpl = new Template;
		$tpl->set('items', $items)
			->set('accounts', $accounts)
			->set('cart', $cart)
			->set('page', $this);
		echo $tpl->render('ragnarok/item/buy');
	}
}
