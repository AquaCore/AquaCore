<?php
namespace Page\Main\Ragnarok\Server;

use Aqua\Core\App;
use Aqua\Ragnarok\Character;
use Aqua\Site\Page;
use Aqua\UI\Menu;
use Aqua\UI\Template;

class Ranking
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
	 * @var array
	 */
	public $characterRankingCriteria = array(
		array( 'base_experience' => 'DESC' ),
		array( 'job_experience' => 'DESC' )
	);

	/**
	 * @var array
	 */
	public $guildRankingCriteria = array(
		array( 'castle_count' => 'DESC' ),
		array( 'average_level' => 'DESC' ),
		array( 'experience' => 'DESC' )
	);

	const MAX_CHARACTER_RANKING = 10;
	const MAX_GUILD_RANKING = 10;
	const MAX_ZENY_RANKING = 10;

	public function run()
	{
		$this->server = App::$activeServer;
		$this->charmap = App::$activeCharMapServer;
		$pgn = $this;
		$this->attach('callaction', function() use (&$pgn) {
				$menu = new Menu;
				$base_url = $pgn->charmap->url(array( 'action' => '' ));
				$menu->append('char', array(
						'title' => __('ragnarok-ranking', 'char-ranking'),
						'url'   => "{$base_url}index"
					))->append('guild', array(
						'title' => __('ragnarok-ranking', 'guild-ranking'),
						'url'   => "{$base_url}guild"
					))->append('guild', array(
						'title' => __('ragnarok-ranking', 'zeny-ranking'),
						'url'   => "{$base_url}zeny"
					))->append('guild', array(
						'title' => __('ragnarok-ranking', 'alch-ranking'),
						'url'   => "{$base_url}alchemist"
					))->append('guild', array(
						'title' => __('ragnarok-ranking', 'smith-ranking'),
						'url'   => "{$base_url}blacksmith"
					))->append('guild', array(
						'title' => __('ragnarok-ranking', 'tk-ranking'),
						'url'   => "{$base_url}taekwon"
					));
				$pgn->theme->set('menu', $menu);
			});
	}

	public function index_action()
	{
		$this->theme->head->section = $this->title = __('ragnarok', 'char-ranking');
		$chars = $this->charmap->charSearch(
			array(),
			$this->characterRankingCriteria,
			self::MAX_CHARACTER_RANKING
		);
		$tpl = new Template;
		$tpl->set('characters', $chars)
			->set('page', $this);
		echo $tpl->render('ragnarok/ranking/character');
	}

	public function zeny_action()
	{
		$this->theme->head->section = $this->title = __('ragnarok', 'zeny-ranking');
		$chars = $this->charmap->charSearch(
			array(
				'cp_option' => array( AC_SEARCH_AND | AC_SEARCH_NO_MATCH, Character::OPT_DISABLE_ZENY_LADDER ),
				'zeny'      => array( AC_SEARCH_HIGHER, 0 )
			),
			array( AC_ORDER_DESC, 'zeny' ),
			self::MAX_CHARACTER_RANKING
		);
		$tpl = new Template;
		$tpl->set('characters', $chars)
			->set('page', $this);
		echo $tpl->render('ragnarok/ranking/zeny');
	}

	public function guild_action()
	{
		$this->title = __('ragnarok', 'guild-ranking');
		$guilds = $this->charmap->guildSearch(array(), $this->guildRankingCriteria, self::MAX_GUILD_RANKING);
		$tpl = new Template;
		$tpl->set('guilds', $guilds)
			->set('page', $this);
		echo $tpl->render('ragnarok/ranking/guild');
	}

	public function alchemist_action()
	{
		$this->theme->head->section = $this->title = __('ragnarok', 'alch-ranking');
		$chars = $this->charmap->charSearch(
			array(
				'class' => array( AC_SEARCH_IN, 18, 4019, 4041, 4071, 4078, 4107 ),
				'fame'  => array( AC_SEARCH_HIGHER, 0 )
			),
			array( AC_ORDER_DESC, 'fame' ),
			$this->charmap->fameRankingLen
		);
		$tpl = new Template;
		$tpl->set('characters', $chars)
			->set('page', $this);
		echo $tpl->render('ragnarok/ranking/taekwon');
	}

	public function blacksmith_action()
	{
		$this->theme->head->section = $this->title = __('ragnarok', 'smith-ranking');
		$chars = $this->charmap->charSearch(
			array(
				'class' => array( AC_SEARCH_IN, 10, 4011, 4033, 4058, 4064, 4100 ),
				'fame'  => array( AC_SEARCH_HIGHER, 0 )
			),
			array( AC_ORDER_DESC, 'fame' ),
			$this->charmap->fameRankingLen
		);
		$tpl = new Template;
		$tpl->set('characters', $chars)
			->set('page', $this);
		echo $tpl->render('ragnarok/ranking/taekwon');
	}

	public function taekwon_action()
	{
		$this->theme->head->section = $this->title = __('ragnarok', 'tk-ranking');
		$chars = $this->charmap->charSearch(
			array(
				'class' => 4046,
				'fame'  => array( AC_SEARCH_HIGHER, 0 )
			),
			array( AC_ORDER_DESC, 'fame' ),
			$this->charmap->fameRankingLen
		);
		$tpl = new Template;
		$tpl->set('characters', $chars)
			->set('page', $this);
		echo $tpl->render('ragnarok/ranking/taekwon');
	}
}
