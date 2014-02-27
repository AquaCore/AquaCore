<?php
namespace Page\Main\Ragnarok;

use Aqua\Core\App;
use Aqua\Ragnarok\Character;
use Aqua\Site\Page;
use Aqua\Ragnarok\Server as _Server;
use Aqua\Ragnarok\Server\Login;
use Aqua\Ragnarok\Server\CharMap;
use Aqua\UI\Menu;
use Aqua\UI\Pagination;
use Aqua\UI\Template;
use Aqua\Log\ErrorLog;

class Server
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

	const CHARS_PER_PAGE = 10;

	public function run()
	{
		$server = App::registryGet('ac_active_ragnarok_server');
		if(!($server instanceof _Server)) {
			$this->dispatcher->triggerError(404);
			return;
		}
		if(!($charmap_name = $this->request->uri->getString('ac_ragnarok_charmap_server')) || !($charmap = $server->charMap($charmap_name))) {
			$charmap = current($server->charmap);
		}
		App::registrySet('ac_active_charmap_server', $charmap);
		$pgn = &$this;
		$this->attach('call_action', function() use(&$pgn, $server, $charmap) {
			$pgn->charmap = $charmap;
			$pgn->server  = $server;
			$menu = new Menu;
			$base_url = $pgn->server->charmap($charmap->key())->url(array( 'action' => '' ));
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
		$this->response->status(301)->redirect($this->server->charMapUri($this->charmap->key())->url(array( 'action' => 'info' )));
		return;
	}

	public function info_action()
	{
		$this->title = __('ragnarok', 'x-server-info', htmlspecialchars($this->charmap->name));
		$this->theme->head->section = __('ragnarok', 'server-info');
		try {
			$tpl = new Template;
			$tpl->set('class_population', $this->charmap->status('class_population'))
				->set('characters', $this->charmap->status('characters_registered'))
				->set('guilds', $this->charmap->status('guild_count'))
				->set('accounts', $this->server->login->status('accounts_registered'))
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
			$options = array(
				'online' => 1,
				'cp_option' => array(AC_SEARCH_NO_MATCH | AC_SEARCH_AND, Character::OPT_DISABLE_WHO_IS_ONLINE)
			);
			if(($x = $this->request->uri->getString('m', false)) !== false) {
				$x = addcslashes($x, '%_\\');
				$options['last_map'] = array( AC_SEARCH_LIKE, "%$x%" );
			}
			if(($x = $this->request->uri->getString('c', false)) !== false) {
				$x = addcslashes($x, '%_\\');
				$options['name'] = array( AC_SEARCH_LIKE, "%$x%" );
			}
			$chars = $this->charmap->charSearch(
				$options,
				array( AC_ORDER_ASC, 'name' ),
				array(($current_page - 1) * self::CHARS_PER_PAGE, self::CHARS_PER_PAGE),
				$num
			);
			$this->theme->head->section = $this->title = __('ragnarok', 'whos-online');
			$avail_pages = ceil($num / self::CHARS_PER_PAGE);
			$pgn = new Pagination(App::request()->uri, $avail_pages, $current_page, 'page');
			$tpl = new Template;
			$tpl->set('characters',   $chars);
			$tpl->set('online_chars', $num);
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
