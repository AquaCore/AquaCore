<?php
namespace Page\Main\Ragnarok\Server;

use Aqua\Core\App;
use Aqua\Log\ErrorLog;
use Aqua\Site\Page;
use Aqua\SQL\Search;
use Aqua\UI\Form;
use Aqua\UI\Menu;
use Aqua\UI\Pagination;
use Aqua\UI\Template;

class Mob
extends Page
{
	/**
	 * @var \Aqua\Ragnarok\Server\CharMap
	 */
	public $charmap;

	/**
	 * @var int
	 */
	public static $dbMobsPerPage = 10;

	public function run()
	{
		$this->charmap = &App::$activeCharMapServer;
		if(!$this->charmap) {
			$this->error(404);
			return;
		}
		$menu = new Menu;
		$menu->append('mob_db', array(
			'title' => __('ragnarok', 'mob-db'),
			'url' => $this->charmap->url(array( 'path' => array( 'mob' ) ))
		));
		$this->theme->set('menu', $menu);
	}

	public function index_action()
	{
		$this->theme->head->section = $this->title = __('ragnarok', 'mob-db');
		try {
			$current_page = $this->request->uri->getInt('page', 1, 1);
			if($x = $this->request->uri->getInt('id', false)) {
				if($monsters = $this->charmap->mob($x)) {
					$monsters = array( $monsters );
					$rows = 1;
				} else {
					$monsters = array();
					$rows = 0;
				}
			} else {
				$search = $this->charmap->mobSearch()
					->calcRows(true)
					->limit(($current_page - 1) * self::$dbMobsPerPage, self::$dbMobsPerPage)
					->order(array( 'id' => 'ASC' ));
				// n : Name
				if($x = $this->request->uri->getString('n', false)) {
					$search->where(array( 'name' => array( Search::SEARCH_LIKE, '%' . addcslashes($x, '%_\\') . '%' ) ));
				}
				// m : Mode
				if(($x = $this->request->uri->get('j')) && ($x = ac_bitmask($x))) {
					$search->where(array( 'mode' => array( Search::SEARCH_AND, $x ) ));
				}
				// c : Custom
				if($this->request->uri->get('c')) {
					$search->where(array( 'custom' => 1 ));
				}
				// s : Size
				if(($x = $this->request->uri->getInt('s', false, 0, 2)) !== false) {
					$search->where(array( 'scale' => $x ));
				}
				// r : Race
				if(($x = $this->request->uri->getInt('r', false, 0, 9)) !== false) {
					$search->where(array( 'race' => $x ));
				}
				// e : Element
				if(($x = $this->request->uri->getInt('e', false, 0, 9)) !== false) {
					$search->where(array( 'element' => $x ));
				}
				// el & el2 : Element Level
				$x = $this->request->uri->getInt('el', null, 1, 4);
				$y = $this->request->uri->getInt('el2', null, 1, 4);
				if(($x || $y) && ($x = ac_between($x, $y))) {
					$search->where(array( 'element_level' => $x ));
				}
				// lv & lv2 : Level
				$x = $this->request->uri->getInt('lv', null, 0);
				$y = $this->request->uri->getInt('lv2', null, 0);
				if(($x || $y) && ($x = ac_between($x, $y))) {
					$search->where(array( 'level' => $x ));
				}
				// be & be2 : Base Experience
				$x = $this->request->uri->getInt('be', null, 0);
				$y = $this->request->uri->getInt('be2', null, 0);
				if(($x || $y) && ($x = ac_between($x, $y))) {
					$search->where(array( 'base_experience' => $x ));
				}
				// je & je2 : Job Experience
				$x = $this->request->uri->getInt('je', null, 0);
				$y = $this->request->uri->getInt('je2', null, 0);
				if(($x || $y) && ($x = ac_between($x, $y))) {
					$search->where(array( 'job_experience' => $x ));
				}
				$search->query();
				$monsters = $search->results;
				$rows     = $search->rowsFound;
			}
			$pgn = new Pagination(App::user()->request->uri, ceil($rows / self::$dbMobsPerPage), $current_page);
			$tpl = new Template;
			$tpl->set('mobs', $monsters)
				->set('mob_count', $rows)
				->set('paginator', $pgn)
				->set('page', $this);
			echo $tpl->render('ragnarok/monster/database');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function view_action($id = '')
	{
		$this->theme->head->section = $this->title = __('ragnarok', 'viewing-mob');
		try {
			if(!$id || !($mob = $this->charmap->mob((int)$id))) {
				$this->error(404);
				return;
			}
			$drops = $this->charmap->mobDrops((int)$id);
			$base_url = $this->charmap->url(array(
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
			$tpl = new Template;
			$tpl->set('mob', $mob)
				->set('drops', $drops)
				->set('page', $this);
			echo $tpl->render('ragnarok/monster/view');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}
}
