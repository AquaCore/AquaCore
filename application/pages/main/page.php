<?php
namespace Page\Main;

use Aqua\Content\ContentData;
use Aqua\Content\ContentType;
use Aqua\Core\App;
use Aqua\Log\ErrorLog;
use Aqua\Site;
use Aqua\UI\Pagination;
use Aqua\UI\PaginationPost;
use Aqua\UI\Template;

class Page
extends Site\Page
{
	/**
	 * @var \Aqua\Content\ContentType
	 */
	public $contentType;

	public function run()
	{
		$this->contentType = ContentType::getContentType(ContentType::CTYPE_PAGE);
	}

	public function index_action($slug = '')
	{
		try {
			if(!$slug || !($page = $this->contentType->get($slug, 'slug')) ||
			   $page->status !== ContentData::STATUS_PUBLISHED) {
				$this->error(404);

				return;
			}
			$this->title = $this->theme->head->section = htmlspecialchars($page->title);
			$pgn = new Pagination(App::request()->uri, count($page->pages), $this->request->uri->getInt('page', 1, 1));
			$tpl = new Template;
			if(!$page->getMeta('rating-disabled', false)) {
				$rating_tpl = new Template;
				$rating_tpl
					->set('content', $page)
					->set('page', $this);
				$tpl->set('rating', $rating_tpl);
			}
			$tpl->set('content', $page)
				->set('paginator', $pgn)
				->set('page', $this);
			echo $tpl->render('page');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function preview_action()
	{
		try {
			if(($title = $this->request->getString('title', false)) === false ||
			   ($content = $this->request->getString('content', false)) === false) {
				$this->response->status(302)->redirect(\Aqua\URL);

				return;
			}
			$publish_date = time();
			$data         = array(
				'title'           => $title,
				'content'         => $content,
				'status'          => $this->request->getInt('status'),
				'author'          => App::user()->account->id,
				'publish_date'    => $publish_date,
				'rating_disabled' => !($this->request->getInt('rating', 0)),
				'parent'          => $this->request->getInt('parent') ? : null
			);
			$this->title = $this->theme->head->section = htmlspecialchars($title);
			$page  = $this->contentType->forge($data);
			$pages = count($page->pages);
			$pgn   = new PaginationPost($this->request, $pages, null, 'ac_post_page');
			$tpl   = new Template;
			$tpl->set('content', $page)
			    ->set('paginator', $pgn)
			    ->set('page', $this);
			if(!$page->getMeta('rating-disabled', false)) {
				$rating_tpl = new Template;
				$rating_tpl
					->set('content', $page)
					->set('page', $this);
				$tpl->set('rating', $rating_tpl);
			}
			echo $tpl->render('page');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}
}
