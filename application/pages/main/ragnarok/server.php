<?php
namespace Page\Main\Ragnarok;

use Aqua\Core\App;
use Aqua\Ragnarok\Character;
use Aqua\Site\Page;
use Aqua\Ragnarok\Server as _Server;
use Aqua\Ragnarok\Server\Login;
use Aqua\Ragnarok\Server\CharMap;
use Aqua\SQL\Search;
use Aqua\UI\Menu;
use Aqua\UI\Pagination;
use Aqua\UI\Template;
use Aqua\Log\ErrorLog;

class Server
extends Page
{

	/**
	 * @var \Aqua\Ragnarok\Server\CharMap
	 */
	public $charmap;

	const CHARS_PER_PAGE = 10;

	public function run()
	{
		$this->charmap = &App::$activeCharMapServer;
		if(!($this->charmap instanceof CharMap)) {
			return;
		}
		$pgn = &$this;
		$this->attach('call_action', function() use(&$pgn) {
			$menu = new Menu;
			$base_url = $pgn->charmap->url(array( 'action' => '' ));
			$menu->append('item db', array(
				'title' => __('ragnarok', 'server-info'),
				'url'   => "{$base_url}info"
				))->append('cash shop', array(
				'title' => __('ragnarok', 'whos-online'),
				'url'   => "{$base_url}online"
				))
			;
			$pgn->theme->set('menu', $menu);
		});
	}

	public function index_action()
	{
		$this->response->status(301)->redirect($this->charmap->url(array( 'action' => 'info' )));
		return;
	}

	public function info_action()
	{
		$this->title = __('ragnarok', 'x-server-info', htmlspecialchars($this->charmap->name));
		$this->theme->head->section = __('ragnarok', 'server-info');
		try {
			$tpl = new Template;
			$tpl->set('class_population', $this->charmap->fetchCache('class_population'))
				->set('homunculus_population', $this->charmap->fetchCache('homunculus_population'))
				->set('characters', $this->charmap->fetchCache('char_count'))
				->set('guilds', $this->charmap->fetchCache('guild_count'))
				->set('parties', $this->charmap->fetchCache('party_count'))
				->set('homunculus', $this->charmap->fetchCache('homunculus_count'))
				->set('online', $this->charmap->fetchCache('online'))
				->set('accounts', $this->charmap->server->login->fetchCache('count'))
				->set('all_time_peak', $this->charmap->fetchCache('all_time_player_peak'))
				->set('this_month_peak', $this->charmap->fetchCache('this_month_player_peak'))
				->set('page', $this);
			echo $tpl->render('ragnarok/server/info');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(1, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function online_action()
	{
		try {
			if($this->charmap->woe()) {
				$this->error(503, __('http-error', 503), __('ragnarok', 'woe-disabled'));
				return;
			}
			$current_page = $this->request->uri->getInt('page', 1, 1);
			$search = $this->charmap->charSearch()
				->calcRows(true)
				->limit(($current_page - 1) * self::CHARS_PER_PAGE, self::CHARS_PER_PAGE)
				->where(array(
					'online' => 1,
				    'cp_options' => array( Search::SEARCH_DIFFERENT | Search::SEARCH_AND, Character::OPT_DISABLE_WHO_IS_ONLINE )
		        ));
			if(($x = $this->request->uri->getString('m', false)) !== false) {
				$search->where(array( 'last_map' => array( Search::SEARCH_LIKE, '%' . addcslashes($x, '%_\\') . '%' ) ));
			}
			if(($x = $this->request->uri->getString('c', false)) !== false) {
				$search->where(array( 'name' => array( Search::SEARCH_LIKE, '%' . addcslashes($x, '%_\\') . '%' ) ));
			}
			$search->query();
			$this->theme->head->section = $this->title = __('ragnarok', 'whos-online');
			$pgn = new Pagination(App::request()->uri, ceil($search->rowsFound / self::CHARS_PER_PAGE), $current_page, 'page');
			$tpl = new Template;
			$tpl->set('characters',   $search->results);
			$tpl->set('online_chars', $search->rowsFound);
			$tpl->set('paginator',    $pgn);
			$tpl->set('page',         $this);
			echo $tpl->render('ragnarok/server/online');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
			return;
		}
	}
}
