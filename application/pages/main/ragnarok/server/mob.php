<?php
namespace Page\Main\Ragnarok\Server;

use Aqua\Core\App;
use Aqua\Core\L10n;
use Aqua\Log\ErrorLog;
use Aqua\Ragnarok\Server\CharMap;
use Aqua\Site\Page;
use Aqua\SQL\Search;
use Aqua\UI\Form;
use Aqua\UI\Menu;
use Aqua\UI\Pagination;
use Aqua\UI\Search\Input;
use Aqua\UI\Template;

class Mob
extends Page
{
	/**
	 * @var \Aqua\Ragnarok\Server\CharMap
	 */
	public $charmap;

	public function run()
	{
		$this->charmap = &App::$activeCharMapServer;
		if(!($this->charmap instanceof CharMap)) {
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
		try {
			$this->theme->head->section = $this->title = __('ragnarok', 'mob-db');
			$currentPage = $this->request->uri->getInt('page', 1, 1);
			$frm = new \Aqua\UI\Search(App::request(), $currentPage);
			$frm->order(array(
					'id'      => 'id',
			        'name'    => 'name',
			        'lvl'     => 'level',
			        'bxp'     => 'base_exp',
			        'jxp'     => 'job_exp',
			        'race'    => 'race',
			        'element' => 'element',
			        'size'    => 'scale',
				))
				->limit(0, 4, 10, 5)
				->defaultOrder('id')
				->defaultLimit(10)
				->persist('mobDB');
			$races = L10n::rangeList('ragnarok-race', range(0, 9));
			$elements = L10n::rangeList('ragnarok-element', range(0, 9));
			asort($races);
			asort($elements);
			$elements = array( '' => __('application', 'any') ) + $elements;
			$races    = array( '' => __('application', 'any') ) + $races;
			$modes    = array(
				0x0001,
			    0x0002,
			    0x0004,
			    0x0008,
			    0x0010 | 0x0200,
			    0x0020,
			    0x0040,
			    0x0080,
			    0x0100,
			    0x0400,
			    0x0800,
			    0x1000 | 0x2000,
			    0x4000
			);
			$frm->input('id')
				->setColumn('id')
				->searchType(Input::SEARCH_EXACT)
				->setLabel(__('ragnarok', 'mob-id'))
				->type('number')
				->attr('min', 0);
			$frm->input('n')
				->setColumn('name')
				->setLabel(__('ragnarok', 'name'))
				->type('text');
			$frm->select('size')
				->setColumn('scale')
				->setLabel(__('ragnarok', 'size'))
				->value(array(
					'' => __('application', 'any'),
				    '0' => __('ragnarok-size', 0),
				    '1' => __('ragnarok-size', 1),
				    '2' => __('ragnarok-size', 2),
				));
			$frm->select('race')
				->setColumn('race')
				->setLabel(__('ragnarok', 'race'))
				->value($races);
			$frm->select('el')
				->setColumn('el')
				->setLabel(__('ragnarok', 'element'))
				->value($elements);
			$frm->range('lv')
				->setColumn('level')
				->setLabel(__('ragnarok', 'level'))
				->type('number')
				->attr('min', 0);
			$frm->range('bxp')
				->setColumn('base_exp')
				->setLabel(__('ragnarok', 'base-exp'))
				->type('number')
				->attr('min', 0);
			$frm->range('jxp')
				->setColumn('job_exp')
				->setLabel(__('ragnarok', 'job-exp'))
				->type('number')
				->attr('min', 0);
			$frm->range('ar')
				->setColumn('attack_range')
				->setLabel(__('ragnarok', 'attack-range'))
				->type('number')
				->attr('min', 0);
			$frm->range('sr')
				->setColumn('skill_range')
				->setLabel(__('ragnarok', 'spell-range'))
				->type('number')
				->attr('min', 0);
			$include = 0;
			$exclude = 0;
			foreach($modes as $key => $mode) {
				$frm->select("m$key")
					->setLabel(__('ragnarok-mob-mode', $mode))
					->value(array(
						'' => __('application', 'any'),
					    '1' => __('application', 'yes'),
					    '0' => __('application', 'no')
					));
				if(($val = $frm->getInt("m$key", null)) !== null) {
					if($val) {
						$include |= $mode;
					} else {
						$exclude |= $mode;
					}
				}
			}
			$search = $this->charmap->mobSearch();
			$frm->apply($search);
			if($include) {
				$search->where(array( array( 'mode' => array( Search::SEARCH_AND, $include, $include ) ) ));
			}
			if($exclude) {
				$search->where(array( array( 'mode' => array( Search::SEARCH_AND | Search::SEARCH_DIFFERENT, $exclude ) ) ));
			}
			$search->calcRows(true)->query();
			$pgn = new Pagination(App::user()->request->uri,
			                      ceil($search->rowsFound / $frm->getLimit()),
			                      $currentPage);
			$tpl = new Template;
			$tpl->set('mobs', $search->results)
				->set('mob_count', $search->rowsFound)
				->set('paginator', $pgn)
				->set('search', $frm)
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
