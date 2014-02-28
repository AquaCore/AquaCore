<?php
namespace Page\Main\Ragnarok\Server;

use Aqua\Core\App;
use Aqua\Log\ErrorLog;
use Aqua\Ragnarok\Cart;
use Aqua\Ragnarok\Ragnarok;
use Aqua\Ragnarok\Server\Login;
use Aqua\Site\Page;
use Aqua\SQL\Query;
use Aqua\SQL\Search;
use Aqua\UI\Form;
use Aqua\UI\Menu;
use Aqua\UI\Pagination;
use Aqua\UI\Template;
use PHPMailer\POP3;

class Item
extends Page
{
	/**
	 * @var \Aqua\Ragnarok\Server\CharMap
	 */
	public $charmap;

	public static $dbItemsPerPage = 10;
	public static $shopItemsPerPage = 8;

	public function run()
	{
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
			$this->theme->set('cart', App::user()->cart($this->charmap));
		}
	}

	public function index_action()
	{
		$this->theme->head->section = $this->title = __('ragnarok', 'item-db');
		try {
			$current_page = $this->request->uri->getInt('page', 1, 1);
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
				    ->limit(($current_page - 1) * self::$dbItemsPerPage, self::$dbItemsPerPage)
			        ->calcRows(true)
					->order(array( 'id' => 'ASC' ))
			        ->query();
				$rows = $search->rowsFound;
				$items = $search->results;
			}
			$pgn = new Pagination(App::user()->request->uri, ceil($rows / self::$dbItemsPerPage), $current_page);
			$tpl = new Template;
			$tpl->set('items', $items)
				->set('item_count', $rows)
				->set('paginator', $pgn)
				->set('page', $this);
			echo $tpl->render('ragnarok/item/database');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
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
				$base_mob_url = $this->charmap->url(array(
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
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function shop_action($slug = null)
	{
		$this->theme->head->section = $this->title = __('ragnarok', 'cash-shop');
		try {
			$current_page = $this->request->uri->getInt('page', 1, 1);
			$categories = $this->charmap->shopCategorySearch()
				->order(array( 'order' => 'ASC' ))
				->query()
				->results;
			if(!$slug) {
				$search = $this->charmap->itemShopSearch();
			} else {
				do {
					foreach($categories as $category) {
						if(strcasecmp($category->slug, $slug) === 0) {
							$search = $category->search();
							break 2;
						}
					}
					$this->error(404);
					return;
				} while(0);
			}
			$search->calcRows(true)
				->limit(($current_page - 1) * self::$shopItemsPerPage, self::$shopItemsPerPage)
				->order(array( 'shop_order' => 'ASC' ))
				->query();
			$pgn = new Pagination(App::user()->request->uri, ceil($search->rowsFound / self::$shopItemsPerPage), $current_page);
			$tpl = new Template;
			$tpl->set('items', $search->results)
				->set('item_count', $search->rowsFound)
				->set('categories', $categories)
				->set('paginator', $pgn)
				->set('page', $this);
			echo $tpl->render('ragnarok/item/shop');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function cart_action()
	{
		$user = App::user();
		$this->response->status(302);
		if(($return = $this->request->uri->get('r')) &&
		   ($return = @base64_decode($return)) &&
		   parse_url($return, PHP_URL_HOST) === \Aqua\DOMAIN) {
			$this->response->redirect($return);
		} else {
			$this->response->redirect(App::request()->previousUrl());
		}
		try {
			$max    = App::settings()->get('ragnarok')->get('cash_shop_max_amount', 99);
			$id     = $this->request->uri->getInt('id', false);
			$action = $this->request->uri->getString('x', 'add');
			$amount = $this->request->uri->getInt('a', 0, 0, $max);
			$cart = $user->cart($this->charmap);
			if($action === 'clear') {
				$cart->clear();
				$user->addFlash('success', null, __('ragnarok', 'cart-clear'));
				return;
			}
			if(!$id || !($item = $this->charmap->item((int)$id)) || !$item->inCashShop) {
				return;
			}
			if($action === 'set') {
				$amount -= $cart->count($item->id);
				if($amount <= 0) {
					$action = 'remove';
					$amount = abs($amount);
				} else {
					$action = 'add';
				}
			}
			switch($action) {
				case 'remove':
					$cart->remove($item->id, $amount);
					if(!$cart->hasItem($item->id)) {
						$user->addFlash('success', null, __('ragnarok', 'cart-remove', htmlspecialchars($item->jpName)));
					} else {
						$user->addFlash('success', null, __('ragnarok', 'cart-remove-m', $amount, htmlspecialchars($item->jpName)));
					}
					return;
				case 'add':
					if($cart->hasItem($item->id)) {
						$amount = min($cart->count($item->id) + $amount, $max);
						$amount -= $cart->count($item->id);
					}
					if($amount === 0) {
						return;
					}
					$cart->add($item->id, $amount);
					$user->addFlash('success', null, __('ragnarok', 'cart-add-m', $amount, htmlspecialchars($item->jpName)));
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
		if(!empty($cart->items) && $this->request->method === 'POST' &&
		   ($account_id = $this->request->getInt('account_id', false)) !== false) {
			$this->response->status(302)->redirect(App::request()->uri->url());
			$inTransaction = false;
			try {
				$account = $this->charmap->server->login->get($account_id);
				if(!$account || $account->owner !== $user->account->id) {
					$user->addFlash('warning', null, __('ragnarok', 'invalid-account'));
					return;
				}
				$dbh = App::connection();
				$dbh->beginTransaction();
				$inTransaction = true;
				$credits = Query::select($dbh)
					->columns(array( 'credits' => '_credits' ))
					->setColumnType(array( 'credits' => 'integer' ))
					->where(array( 'id' => $user->account->id ))
					->from(ac_table('users'))
					->forUpdate(true)
					->query()
					->results[0]['credits'];
				if(($credits - $cart->total) < 0) {
					$user->addFlash('warning', null, __('ragnarok', 'not-enough-credits'));
					$dbh->rollBack();
					return;
				}
				$tbl = ac_table('users');
				$sth = $dbh->prepare("
				UPDATE `$tbl`
				SET _credits = _credits - ?
				WHERE id = ?
				");
				$sth->bindValue(1, $cart->total, \PDO::PARAM_INT);
				$sth->bindValue(2, $user->account->id, \PDO::PARAM_INT);
				$sth->execute();
				$cart->checkout($account);
				$dbh->commit();
				$cart->clear();
				$user->addFlash('success', null, __('ragnarok', 'purchase-complete'));
			} catch(\Exception $exception) {
				if(isset($dbh) && $inTransaction) {
					$dbh->rollBack();
				}
				ErrorLog::logSql($exception);
				$user->addFlash('error', null, __('application', 'unexpected-error'));
			}
			return;
		}
		$this->theme->head->section = $this->title = __('ragnarok', 'checkout');
		try {
			$cart->update();
			$accounts = $this->charmap->server->login->getAccounts($user->account);
			$tpl = new Template;
			$tpl->set('accounts', $accounts)
			    ->set('cart', $cart)
			    ->set('page', $this);
			echo $tpl->render('ragnarok/item/buy');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
			return;
		}
	}
}
