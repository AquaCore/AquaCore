<?php
namespace Page\Main\Ragnarok\Server;

use Aqua\Core\App;
use Aqua\Core\L10n;
use Aqua\Log\ErrorLog;
use Aqua\Ragnarok\Server\CharMap;
use Aqua\Site\Page;
use Aqua\SQL\Query;
use Aqua\SQL\Search;
use Aqua\UI\Form;
use Aqua\UI\Menu;
use Aqua\UI\Pagination;
use Aqua\UI\Search\Input;
use Aqua\UI\Search\Select;
use Aqua\UI\Template;

class Item
extends Page
{
	/**
	 * @var \Aqua\Ragnarok\Server\CharMap
	 */
	public $charmap;

	public static $shopItemsPerPage = 8;

	public function run()
	{
		$this->charmap = &App::$activeCharMapServer;
		if(!($this->charmap instanceof CharMap)) {
			$this->error(404);
			return;
		}
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

	public function validateItemTypeSearch(Select $select, \Aqua\UI\Search $frm, $value, $type) {
		if($frm->getInt('t') === $type) {
			return $select->_parse($value);
		} else {
			return false;
		}
	}

	public function index_action()
	{
		$this->theme->head->section = $this->title = __('ragnarok', 'item-db');
		try {
			$currentPage = $this->request->uri->getInt('page', 1, 1);
			$frm = new \Aqua\UI\Search(App::request(), $currentPage);
			$frm->order(array(
					'id'     => 'id',
			        'name'   => 'name',
			        'type'   => 'type',
			        'weight' => 'weight',
			        'buy'    => 'buying_price',
			        'sell'   => 'selling_price',
				))
				->limit(0, 4, 10, 5)
				->defaultOrder('id')
				->defaultLimit(10)
				->persist('itemDB');
			$itemTypes = L10n::rangeList('ragnarok-item-type',
			                             array( 0 ),
			                             range(2, 8),
			                             range(10, 12));
			$equipLocations = L10n::rangeList('ragnarok-equip-location', array(
					0x0001, 0x0002, 0x0004, 0x0008,
					0x0010, 0x0020, 0x0040, 0x0080,
					0x0100, 0x0200, 0x0400, 0x0800,
					0x1000, 0x2000,
					0x0101, 0x0201, 0x0300, 0x0301,
				    0x1400, 0x0C00, 0x1800, 0x1C00
				));
			$weaponTypes = L10n::rangeList('ragnarok-weapon-type', range(0, 8), range(10, 22));
			$ammoTypes = L10n::rangeList('ragnarok-ammo-type', range(0, 7));
			$jobs = L10n::rangeList('ragnarok-equip-job', array(
				0x0000001, 0x0000002, 0x0000004, 0x0000008,
				0x0000010, 0x0000020, 0x0000040, 0x0000080,
				0x0000100, 0x0000200, 0x0000400, 0x0000800,
				0x0001000,            0x0004000, 0x0008000,
				0x0010000, 0x0020000, 0x0040000, 0x0080000,
				           0x0200000, 0x0400000, 0x0800000,
				0x1000000, 0x2000000
			));
			$upper = L10n::rangeList('ragnarok-equip-upper', array( 0x01, 0x02, 0x04, 0x08 ));
			asort($itemTypes, SORT_STRING);
			asort($equipLocations, SORT_STRING);
			asort($weaponTypes, SORT_STRING);
			asort($ammoTypes, SORT_STRING);
			$itemTypes = array( '' => __('application', 'any') ) + $itemTypes;
			$equipLocations = array( '' => __('application', 'any') ) + $equipLocations;
			$weaponTypes = array( '' => __('application', 'any') ) + $weaponTypes;
			$ammoTypes = array( '' => __('application', 'any') ) + $ammoTypes;
			$frm->input('id')
				->setColumn('id')
				->searchType(Input::SEARCH_EXACT)
				->setLabel(__('ragnarok', 'item-id'))
				->type('number')
				->attr('min', 0);
			$frm->input('n')
				->setColumn('name')
				->setLabel(__('ragnarok', 'name'))
				->type('text');
			$frm->select('t')
				->setColumn('type')
				->setLabel(__('ragnarok', 'type'))
				->value($itemTypes);
			$frm->select('w')
				->setColumn('look')
				->setParser(array( $this, 'validateItemTypeSearch' ), array( 4 ))
				->setLabel(__('ragnarok', 'weapon-type'))
				->value($weaponTypes);
			$frm->select('loc')
				->setColumn('location')
				->searchType(Search::SEARCH_AND)
				->setParser(array( $this, 'validateItemTypeSearch' ), array( 5 ))
				->setLabel(__('ragnarok', 'equip-locations'))
				->value($equipLocations);
			$frm->select('ammo')
				->setColumn('look')
				->setParser(array( $this, 'validateItemTypeSearch' ), array( 10 ))
				->setLabel(__('ragnarok', 'ammo-type'))
				->value($ammoTypes);
			$frm->select('job')
				->setColumn('job')
				->searchType(Search::SEARCH_AND)
				->setLabel(__('ragnarok', 'applicable-jobs'))
				->multiple()
				->value($jobs);
			$frm->select('up')
				->setColumn('upper')
				->setLabel(__('ragnarok', 'upper'))
				->multiple()
				->value($upper)
				->setParser(function($field, $frm, $value) {
					$upper = 0;
					foreach($value as &$x) {
						$upper |= (int)$x;
					}
					return array( Search::SEARCH_AND, $upper, $upper );
				});
			$frm->range('atk')
				->setColumn('attack')
				->setLabel(__('ragnarok', 'attack'))
				->type('number')
				->attr('min', 0);
			$frm->range('def')
				->setColumn('defence')
				->setLabel(__('ragnarok', 'defence'))
				->type('number')
				->attr('min', 0);
			$frm->range('slt')
				->setColumn('slots')
				->setLabel(__('ragnarok', 'slots'))
				->type('number')
				->attr('min', 0);
			$frm->range('rng')
				->setColumn('range')
				->setLabel(__('ragnarok', 'range'))
				->type('number')
				->attr('min', 0);
			$frm->range('buy')
				->setColumn('buying_price')
				->setLabel(__('ragnarok', 'buy-price'))
				->type('number')
				->attr('min', 0);
			$frm->range('sell')
				->setColumn('selling_price')
				->setLabel(__('ragnarok', 'sell-price'))
				->type('number')
				->attr('min', 0);
			$frm->select('r')
				->setColumn('refineable')
				->setLabel(__('ragnarok', 'refinable'))
				->value(array(
					'' => __('application', 'any'),
					'1' => __('application', 'yes'),
					'0' => __('application', 'no'),
				));
			$frm->select('c')
				->setColumn('custom')
				->setLabel(__('ragnarok', 'custom'))
				->value(array(
					'' => __('application', 'any'),
					'1' => __('application', 'yes'),
					'0' => __('application', 'no'),
				));
			$search = $this->charmap->itemSearch();
			$frm->apply($search);
			$search->calcRows(true)->query();
			$pgn = new Pagination(App::request()->uri,
			                      ceil($search->rowsFound / $frm->getLimit()),
			                      $currentPage);
			$tpl = new Template;
			$tpl->set('items', $search->results)
				->set('item_count', $search->rowsFound)
				->set('paginator', $pgn)
				->set('search', $frm)
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
			$currentPage = $this->request->uri->getInt('page', 1, 1);
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
				->limit(($currentPage - 1) * self::$shopItemsPerPage, self::$shopItemsPerPage)
				->order(array( 'shop_order' => 'ASC' ))
				->query();
			$pgn = new Pagination(App::user()->request->uri, ceil($search->rowsFound / self::$shopItemsPerPage), $currentPage);
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
					->get('credits', 0);
				if(($credits - $cart->total) < 0) {
					$user->addFlash('warning', null, __('ragnarok', 'not-enough-credits'));
					$dbh->rollBack();
					return;
				}
				$sth = $dbh->prepare(sprintf('
				UPDATE %s
				SET _credits = _credits - ?
				WHERE id = ?
				', ac_table('users')));
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
