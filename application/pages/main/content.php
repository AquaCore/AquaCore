<?php
namespace Page\Main;

use Aqua\Content\ContentData;
use Aqua\Content\ContentType;
use Aqua\Content\Filter\ArchiveFilter;
use Aqua\Content\Filter\CategoryFilter\Category;
use Aqua\Content\Filter\ScheduleFilter;
use Aqua\Core\App;
use Aqua\Log\ErrorLog;
use Aqua\Site\Page;
use Aqua\SQL\Select;
use Aqua\UI\Pagination;
use Aqua\UI\PaginationPost;
use Aqua\UI\Search;
use Aqua\UI\Template;
use Aqua\UI\Theme;
use Aqua\User\Account;
use Aqua\Util\DataPreload;

class Content
extends Page
{
	/**
	 * @var \Aqua\Content\ContentType
	 */
	public $contentType;
	public static $commentsPerPage = 7;
	public static $contentPerPage  = 10;

	public function run()
	{
		$this->contentType = &App::$activeContentType;
		if(!$this->contentType && $this->request->uri->action !== 'search') {
			$this->error(404);
			return;
		}
		if($this->contentType) {
			$filters = array(
				'CategoryFilter' => array( 'category' ),
				'TagFilter'      => array( 'tagged' )
			);
			foreach($filters as $filter => $actions) {
				if(!$this->contentType->hasFilter($filter) && in_array($this->request->uri->action, $actions)) {
					$this->error(404);
					return;
				}
			}
		}
	}

	public function index_action()
	{
		try {
			if(!$this->contentType->listing) {
				$this->error(404);
				return;
			}
			if($this->contentType->hasFilter('FeedFilter')) {
				$this->theme->head->enqueueLink('rss')
					->rel('alternate')
					->type('application/rss+xml')
					->href($this->contentType->url(array( 'action' => 'feed', 'arguments' => array( 'rss' ) )));
				$this->theme->head->enqueueLink('atom')
					->rel('alternate')
					->type('application/atom+xml')
					->href($this->contentType->url(array( 'action' => 'feed', 'arguments' => array( 'atom' ) )));
			}
			$currentPage = $this->request->uri->getInt('page', 1, 1);
			$this->title = $this->theme->head->section = htmlspecialchars($this->contentType->name);
			$search = $this->_keywordSearch($this->contentType->search(), $values);
			$search
				->calcRows(true)
				->query($values);
			$preLoad = new DataPreload('Aqua\\User\\Account::search', Account::$users);
			$preLoad
				->add($search, array( 'author', 'last_editor' ))
				->run();
			$pgn = new Pagination(App::request()->uri,
			                      ceil($search->rowsFound / self::$contentPerPage),
			                      $currentPage);
			$tpl = new Template;
			$tpl->set('content', $search->results)
				->set('contentCount', $search->rowsFound)
				->set('paginator', $pgn)
				->set('contentType', $this->contentType)
				->set('page', $this);
			echo $tpl->render("content/{$this->contentType->key}/search", 'content/search');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function search_action()
	{
		try {
			if(trim($this->request->uri->getString('s', '')) === '') {
				$this->response->status(302)->redirect(\Aqua\URL);
				return;
			}
			$this->title = $this->theme->head->section = __('application', 'search');
			$currentPage = $this->request->uri->getInt('page', 1, 1);
			$search = $this->_keywordSearch(ContentType::searchSite(), $values);
			$search
				->calcRows(true)
				->query($values);
			$preLoad = new DataPreload('Aqua\\User\\Account::search', Account::$users);
			$preLoad
				->add($search, array( 'author', 'last_editor' ))
				->run();
			$pgn = new Pagination(App::request()->uri,
			                      ceil($search->rowsFound / self::$contentPerPage),
			                      $currentPage);
			$tpl = new Template;
			$tpl->set('content', $search->results)
				->set('contentCount', $search->rowsFound)
				->set('contentType', null)
				->set('paginator', $pgn)
				->set('page', $this);
			echo $tpl->render('content/search');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function view_action($slug = null)
	{
		try {
			if(!$slug || !($content = $this->contentType->get($slug, 'slug')) ||
			   ($content->status !== ContentData::STATUS_PUBLISHED &&
			    $content->status !== ArchiveFilter::STATUS_ARCHIVED)) {
				$this->error(404);
				return;
			}
			$this->title = $this->theme->head->section = htmlspecialchars($this->contentType->name);
			$accounts = array( $content->authorId, $content->lastEditorId );
			$pgn = new Pagination(App::request()->uri, count($content->pages));
			$tpl = new Template;
			$tpl->set('content', $content)
				->set('paginator', $pgn)
			    ->set('page', $this);
			if($this->contentType->hasFilter('CommentFilter') &&
			   !$content->meta->get('comments-disabled', false)) {
				$currentPage = $this->request->uri->getInt('comments', 1, 1);
				list( $comments, $rowsFound ) = $content->getComments(
					$this->request->uri->getInt('root', null),
					($currentPage - 1) * self::$commentsPerPage,
					self::$commentsPerPage
				);
				$commentsPgn = new Pagination(App::request()->uri,
				                              ceil($rowsFound / self::$commentsPerPage),
				                              $currentPage,
				                              'comments');
				foreach($comments as $comment) {
					if(!$comment->anonymous) {
						$accounts[] = $comment->authorId;
						$accounts[] = $comment->lastEditorId;
					} else if($comment->lastEditorId !== $comment->authorId) {
						$accounts[] = $comment->lastEditorId;
					}
				}
				$commentsTpl = new Template;
				$commentsTpl
					->set('comments', $comments)
					->set('commentCount', $content->commentCount())
					->set('content', $content)
					->set('paginator', $commentsPgn)
					->set('page', $this);
				$tpl->set('comments', $commentsTpl);
			}
			if($this->contentType->hasFilter('RatingFilter') &&
			   !$content->meta->get('rating-disabled', false)) {
				$ratingTpl = new Template;
				$ratingTpl
					->set('content', $content)
					->set('page', $this);
				$tpl->set('rating', $ratingTpl);
			}
			if($this->contentType->hasFilter('RelationshipFilter') &&
			   ($parent = $content->parent())) {
				$this->theme->set('return', $this->contentType->url(array( 'path' => $parent->slug )));
			}
			$accounts = array_filter(array_unique($accounts));
			array_unshift($accounts, \Aqua\SQL\Search::SEARCH_IN);
			Account::search()->where(array( 'id' => $accounts ))->query();
			echo $tpl->render("content/{$this->contentType->key}/view", 'content/view');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function preview_action()
	{
		try {
			if(($title = $this->request->getString('title', false)) === false ||
			   ($html  = $this->request->getString('content', false)) === false) {
				$this->response->status(302)->redirect(\Aqua\URL);
				return;
			}
			$publishDate = time();
			$data = array(
				'title' => $title,
			    'content' => $html,
			    'status' => $this->request->getInt('status'),
			    'author' => App::user()->account->id,
			    'publish_date' => $publishDate,
			    'rating_disabled' => !($this->request->getInt('rating')),
			    'comments_disabled' => !($this->request->getInt('comments')),
			    'comment_anonymously' => $this->request->getInt('anonymous'),
			    'category' => $this->request->getArray('category'),
			    'tags' => $this->request->getString('tags'),
			) + $this->request->data;
			$this->theme->head->section = $this->title = __('content', 'preview');
			$content = $this->contentType->forge($data);
			$pages = count($content->pages);
			$this->title = $this->theme->head->section = __('content', 'preview-title', htmlspecialchars($this->contentType->name));
			$pgn   = new PaginationPost($this->request, $pages, null, 'ac_post_page');
			$tpl = new Template;
			$tpl->set('content', $content)
				->set('paginator', $pgn)
				->set('page', $this);
			if($this->contentType->hasFilter('CommentFilter') &&
			   !($content->meta->get('comments-disabled', false))) {
				$comments = array();
				$commentsPgn = new Pagination(App::request()->uri, 0, 1, 'comments');
				$commentsTpl = new Template;
				$commentsTpl
					->set('comments', $comments)
					->set('commentCount', 0)
					->set('content', $content)
					->set('paginator', $commentsPgn)
					->set('page', $this);
				$tpl->set('comments', $commentsTpl);
			}
			if($this->contentType->hasFilter('RatingFilter') &&
			   !$content->meta->get('rating-disabled', false)) {
				$ratingTpl = new Template;
				$ratingTpl
					->set('content', $content)
					->set('page', $this);
				$tpl->set('rating', $ratingTpl);
			}
			echo $tpl->render("content/{$this->contentType->key}/view", 'content/view');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function category_action($slug = null)
	{
		try {
			if(!$slug || !($category = $this->contentType->getCategory($slug, 'slug'))) {
				$this->error(404);
				return;
			}
			if($this->contentType->hasFilter('FeedFilter')) {
				$this->theme->head->enqueueLink('rss')
					->rel('alternate')
					->type('application/rss+xml')
					->href($this->contentType->url(array( 'action' => 'feed', 'arguments' => array( 'rss', $slug ) )));
				$this->theme->head->enqueueLink('atom')
					->rel('alternate')
					->type('application/atom+xml')
					->href($this->contentType->url(array( 'action' => 'feed', 'arguments' => array( 'atom', $slug ) )));
			}
			$currentPage = $this->request->uri->getInt('page', 1, 1);
			$this->title = $this->theme->head->section = __('content', 'category-title',
			                                                htmlspecialchars($this->contentType->name),
			                                                htmlspecialchars($category->name));
			$search = $this->_keywordSearch(
				$this->contentType->categorySearch()->where(array( 'category_id' => $category->id )),
				$values
			);
			$search
				->calcRows(true)
				->query($values);
			$preLoad = new DataPreload('Aqua\\User\\Account::search', Account::$users);
			$preLoad
				->add($search, array( 'author', 'last_editor' ))
				->run();
			$pgn = new Pagination(App::request()->uri,
			                      ceil($search->rowsFound / self::$contentPerPage),
			                      $currentPage);
			$tpl = new Template;
			$tpl->set('content', $search->results)
			    ->set('contentCount', $search->rowsFound)
			    ->set('paginator', $pgn)
			    ->set('contentType', $this->contentType)
			    ->set('category', $category)
			    ->set('page', $this);
			echo $tpl->render("content/{$this->contentType->key}/category", 'content/category',
			                  "content/{$this->contentType->key}/search", 'content/search');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function tagged_action($tag = null)
	{
		try {
			$tag = trim($tag);
			if(!$tag) {
				$this->error(404);
				return;
			}
			$currentPage = $this->request->uri->getInt('page', 1, 1);
			$this->title = $this->theme->head->section = __('content', 'tag-title',
			                                                htmlspecialchars($this->contentType->name),
			                                                htmlspecialchars($tag));
			$search = $this->_keywordSearch(
			               $this->contentType->tagSearch()->where(array( 'tag' => $tag )),
			               $values
			);
			$search
				->calcRows(true)
				->query($values);
			$preLoad = new DataPreload('Aqua\\User\\Account::search', Account::$users);
			$preLoad
				->add($search, array( 'author', 'last_editor' ))
				->run();
			$pgn = new Pagination(App::request()->uri,
			                      ceil($search->rowsFound / self::$contentPerPage),
			                      $currentPage);
			$tpl = new Template;
			$tpl->set('content', $search->results)
			    ->set('contentCount', $search->rowsFound)
			    ->set('paginator', $pgn)
			    ->set('contentType', $this->contentType)
			    ->set('page', $this);
			echo $tpl->render("content/{$this->contentType->key}/tag", 'content/tag',
			                  "content/{$this->contentType->key}/search", 'content/search');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function feed_action($type = null, $category = null)
	{
		try {
			if(!in_array($type, array( 'atom', 'rss' ), true) ||
			   !$this->contentType->hasFilter('FeedFilter') ||
			   $category && (!$this->contentType->hasFilter('CategoryFilter') ||
			    !($category = $this->contentType->getCategory($category, 'slug')))) {
				$this->error(404);
				return;
			}
			if(!($category instanceof Category)) {
				$category = null;
			}
			if($type === 'atom') {
				$feed = $this->contentType->atomFeed($category);
			} else {
				$feed = $this->contentType->rssFeed($category);
			}
			$ttl = $this->contentType->filter('FeedFilter')->ttl();
			if($ttl) {
				$this->response
					->setHeader('Cache-Control', sprintf('max-age=%d, public', $ttl))
					->setHeader('Expires', $feed['lastModified'] + $ttl);
			}
			$this->response
				->setHeader('Last-Modified', $feed['lastModified'])
				->setHeader('Age', time() - $feed['lastModified']);
			if($ifModifiedSince = $this->request->header('If-Modified-Since', null)) {
				$ifModifiedSince = strtotime($ifModifiedSince);
			}
			if($feed['lastModified'] >= $ifModifiedSince) {
				$this->response->status(304);
			} else {
				$this->response->setHeader('Content-Type', "application/$type+xml");
				echo $feed['xml'];
			}
			$this->theme = new Theme;
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			var_dump($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function subscribe_action($slug = null)
	{
		if(!$this->request->method !== 'POST') {
			$this->error(405);
		}
		if($slug && !($content = $this->contentType->get($slug, 'slug'))) {
			$this->error(404);
			return;
		}
		if(isset($content)) {
			$this->response->status(302)->redirect($this->contentType->url(array( 'path' => array( $content->slug ) )));
			if($subType = $this->request->getInt('type')) {
				$subType = min(2, max(1, $subType));
				$content->addSubscription(App::user()->account, $subType);
			} else {
				$content->removeSubscription(App::user()->account);
			}
		} else {
			$this->response->status(302)->redirect($this->contentType->url());
			if($this->request->getInt('subscribe')) {
				$this->contentType->addSubscription(App::user()->account);
			} else {
				$this->contentType->removeSubscription(App::user()->account);
			}
		}
	}

	protected function _keywordSearch(Select $search, &$values)
	{
		$where = array(array(
			'status' => array( \Aqua\SQL\Search::SEARCH_IN,
			                   ContentData::STATUS_PUBLISHED,
			                   ArchiveFilter::STATUS_ARCHIVED),
			'OR',
			array(
				'status' => ScheduleFilter::STATUS_SCHEDULED,
				'publish_date' => array( \Aqua\SQL\Search::SEARCH_HIGHER |
				                         \Aqua\SQL\Search::SEARCH_DIFFERENT,
				                         date('Y-m-d H:i:s'))
			)
		));
		$order = array( 'publish_date' => 'DESC' );
		$values = array();
		if($keywords = $this->request->uri->getString('s', '')) {
			$fulltext = 'MATCH(c._title, c._plain_content) AGAINST (:ftkeyword IN BOOLEAN MODE)';
			$search->columns(array( '_fulltext_relevance' => $fulltext ));
			$where[] = $fulltext;
			$values[':ftkeyword'] = $keywords;
			$order = array( '_fulltext_relevance' => 'DESC' );
		}
		$search
			->where($where)
			->order($order);
		return $search;
	}
}
