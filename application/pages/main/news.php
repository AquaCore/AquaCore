<?php
namespace Page\Main;

use Aqua\Content\ContentType;
use Aqua\Content\Adapter\Post;
use Aqua\Content\Feed\Rss;
use Aqua\Content\Filter\CommentFilter\Comment;
use Aqua\Content\Filter\ScheduleFilter;
use Aqua\Core\App;
use Aqua\Core\L10n;
use Aqua\Core\Settings;
use Aqua\Log\ErrorLog;
use Aqua\Site\Page;
use Aqua\SQL\Search;
use Aqua\UI\Form;
use Aqua\UI\Pagination;
use Aqua\UI\PaginationPost;
use Aqua\UI\Template;
use Aqua\UI\Theme;

class News
extends Page
{
	/**
	 * @var \Aqua\Content\ContentType
	 */
	public $contentType;
	public static $commentsPerPage = 7;
	public static $postsPerPage    = 10;

	public function run()
	{
		$this->contentType = ContentType::getContentType(ContentType::CTYPE_POST);
	}

	public function index_action()
	{
		$this->theme->head->section = $this->title = __('news', 'news');
		try {
			$current_page = $this->request->uri->getInt('page', 1, 1);
			$search       = $this->contentType->search();
			$order        = array( 'publish_date' => 'DESC' );
			$values       = array();
			$where        = array(array(
					'status' => Post::STATUS_PUBLISHED,
					'OR',
					array(
						'status'       => Post::STATUS_SCHEDULED,
						'publish_date' => array( Search::SEARCH_HIGHER | Search::SEARCH_DIFFERENT, date('Y-m-d H:i:s') )
					)
				));
			if(($keywords = $this->request->uri->getString('s', ''))) {
				$fulltext = 'MATCH(c._title, c._plain_content) AGAINST (:ftkeyword IN BOOLEAN MODE)';
				$search->columns(array( '_fulltext_relevance' => $fulltext ));
				$where[]              = $fulltext;
				$values[':ftkeyword'] = $keywords;
				$order                = array( '_fulltext_relevance' => 'DESC' );
			}
			$search
				->calcRows(true)
				->order($order)
				->limit(($current_page - 1) * self::$postsPerPage, self::$postsPerPage)
				->where($where)
				->query($values);
			$pgn = new Pagination(App::request()->uri,
			                      ceil($search->rowsFound / self::$postsPerPage),
			                      $current_page);
			$tpl = new Template;
			$tpl->set('posts', $search->results)
				->set('post_count', $search->rowsFound)
				->set('paginator', $pgn)
				->set('page', $this);
			echo $tpl->render('news/main');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function category_action($slug = '')
	{
		try {
			if(!($category = $this->contentType->getCategory($slug, 'slug'))) {
				$this->error(404);

				return;
			}
			$this->title  = __('content', 'category-x', htmlspecialchars($category->name));
			$current_page = $this->request->uri->getInt('page', 1, 1);
			$search       = $this->contentType->categorySearch();
			$order        = array( 'publish_date' => 'DESC' );
			$values       = array();
			$where        = array(
				array(
					'status' => Post::STATUS_PUBLISHED,
					'OR',
					array(
						'status'       => Post::STATUS_SCHEDULED,
						'publish_date' => array( Search::SEARCH_HIGHER | Search::SEARCH_DIFFERENT, date('Y-m-d H:i:s') )
					)
				),
				'category_id' => $category->id
			);
			if(($keywords = $this->request->uri->getString('s', ''))) {
				$fulltext = 'MATCH(c._title, c._plain_content) AGAINST (:ftkeyword IN BOOLEAN MODE)';
				$search->columns(array( '_fulltext_relevance' => $fulltext ));
				$where[]              = $fulltext;
				$values[':ftkeyword'] = $keywords;
				$order                = array( '_fulltext_relevance' => 'DESC' );
			}
			$search
				->calcRows(true)
				->order($order)
				->limit(($current_page - 1) * self::$postsPerPage, self::$postsPerPage)
				->where($where)
				->query($values);
			$pgn   = new Pagination(App::request()->uri,
			                        ceil($search->rowsFound / self::$postsPerPage),
			                        $current_page);
			$tpl   = new Template;
			$tpl->set('posts', $search->results)
				->set('post_count', $search->rowsFound)
				->set('paginator', $pgn)
				->set('category', $category)
				->set('page', $this);
			echo $tpl->render('news/category');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function tagged_action($tag = '')
	{
		try {
			if($tag === '') {
				$this->error(404);

				return;
			}
			$this->theme->head->section = $this->title = __('content', 'tagged-x', htmlspecialchars($tag));
			$current_page = $this->request->uri->getInt('page', 1, 1);
			$search     = $this->contentType->tagSearch();
			$order      = array( 'publish_date' => 'DESC' );
			$values     = array();
			$where      = array(
				'tag' => $tag,
				array(
					'status' => Post::STATUS_PUBLISHED,
					'OR',
					array(
						'status'       => Post::STATUS_SCHEDULED,
						'publish_date' => array( Search::SEARCH_HIGHER | Search::SEARCH_DIFFERENT, date('Y-m-d H:i:s') )
					)
				));
			if(($keywords = $this->request->uri->getString('s', ''))) {
				$fulltext = 'MATCH(c._title, c._plain_content) AGAINST (:ftkeyword IN BOOLEAN MODE)';
				$search->columns(array( '_fulltext_relevance' => $fulltext ));
				$where[]              = $fulltext;
				$values[':ftkeyword'] = $keywords;
				$order                = array( '_fulltext_relevance' => 'DESC' );
			}
			$search
				->calcRows(true)
				->order($order)
				->limit(($current_page - 1) * self::$postsPerPage, self::$postsPerPage)
				->where($where)
				->query($values);
			$pgn   = new Pagination(App::request()->uri,
			                        ceil($search->rowsFound / self::$postsPerPage),
			                        $current_page);
			$tpl   = new Template;
			$tpl->set('posts', $search->results)
				->set('post_count', $search->rowsFound)
				->set('paginator', $pgn)
				->set('tag', $tag)
				->set('page', $this);
			echo $tpl->render('news/tags');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function view_action($id = '')
	{
		try {
			if(!($post = $this->contentType->get($id, 'slug')) || $post->status !== Post::STATUS_PUBLISHED) {
				$this->error(404);

				return;
			}
			$this->contentType->filter('CommentFilter')->setOption('rating', true);
			$this->title = __('news', 'news');
			$this->theme->head->section = htmlspecialchars($post->title);
			$pages = count($post->pages);
			$pgn   = new Pagination(App::request()->uri, $pages);
			$tpl   = new Template;
			$tpl->set('post', $post)
				->set('paginator', $pgn)
				->set('page', $this);
			if(!$post->getMeta('comments-disabled', false)) {
				$currentPage = $this->request->uri->getInt('comments', 1, 1);
				list( $comments, $rowsFound ) = $post->getComments(
					$this->request->uri->getInt('root', null),
					($currentPage - 1) * self::$postsPerPage,
					self::$commentsPerPage
				);
				$commentsPgn = new Pagination(App::request()->uri,
				                              ceil($rowsFound / self::$commentsPerPage),
				                              $currentPage, 'comments');
				$commentsTpl = new Template;
				$commentsTpl
					->set('comments', $comments)
					->set('commentCount', $rowsFound)
					->set('content', $post)
					->set('paginator', $commentsPgn)
					->set('page', $this);
				$tpl->set('comments', $commentsTpl);
			}
			if(!$post->getMeta('rating-disabled', false)) {
				$ratingTpl = new Template;
				$ratingTpl
					->set('content', $post)
					->set('page', $this);
				$tpl->set('rating', $ratingTpl);
			}
			echo $tpl->render('news/view');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function preview_action()
	{
		if(($title = $this->request->getString('title', false)) === false ||
		   ($content = $this->request->getString('content', false)) === false) {
			$this->response->status(302)->redirect(\Aqua\URL);

			return;
		}
		$publish_date = time();
		$data         = array(
			'title'               => $title,
			'content'             => $content,
			'status'              => $this->request->getInt('status'),
			'author'              => App::user()->account->id,
			'publish_date'        => $publish_date,
			'rating_disabled'     => !($this->request->getInt('rating')),
			'comments_disabled'   => !($this->request->getInt('comments')),
			'comment_anonymously' => $this->request->getInt('anonymous'),
			'category'            => $this->request->getArray('category'),
			'tags'                => $this->request->getString('tags')
		);
		$this->theme->head->section = $this->title = __('content', 'preview');
		$post  = $this->contentType->forge($data);
		$pages = count($post->pages);
		$pgn   = new PaginationPost($this->request, $pages, null, 'ac_post_page');
		$tpl   = new Template;
		$tpl->set('post', $post)
			->set('paginator', $pgn)
			->set('page', $this);
		if(!$post->getMeta('comments-disabled', false)) {
			$comments     = array();
			$comments_pgn = new Pagination(App::request()->uri, 0, 1, 'comments');
			$comments_tpl = new Template;
			$comments_tpl
				->set('comments', $comments)
				->set('comment_count', 0)
				->set('content', $post)
				->set('paginator', $comments_pgn)
				->set('page', $this);
			$comments_frm = new Form($this->request);
			$comments_frm->textarea('content')->required();
			if($post->getMeta('comment-anonymously')) {
				$comments_frm->checkbox('anonymous')->value(array( '1' => '' ))->setLabel(__('comment', 'comment-anonymously'));
			}
			$comments_frm->submit(__('comments', 'submit-comment'))->bool('disabled', true);
			$comments_tpl->set('form', $comments_frm);
			$tpl->set('comments', $comments_tpl);
		}
		if(!$post->getMeta('rating-disabled', false)) {
			$rating_tpl = new Template;
			$rating_tpl
				->set('content', $post)
				->set('page', $this);
			$tpl->set('rating', $rating_tpl);
		}
		echo $tpl->render('news/view');
	}

	public function feed_action($category = null)
	{
		try {
			if($category && !($category = $this->contentType->getCategory($category, 'slug'))) {
				$this->error(404);

				return;
			}
			$rss = Rss::get($category ? 'posts.c' . $category->id : 'posts');
			if(!$rss->inCache) {
				$settings = App::settings()->get('rss');
				$title    = $settings->get('title', App::settings()->get('title', ''));
				$rss->category($settings->get('categories')->toArray())
					->ttl($settings->get('ttl', 60))
					->copyright($settings->get('copyright', null));
				if($category) {
					$link = ac_build_url(array( 'path' => array( 'news', 'category', $category->slug ) ));
					$title .= ' - ' . $category->name;
					$rss->title($title)
						->link($link)
						->description($category->description)
						->category($category->name);
					if($category->imageUrl) {
						$rss->image($category->imageUrl, $title, $link);
					}
					$search = $this->contentType->categorySearch();
				} else {
					$rss->title($title)
						->link(\Aqua\URL)
						->description($settings->get('description', ''));
					if($image = $settings->get('image', false)) {
						$rss->image(
							$image,
							$settings->get('image_title', $title),
							$settings->get('image_link', \Aqua\URL),
							$settings->get('image_description', null)
						);
					}
					$search = $this->contentType->search();
				}
				$search
					->order(array( 'publish_date' => 'DESC' ))
					->limit($settings->get('limit', 20))
					->query();
				foreach($search as $item) {
					$rss->addItem($item, ac_build_url(array( 'path' => array( 'news', $item->slug ) )));
				}
				$rss->save();
			}
			echo $rss->build()->saveXML();
			$this->theme = new Theme;
			$this->response->setHeader('Content-Type', 'application/rss+xml');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}
}
