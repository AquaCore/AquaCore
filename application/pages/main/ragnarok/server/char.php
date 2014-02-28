<?php
namespace Page\Main\Ragnarok\Server;

use Aqua\Core\App;
use Aqua\Ragnarok\Character;
use Aqua\Ragnarok\Server;
use Aqua\Ragnarok\Server\CharMap;
use Aqua\Site\Page;
use Aqua\UI\Menu;
use Aqua\UI\Pagination;
use Aqua\UI\Template;
use Aqua\Log\ErrorLog;

class Char
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

	/**
	 * @var \Aqua\Ragnarok\Character
	 */
	public $char;

	const ITEMS_PER_PAGE = 10;

	public function run()
	{
		$server = App::registryGet('ac_active_ragnarok_server');
		$charmap = App::registryGet('ac_active_charmap_server');
		$char_name_url = App::settings()->get('ragnarok')->get('char_name_url', false);
		if(($server instanceof Server) && ($charmap instanceof CharMap) && ($name = $this->request->uri->getString('ac_ragnarok_character'))) {
			if($char_name_url) {
				$char = $charmap->character($name, 'name');
			} else {
				$char = $charmap->character((int)$name, 'id');
			}
			if(!$char) {
				$this->dispatcher->triggerError(404);
				return;
			}
		} else {
			$this->dispatcher->triggerError(404);
			return;
		}
		$pgn = &$this;
		$this->attach('call_action', function() use (&$pgn, $server, $charmap, $char, $char_name_url) {
			$pgn->theme->remove('navbar_alt');
			$pgn->response->setHeader('Cache-Control', 'no-store, co-cache, must-revalidate, max-age=0');
			$pgn->response->setHeader('Expires', time() - 1);
			$pgn->server = $server;
			$pgn->charmap = $charmap;
			$pgn->char = $char;
			$base_url = $pgn->server->charMapUri($pgn->charmap->key())->url(array(
				'path' => array( 'c', ($char_name_url ? $pgn->char->name : $pgn->char->id) ),
				'action' => ''
			));
			$menu = new Menu;
			$menu->append('char', array(
				'title' => __('ragnarok', 'character'),
				'url' => "{$base_url}index"
				))->append('options', array(
				'title' => __('application', 'preferences'),
				'url' => "{$base_url}options"
				))->append('inventory', array(
				'title' => __('ragnarok', 'inventory'),
				'url' => "{$base_url}inventory"
				))
			;
			switch($pgn->char->class) {
				case 5:
				case 10:
				case 18:
				case 4011:
				case 4019:
				case 4028:
				case 4033:
				case 4041:
				case 4058:
				case 4064:
				case 4071:
				case 4078:
				case 4100:
				case 4107:
					$menu->append('cart', array(
						'title' => __('ragnarok', 'cart'),
						'url'   => "{$base_url}cart"
					));
			}
			$pgn->theme->set('menu', $menu);
		});
	}

	public function index_action()
	{
		$this->title = __('ragnarok', 'viewing-x-character', htmlspecialchars($this->char->name));
		$this->theme->head->section = __('ragnarok', 'character');
		$tpl = new Template;
		$tpl->set('char', $this->char)
			->set('page', $this);
		echo $tpl->render('ragnarok/character/view');
	}

	public function inventory_action()
	{
		$this->title = __('ragnarok', 'x-inventory', htmlspecialchars($this->char->name));
		$this->theme->head->section = __('ragnarok', 'inventory');
		try {
			$current_page = $this->request->uri->getInt('page', 1, 1);
			$options = array( 'char_id' => $this->char->id );
			if(($x = $this->request->uri->getString('s', false))) {
				$x = addcslashes($x, '%_\\');
				$options['name'] = array( AC_SEARCH_LIKE, "%$x%" );
			}
			if(($x = $this->request->uri->getString('t', false)) !== false) {
				switch($x) {
					case 'use':    $options['type'] = array( AC_SEARCH_IN, 1, 2, 11 ); break;
					case 'misc':   $options['type'] = array( AC_SEARCH_IN, 3, 8, 12 ); break;
					case 'weapon': $options['type'] = 4; break;
					case 'armor':  $options['type'] = 5; break;
					case 'egg':    $options['type'] = 7; break;
					case 'card':   $options['type'] = 6; break;
					case 'ammo':   $options['type'] = 10; break;
				}
			}
			if(!empty($options)) $options['identify'] = 1;
			$inventory = $this->charmap->inventorySearch(
				$options,
				array( AC_ORDER_ASC, 'name' ),
				array(($current_page - 1) * self::ITEMS_PER_PAGE, self::ITEMS_PER_PAGE),
				$rows
			);
			$pgn = new Pagination(App::user()->request->uri, ceil($rows / self::ITEMS_PER_PAGE), $current_page);
			$tpl = new Template;
			$tpl->set('inventory_size', $rows)
				->set('inventory', $inventory)
				->set('paginator', $pgn)
				->set('page', $this);
			echo $tpl->render('ragnarok/character/inventory');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(1, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function cart_action()
	{
		switch($this->char->class) {
			case 5:
			case 10:
			case 18:
			case 4011:
			case 4019:
			case 4028:
			case 4033:
			case 4041:
			case 4058:
			case 4064:
			case 4071:
			case 4078:
			case 4100:
			case 4107:
				break;
			default:
				$this->response->status(302)->redirect(App::request()->previousUrl());
				return;
		}
		$this->title = __('ragnarok', 'x-cart', htmlspecialchars($this->char->name));
		$this->theme->head->section = __('ragnarok', 'cart');
		try {
			$current_page = $this->request->uri->getInt('page', 1, 1);
			$options = array( 'char_id' => $this->char->id );
			if(($x = $this->request->uri->getString('s', false))) {
				$x = addcslashes($x, '%_\\');
				$options['name'] = array( AC_SEARCH_LIKE, "%$x%" );
			}
			if(($x = $this->request->uri->getString('t', false)) !== false) {
				switch($x) {
					case 'use':    $options['type'] = array( AC_SEARCH_IN, 1, 2, 11 ); break;
					case 'misc':   $options['type'] = array( AC_SEARCH_IN, 3, 8, 12 ); break;
					case 'weapon': $options['type'] = 4; break;
					case 'armor':  $options['type'] = 5; break;
					case 'egg':    $options['type'] = 7; break;
					case 'card':   $options['type'] = 6; break;
					case 'ammo':   $options['type'] = 10; break;
				}
			}
			if(!empty($options)) $options['identify'] = 1;
			$cart = $this->charmap->cartSearch(
				$options,
				array( AC_ORDER_ASC, 'name' ),
				array(($current_page - 1) * self::ITEMS_PER_PAGE, self::ITEMS_PER_PAGE),
				$rows
			);
			$pgn = new Pagination(App::user()->request->uri, ceil($rows / self::ITEMS_PER_PAGE), $current_page);
			$tpl = new Template;
			$tpl->set('cart_size', $rows)
				->set('cart', $cart)
				->set('paginator', $pgn)
				->set('page', $this);
			echo $tpl->render('ragnarok/character/cart');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(1, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function options_action()
	{
		if($this->request->getInt('edit_char')) {
			$this->response->status(302)->redirect(App::request()->uri->url());
			try {
				$opt = $this->char->CPOptions;
				$options = array();
				if($this->request->getInt('hide_online')) {
					$opt |= Character::OPT_DISABLE_WHO_IS_ONLINE;
				} else {
					$opt &= ~Character::OPT_DISABLE_WHO_IS_ONLINE;
				}
				if($this->request->getInt('hide_map')) {
					$opt |= Character::OPT_DISABLE_MAP_WHO_IS_ONLINE;
				} else {
					$opt &= ~Character::OPT_DISABLE_MAP_WHO_IS_ONLINE;
				}
				if($this->request->getInt('hide_zeny')) {
					$opt |= Character::OPT_DISABLE_ZENY_LADDER;
				} else {
					$opt &= ~Character::OPT_DISABLE_ZENY_LADDER;
				}
				if($opt !== $this->char->CPOptions) {
					$options['cp_option'] = $opt;
				}
				if($this->request->getInt('reset_look')) {
					if($this->char->online) {
						App::user()->addFlash('warning', null, __('ragnarok', 'reset-look-online'));
					} else {
						$options+= array(
							'hair' => 0,
							'hair_color' => 0,
							'clothes_color' => 0,
							'weapon' => 0,
							'shield' => 0,
							'robe' => 0,
							'head_top' => 0,
							'head_mid' => 0,
							'head_bottom' => 0
						);
						App::user()->addFlash('success', null, __('ragnarok', 'look-reset'));
					}
				}
				if($this->request->getInt('reset_position')) do {
					if($this->char->online) {
						App::user()->addFlash('warning', null, __('ragnarok', 'reset-pos-online'));
						break;
					}
					foreach($this->charmap->positionRestrictions as &$regex) {
						if(preg_match($regex, $this->char->lastMap)) {
							App::user()->addFlash('warning', null, __('ragnarok', 'reset-map-restriction', htmlspecialchars($this->char->lastMap)));
							break;
						}
					}
					$options['map'] = $this->charmap->positionMap;
					$options['x_coordinate'] = $this->charmap->positionX;
					$options['y_coordinate'] = $this->charmap->positionY;
					App::user()->addFlash('success', null, __('ragnarok', 'pos-reset'));
				} while(0);
				if(!empty($options)) {
					$this->charmap->updateChar($this->char->id, $options);
				}
			} catch(\Exception $exception) {
				ErrorLog::logSql($exception);
				App::user()->addFlash('error', null, __('application', 'unexpected-error'));
			}
			return;
		}
		$this->title = __('ragnarok', 'edit-char', htmlspecialchars($this->char->name));
		$this->theme->head->section = __('ragnarok', 'char-preferences');
		$tpl = new Template;
		$tpl->set('page', $this);
		echo $tpl->render('ragnarok/character/edit');
	}
}
