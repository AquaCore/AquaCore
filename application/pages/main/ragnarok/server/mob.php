<?php
namespace Page\Main\Ragnarok\Server;

use Aqua\Core\App;
use Aqua\Log\ErrorLog;
use Aqua\Site\Page;
use Aqua\UI\Form;
use Aqua\UI\Menu;
use Aqua\UI\Pagination;
use Aqua\UI\Template;

class Mob
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

	const DB_MOBS_PER_PAGE = 10;

	public function run()
	{
		$pgn = &$this;
		$this->attach('call_action', function() use (&$pgn) {
			$pgn->server  = App::registryGet('ac_active_ragnarok_server');
			$pgn->charmap = App::registryGet('ac_active_charmap_server');
			$navbar = &$pgn->theme->get('navbar', array());
			$uri = $pgn->server->charmapUri($pgn->charmap->key());
			if($pgn->server->charmapCount > 1) {
				$navbar[] = array( 'title' => $pgn->charmap->name, 'url' => $uri->url() );
			} else {
				$navbar[] = array( 'title' => __('navbar', 'server'), 'url' => $uri->url() );
			}
			$navbar[] = array( 'title' => __('navbar', 'mob-db'), 'url' => $uri->url(array( 'path' => array( 'mob' ) )) );
			$menu = new Menu;
			$menu->append('mob_db', array(
				'title' => __('ragnarok', 'mob-db'),
				'url' => $uri->url(array( 'path' => array( 'mob' ) ))
				));
			$pgn->theme->set('menu', $menu);
		});
	}

	public function index_action()
	{
		$this->theme->head->section = $this->title = __('ragnarok', 'mob-db');
		try {
			$current_page = $this->request->uri->getInt('page', 1, 1);
			$options = array();
			if($x = $this->request->uri->getInt('id', false)) {
				if($monsters = $this->charmap->mob($x)) {
					$monsters = array( $monsters );
					$rows = 1;
				} else {
					$monsters = array();
					$rows = 0;
				}
			} else {
				// n : Name
				if($x = $this->request->uri->getString('n', false)) {
					$x = addcslashes($x, '%_\\');
					$options['name'] = array( AC_SEARCH_LIKE, "%$x%" );
				}
				// m : Mode
				if(($x = $this->request->uri->get('j')) && ($x = ac_req_parse_search_bitmask($x))) {
					$options['mode'] = array( AC_SEARCH_AND, $x );
				}
				// c : Custom
				if($this->request->uri->get('c')) {
					$options['custom'] = 1;
				}
				// s : Size
				if(($x = $this->request->uri->getInt('s', false, 0, 2)) !== false) {
					$options['scale'] = (int)$x;
				}
				// r : Race
				if(($x = $this->request->uri->getInt('r', false, 0, 9)) !== false) {
					$options['race'] = (int)$x;
				}
				// e : Element
				if(($x = $this->request->uri->getInt('e', false, 0, 9)) !== false) {
					$options['element'] = (int)$x;
				}
				// el & el2 : Element Level
				$x = $this->request->uri->getInt('el', null, 1, 4);
				$y = $this->request->uri->getInt('el2', null, 1, 4);
				if(($x || $y) && ($x = ac_between($x, $y))) {
					$options['element_level'] = $x;
				}
				// lv & lv2 : Level
				$x = $this->request->uri->getInt('lv', null, 0);
				$y = $this->request->uri->getInt('lv2', null, 0);
				if(($x || $y) && ($x = ac_between($x, $y))) {
					$options['level'] = $x;
				}
				// be & be2 : Base Experience
				$x = $this->request->uri->getInt('be', null, 0);
				$y = $this->request->uri->getInt('be2', null, 0);
				if(($x || $y) && ($x = ac_between($x, $y))) {
					$options['base_exp'] = $x;
				}
				// je & je2 : Job Experience
				$x = $this->request->uri->getInt('je', null, 0);
				$y = $this->request->uri->getInt('je2', null, 0);
				if(($x || $y) && ($x = ac_between($x, $y))) {
					$options['job_exp'] = $x;
				}
				$monsters = $this->charmap->mobSearch(
					$options,
					array( AC_ORDER_ASC, 'id' ),
					array(($current_page - 1) * self::DB_MOBS_PER_PAGE, self::DB_MOBS_PER_PAGE),
					$rows
				);
			}
			$pgn = new Pagination(App::user()->request->uri, ceil($rows / self::DB_MOBS_PER_PAGE), $current_page);
			$tpl = new Template;
			$tpl->set('mobs', $monsters)
				->set('mob_count', $rows)
				->set('paginator', $pgn)
				->set('page', $this);
			echo $tpl->render('ragnarok/monster/database');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			echo __('application', 'unexpected-error');
		}
	}

	public function view_action($id = '')
	{
		$this->theme->head->section = $this->title = __('ragnarok', 'viewing-mob');
		try {
			if(!($mob = $this->charmap->mob((int)$id))) {
				$drops = array();
			} else {
				$drops = $this->charmap->mobDrops((int)$id);
				$base_url = $this->server->charMapUri($this->charmap->key())->url(array(
					'path'      => array( 'item' ),
					'action'    => 'view',
					'arguments' => array( '' )
				));
				if(isset($drops['card'])) {
					$drops['card']['url'] = $base_url . $drops['card']['id'];
				}
				if(isset($drops['normal'])) {
					foreach($drops['normal'] as &$drop) {
						$drop['url'] = $base_url . $drop['id'];
					}
				}
				if(isset($drops['mvp'])) {
					foreach($drops['mvp'] as &$drop) {
						$drop['url'] = $base_url . $drop['id'];
					}
				}
			}
			$tpl = new Template;
			$tpl->set('mob', $mob)
				->set('drops', $drops)
				->set('page', $this);
			echo $tpl->render('ragnarok/monster/view');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			echo __('application', 'unexpected-error');
		}
	}
}
