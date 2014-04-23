<?php
namespace Page\Main\Ragnarok\Server;

use Aqua\Core\App;
use Aqua\Ragnarok\Character;
use Aqua\Ragnarok\Server;
use Aqua\Site\Page;
use Aqua\SQL\Search;
use Aqua\UI\Form;
use Aqua\UI\Menu;
use Aqua\UI\Pagination;
use Aqua\UI\Template;
use Aqua\Log\ErrorLog;

class Char
extends Page
{
	/**
	 * @var \Aqua\Ragnarok\Server\CharMap
	 */
	public $charmap;
	/**
	 * @var \Aqua\Ragnarok\Character
	 */
	public $char;

	public static $itemsPerPage = 10;

	public function run()
	{
		$this->charmap = &App::$activeCharMapServer;
		$this->char = &App::$activeRagnarokCharacter;
		if(!$this->charmap || !$this->char) {
			$this->error(404);
			return;
		}
		$menu = new Menu;
		$base_url = $this->char->url(array(
			'action' => ''
		));
		$menu->append('char', array(
			'title' => __('ragnarok', 'character'),
			'url' => "{$base_url}index"
		))->append('options', array(
			'title' => __('profile', 'preferences'),
			'url' => "{$base_url}options"
		))->append('inventory', array(
			'title' => __('ragnarok', 'inventory'),
			'url' => "{$base_url}inventory"
		));
		if($this->charmap->getOption('show-cart', '') ||
		   in_array($this->char->class, array(
			   5, 10, 18, 4011, 4019, 4028, 4033, 4041,
			   4058, 4064, 4071, 4078, 4100, 4107
		   ))) {
			$menu->append('cart', array(
				'title' => __('ragnarok', 'cart'),
				'url'   => "{$base_url}cart"
			));
		}
		$this->theme->set('menu', $menu);
	}

	public function index_action()
	{
		try {
			$this->title = __('ragnarok', 'viewing-x-character', htmlspecialchars($this->char->name));
			$this->theme->head->section = __('ragnarok', 'character');
			$tpl = new Template;
			$tpl->set('char', $this->char)
				->set('page', $this);
			echo $tpl->render('ragnarok/character/view');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function inventory_action()
	{
		try {
			$this->title = __('ragnarok', 'x-inventory', htmlspecialchars($this->char->name));
			$this->theme->head->section = __('ragnarok', 'inventory');
			$current_page = $this->request->uri->getInt('page', 1, 1);
			$search = $this->charmap->inventorySearch()
				->calcRows(true)
				->where(array( 'char_id' => $this->char->id ))
				->limit(($current_page - 1) * self::$itemsPerPage, self::$itemsPerPage)
				->order(array( 'name' => 'ASC' ));
			if(($x = $this->request->uri->getString('s', false))) {
				$search->where(array( Search::SEARCH_LIKE, '%' . addcslashes($x, '%_\\') . '%' ));
			}
			if(($x = $this->request->uri->getString('t', false)) !== false) {
				do {
					switch($x) {
						case 'use':    $type = array( Search::SEARCH_IN, 1, 2, 11 ); break;
						case 'misc':   $type = array( Search::SEARCH_IN, 3, 8, 12 ); break;
						case 'weapon': $type = 4; break;
						case 'armor':  $type = 5; break;
						case 'egg':    $type = 7; break;
						case 'card':   $type = 6; break;
						case 'ammo':   $type = 10; break;
						default: break 2;
					}
					$search->where(array( 'type' => $type ));
				} while(0);
			}
			if($x) {
				$search->where(array( 'intentify' => 1 ));
			}
			$search->query();
			$pgn = new Pagination(App::request()->uri, ceil($search->rowsFound / self::$itemsPerPage), $current_page, 'page');
			$tpl = new Template;
			$tpl->set('inventory_size', $search->rowsFound)
				->set('inventory', $search->results)
				->set('paginator', $pgn)
				->set('page', $this);
			echo $tpl->render('ragnarok/character/inventory');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function cart_action()
	{
		if(!$this->charmap->getOption('show-cart', '') &&
		   !in_array($this->char->class, array(
			   5, 10, 18, 4011, 4019, 4028, 4033, 4041,
			   4058, 4064, 4071, 4078, 4100, 4107
		   ))) {
			$this->error(404);
			return;
		}
		$this->title = __('ragnarok', 'x-cart', htmlspecialchars($this->char->name));
		$this->theme->head->section = __('ragnarok', 'cart');
		try {
			$currentPage = $this->request->uri->getInt('page', 1, 1);
			$frm = new \Aqua\UI\Search(App::request());
			$frm->order(array(

			));
			$frm->input('name')
				->setColumn('name')
				->setLabel(__('ragnarok', 'name'))
				->type('text');
			$search = $this->charmap->cartSearch()
				->calcRows(true)
				->where(array( 'char_id' => $this->char->id ))
				->limit(($currentPage - 1) * self::$itemsPerPage, self::$itemsPerPage)
				->order(array( 'name' => 'ASC' ));
			if(($x = $this->request->uri->getString('s', false))) {
				$search->where(array( Search::SEARCH_LIKE, '%' . addcslashes($x, '%_\\') . '%' ));
			}
			if(($x = $this->request->uri->getString('t', false)) !== false) {
				do {
					switch($x) {
						case 'use':    $type = array( Search::SEARCH_IN, 1, 2, 11 ); break;
						case 'misc':   $type = array( Search::SEARCH_IN, 3, 8, 12 ); break;
						case 'weapon': $type = 4; break;
						case 'armor':  $type = 5; break;
						case 'egg':    $type = 7; break;
						case 'card':   $type = 6; break;
						case 'ammo':   $type = 10; break;
						default: break 2;
					}
					$search->where(array( 'type' => $type ));
				} while(0);
			}
			if($x) {
				$search->where(array( 'intentify' => 1 ));
			}
			$search->query();
			$pgn = new Pagination(App::request()->uri, ceil($search->rowsFound / self::$itemsPerPage), $currentPage, 'page');
			$tpl = new Template;
			$tpl->set('cart_size', $search->rowsFound)
				->set('cart', $search->results)
				->set('paginator', $pgn)
				->set('page', $this);
			echo $tpl->render('ragnarok/character/cart');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function options_action()
	{
		try {
			$frm = new Form($this->request);
			$frm->checkbox('hide_online')
				->value(array( '1' => '' ))
				->checked($this->char->CPOptions & Character::OPT_DISABLE_WHO_IS_ONLINE ? '1' : null)
				->setLabel(__('ragnarok', 'hide-whos-online'));
			$frm->checkbox('hide_map')
				->value(array( '1' => '' ))
				->checked($this->char->CPOptions & Character::OPT_DISABLE_MAP_WHO_IS_ONLINE ? '1' : null)
				->setLabel(__('ragnarok', 'hide-map-whos-online'));
			$frm->checkbox('hide_zeny')
				->value(array( '1' => '' ))
				->checked($this->char->CPOptions & Character::OPT_DISABLE_ZENY_LADDER ? '1' : null)
				->setLabel(__('ragnarok', 'hide-zeny'));
			$frm->token('ragnarok_edit_char');
			$frm->input('reset_look')
				->type('submit')
				->value(__('ragnarok', 'reset-look'));
			$frm->input('reset_pos')
				->type('submit')
				->value(__('ragnarok', 'reset-position'));
			$frm->submit();
			$frm->validate();
			if($frm->status !== Form::VALIDATION_SUCCESS) {
				$this->title = __('ragnarok', 'edit-char', htmlspecialchars($this->char->name));
				$this->theme->head->section = __('ragnarok', 'char-preferences');
				$tpl = new Template;
				$tpl->set('form', $frm)
					->set('page', $this);
				echo $tpl->render('ragnarok/character/edit');
				return;
			}
			if($this->request->getString('reset_pos')) {
				if($this->char->online) {
					App::user()->addFlash('warning', null, __('ragnarok', 'reset-pos-online'));
				} else if(($pattern = $this->charmap->getOption('map-restriction')) &&
				          preg_match($pattern, $this->char->lastMap)) {
					App::user()->addFlash('warning', null, __('ragnarok', 'reset-map-restriction', htmlspecialchars($this->char->lastMap)));
				} else if($this->char->update(array(
						'map' => $this->charmap->getOption('default-map'),
						'x' => $this->charmap->getOption('default-map-x'),
						'y' => $this->charmap->getOption('default-map-y')
					))) {
					App::user()->addFlash('success', null, __('ragnarok', 'pos-reset'));
				}
			} else if($this->request->getString('reset_look')) {
				if($this->char->online) {
					App::user()->addFlash('warning', null, __('ragnarok', 'reset-look-online'));
				} else if($this->char->update(array(
						'hair' => 0,
						'hair_color' => 0,
						'clothes_color' => 0,
						'weapon' => 0,
						'shield' => 0,
						'robe' => 0,
						'head_top' => 0,
						'head_mid' => 0,
						'head_bottom' => 0
					))) {
					App::user()->addFlash('success', null, __('ragnarok', 'look-reset'));
				}
			} else {
				$opt = $this->char->CPOptions;
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
				if($opt !== $this->char->CPOptions &&
				   $this->char->update(array( 'cp_options' => $opt ))) {
					App::user()->addFlash('success', null, __('ragnarok', 'char-updated'));
				}
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}
}
