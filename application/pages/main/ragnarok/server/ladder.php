<?php
namespace Page\Main\Ragnarok\Server;

use Aqua\Core\App;
use Aqua\Ragnarok\Character;
use Aqua\Site\Page;
use Aqua\SQL\Search;
use Aqua\UI\Menu;
use Aqua\UI\Template;

class Ladder
extends Page
{
	/**
	 * @var \Aqua\Ragnarok\Server\CharMap
	 */
	public $charmap;
	/**
	 * @var array
	 */
	public static $characterRankingCriteria = array(
		'base_experience' => 'DESC',
		'job_experience' => 'DESC'
	);
	/**
	 * @var array
	 */
	public static $guildRankingCriteria = array(
		'castle_count' => 'DESC',
		'average_level' => 'DESC',
		'experience' => 'DESC'
	);
	/**
	 * @var array
	 */
	public static $homunculusRankingCriteria = array(
		'experience' => 'DESC',
		'intimacy' => 'DESC',
	);
	/**
	 * @var int
	 */
	public static $maxHomunculusRanking = 10;
	/**
	 * @var int
	 */
	public static $maxCharacterRanking  = 10;
	/**
	 * @var int
	 */
	public static $maxGuildRanking      = 10;
	/**
	 * @var int
	 */
	public static $maxZenyRanking       = 10;

	public function run()
	{
		$this->charmap = App::$activeCharMapServer;
		$menu = new Menu;
		$base_url = $this->charmap->url(array(
			'path'   => array( 'ladder' ),
			'action' => ''
		));
		$menu->append('char', array(
			'title' => __('ragnarok', 'char-ranking'),
			'url'   => "{$base_url}index"
		))->append('homunculus', array(
			'title' => __('ragnarok', 'homunculus-ranking'),
			'url'   => "{$base_url}homunculus"
		))->append('guild', array(
			'title' => __('ragnarok', 'guild-ranking'),
			'url'   => "{$base_url}guild"
		))->append('zeny', array(
			'title' => __('ragnarok', 'zeny-ranking'),
			'url'   => "{$base_url}zeny"
		))->append('alchemist', array(
			'title' => __('ragnarok', 'alch-ranking'),
			'url'   => "{$base_url}alchemist"
		))->append('blacksmith', array(
			'title' => __('ragnarok', 'smith-ranking'),
			'url'   => "{$base_url}blacksmith"
		))->append('taekwon', array(
			'title' => __('ragnarok', 'tk-ranking'),
			'url'   => "{$base_url}taekwon"
		));
		$this->theme->set('menu', $menu);
	}

	public function index_action()
	{
		$this->theme->head->section = $this->title = __('ragnarok', 'char-ranking');
		$chars = $this->charmap->charSearch()
			->limit(self::$maxCharacterRanking)
			->order(self::$characterRankingCriteria)
			->query()
			->results;
		$tpl = new Template;
		$tpl->set('characters', $chars)
			->set('count', self::$maxCharacterRanking)
			->set('page', $this);
		echo $tpl->render('ragnarok/ranking/character');
	}

	public function zeny_action()
	{
		$this->theme->head->section = $this->title = __('ragnarok', 'zeny-ranking');
		$chars = $this->charmap->charSearch()
			->limit(self::$maxZenyRanking)
			->where(array(
				'cp_options' => array( Search::SEARCH_DIFFERENT | Search::SEARCH_AND, Character::OPT_DISABLE_ZENY_LADDER ),
			    'zeny'       => array( Search::SEARCH_HIGHER, 0 )
			))
			->order(array( 'zeny' => 'DESC' ))
			->query()
			->results;
		$tpl = new Template;
		$tpl->set('characters', $chars)
			->set('count', self::$maxZenyRanking)
			->set('page', $this);
		echo $tpl->render('ragnarok/ranking/zeny');
	}

	public function homunculus_action()
	{
		$this->theme->head->section = $this->title = __('ragnarok', 'homunculus-ranking');
		$homun = $this->charmap->homunculusSearch()
			->limit(self::$maxHomunculusRanking)
			->order(self::$homunculusRankingCriteria)
			->query()
			->results;
		$tpl = new Template;
		$tpl->set('homunculus', $homun)
			->set('count', self::$maxHomunculusRanking)
			->set('page', $this);
		echo $tpl->render('ragnarok/ranking/homunculus');
	}

	public function guild_action()
	{
		$this->title = __('ragnarok', 'guild-ranking');
		$guilds = $this->charmap->guildSearch()
			->limit(self::$maxGuildRanking)
			->order(self::$guildRankingCriteria)
			->query()
			->results;
		$tpl = new Template;
		$tpl->set('guilds', $guilds)
			->set('count', self::$maxGuildRanking)
			->set('page', $this);
		echo $tpl->render('ragnarok/ranking/guild');
	}

	public function alchemist_action()
	{
		$this->theme->head->section = $this->title = __('ragnarok', 'alch-ranking');
		$chars = $this->charmap->charSearch()
			->limit((int)$this->charmap->getOption('fame-ladder', 10))
			->where(array(
				'class' => array( Search::SEARCH_IN, 18, 4019, 4041, 4071, 4078, 4107 ),
			    'fame'  => array( Search::SEARCH_HIGHER, 0 )
			))
			->order(array( 'fame' => 'DESC' ))
			->query()
			->results;
		$tpl = new Template;
		$tpl->set('characters', $chars)
			->set('count', (int)$this->charmap->getOption('fame-ladder', 10))
			->set('page', $this);
		echo $tpl->render('ragnarok/ranking/taekwon');
	}

	public function blacksmith_action()
	{
		$this->theme->head->section = $this->title = __('ragnarok', 'smith-ranking');
		$chars = $this->charmap->charSearch()
			->limit((int)$this->charmap->getOption('fame-ladder', 10))
			->where(array(
				'class' => array( Search::SEARCH_IN, 10, 4011, 4033, 4058, 4064, 4100 ),
				'fame'  => array( Search::SEARCH_HIGHER, 0 )
			))
			->order(array( 'fame' => 'DESC' ))
			->query()
			->results;
		$tpl = new Template;
		$tpl->set('characters', $chars)
			->set('count', (int)$this->charmap->getOption('fame-ladder', 10))
			->set('page', $this);
		echo $tpl->render('ragnarok/ranking/taekwon');
	}

	public function taekwon_action()
	{
		$this->theme->head->section = $this->title = __('ragnarok', 'tk-ranking');
		$chars = $this->charmap->charSearch()
			->limit((int)$this->charmap->getOption('fame-ladder', 10))
			->where(array(
				'class' => 4046,
				'fame'  => array( Search::SEARCH_HIGHER, 0 )
			))
			->order(array( 'fame' => 'DESC' ))
			->query()
			->results;
		$tpl = new Template;
		$tpl->set('characters', $chars)
			->set('count', (int)$this->charmap->getOption('fame-ladder', 10))
			->set('page', $this);
		echo $tpl->render('ragnarok/ranking/taekwon');
	}
}
