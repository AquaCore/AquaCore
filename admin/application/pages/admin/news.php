<?php
namespace Page\Admin;

use Aqua\Content\Adapter\Post;
use Aqua\Content\ContentType;
use Aqua\Core\App;
use Aqua\Log\ErrorLog;
use Aqua\Site\Page;
use Aqua\SQL\Search;
use Aqua\UI\Form;
use Aqua\UI\Pagination;
use Aqua\UI\Template;
use Aqua\User\Account;

class News
extends Page
{
	/**
	 * @var \Aqua\Content\ContentType
	 */
	public $contentType;

	const ENTRIES_PER_PAGE = 20;

	public function run()
	{
		$this->contentType = ContentType::getContentType(ContentType::CTYPE_POST);
	}

	public function index_action()
	{
		if(isset($this->request->data['x-bulk']) && ($ids = $this->request->getArray('posts', null)) && !empty($ids)) {
			$this->response->status(302)->redirect(App::request()->uri->url());
			try {
				$action  = $this->request->getString('action');
				$updated = 0;
				foreach($ids as $id) {
					if(!($content = $this->contentType->get($id))) {
						continue;
					}
					if($action === 'delete') {
						if($content->delete()) {
							++$updated;
						}
					}
				}
				if($updated) {
					App::user()->addFlash(
						'success',
						null,
						__('news',
						  "posts-{$action}-" . ($updated === 1 ? 's' : 'p'),
						  number_format($updated))
					);
				}
			} catch(\Exception $exception) {
				ErrorLog::logSql($exception);
				App::user()->addFlash('error', null, __('news', 'unexpected-error'));
			}

			return;
		}
		try {
			$this->theme->head->section = $this->title = __('news', 'news');
			$where = array();
			// k : Keywords
			if($x = $this->request->uri->getString('k')) {
				$where['keyword'] = $x;
			}
			// c : Category
			if($x = $this->request->uri->getArray('c')) {
				if(count($x) > 1) {
					array_unshift($x, Search::SEARCH_IN);
				} else {
					$x = current($x);
				}
				$where['category_id'] = $x;
			}
			// s : Status
			if($x = $this->request->uri->getArray('s')) {
				if(count($x) > 1) {
					array_unshift($x, Search::SEARCH_IN);
				} else {
					$x = current($x);
				}
				$where['status'] = $x;
			}
			$current_page = $this->request->uri->getInt('page', 1, 1);
			if(isset($where['category_id'])) {
				$search = $this->contentType->categorySearch();
			} else {
				$search = $this->contentType->search();
			}
			$search
				->calcRows(true)
				->where($where)
				->limit(($current_page - 1) * self::ENTRIES_PER_PAGE, self::ENTRIES_PER_PAGE)
				->order(array( 'uid' => 'DESC' ))
				->query();
			$pgn = new Pagination(App::request()->uri, ceil($search->rowsFound / self::ENTRIES_PER_PAGE), $current_page);
			$tpl = new Template;
			$tpl->set('posts', $search->results)
				->set('post_count', $search->rowsFound)
				->set('paginator', $pgn)
				->set('page', $this);
			echo $tpl->render('admin/news/main');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function new_action()
	{
		try {
			$settings = App::settings()->get('cms')->get('post');
			$frm        = new Form($this->request);
			$categories = array();
			foreach($this->contentType->categories() as $category) {
				$categories[$category->id] = htmlspecialchars($category->name);
			}
			$frm->input('title', true)
				->type('text')
				->setLabel(__('content', 'title'));
			$frm->input('tags', true)
				->type('text')
				->setLabel(__('content', 'tags'))
				->setDescription(__('content', 'tags-desc'));
			$frm->checkbox('category', true)
				->multiple()
				->value($categories)
				->setLabel(__('content', 'category'));
			$frm->select('status', true)
				->required()
				->value(array(
			        Post::STATUS_PUBLISHED => __('news-status', Post::STATUS_PUBLISHED),
			        Post::STATUS_DRAFT     => __('news-status', Post::STATUS_DRAFT),
			        Post::STATUS_ARCHIVED  => __('news-status', Post::STATUS_ARCHIVED),
				))
				->setLabel(__('content', 'status'));
			$frm->input('publish_date', true)
				->type('text')
				->placeholder('YYYY-MM-DD hh:mm:ss')
				->setLabel(__('content', 'publish-date'));
			$frm->input('archive_date', true)
				->type('text')
				->placeholder('YYYY-MM-DD hh:mm:ss')
				->setLabel(__('content', 'archive-date'));
			$frm->checkbox('comments', true)
				->value(array( '1' => '' ))
				->checked($settings->get('enable_comments_by_default', false) ? '1' : null)
				->setLabel(__('content', 'comments-enabled'));
			$frm->checkbox('anonymous', true)
				->value(array( '1' => '' ))
				->checked($settings->get('enable_anonymous_by_default', false) ? '1' : null)
				->setLabel(__('content', 'anonymous-enabled'));
			$frm->checkbox('featured', true)
				->value(array( '1' => '' ))
				->checked($settings->get('featured_by_default', false) ? '1' : null)
				->setLabel(__('content', 'featured'))
				->setDescription(__('content', 'featured-desc'));
			$frm->checkbox('rating', true)
				->value(array( '1' => '' ))
				->checked($settings->get('enable_rating_by_default', false) ? '1' : null)
				->setLabel(__('content', 'enable-rating'));
			$frm->checkbox('archiving', true)
				->value(array( '1' => '' ))
				->checked($settings->get('enable_archiving_by_default', false) ? '1' : null)
				->setLabel(__('content', 'enable-archiving'));
			$frm->textarea('content', true);
			$frm->validate(function (Form $frm) {
					if(($date = trim($frm->request->getString('publish_date'))) &&
					   !\DateTime::CreateFromFormat('Y-m-d H:i:s', $date)) {
						$frm->field('publish_date')->setWarning(__('form', 'invalid-date'));

						return false;
					}
					if(($date = trim($frm->request->getString('archive_date'))) &&
					   !\DateTime::CreateFromFormat('Y-m-d H:i:s', $date)) {
						$frm->field('archive_date')->setWarning(__('form', 'invalid-date'));

						return false;
					}

					return true;
				});
			if($frm->status !== Form::VALIDATION_SUCCESS) {
				$this->theme->head->section = $this->title = __('news', 'new-post');
				$this->theme->set('return', ac_build_url(array( 'path' => array( 'news' ) )));
				$tpl = new Template;
				$tpl->set('form', $frm)
					->set('page', $this);
				echo $tpl->render('admin/news/create');

				return;
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));

			return;
		}
		$this->response->status(302);
		try {
			if(($publishDate = trim($frm->request->getString('publish_date'))) &&
			   ($publishDate = \DateTime::CreateFromFormat('Y-m-d H:i:s', $publishDate))) {
				$publishDate = $publishDate->getTimestamp();
			} else {
				$publishDate = time();
			}
			if(($archiveDate = trim($frm->request->getString('archive_date'))) &&
			   ($archiveDate = \DateTime::CreateFromFormat('Y-m-d H:i:s', $publishDate))) {
				$archiveDate = $archiveDate->getTimestamp();
			} else {
				$archiveDate = null;
			}
			$post = $this->contentType->create(array(
				'title'               => $this->request->getString('title'),
				'content'             => $this->request->getString('content'),
				'status'              => $this->request->getInt('status'),
				'publish_date'        => $publishDate,
				'archive_date'        => $archiveDate,
				'comments_disabled'   => !(bool)$this->request->getInt('comments'),
				'category'            => $this->request->getArray('category'),
				'tags'                => $this->request->getString('tags'),
				'comment_anonymously' => (bool)$this->request->getInt('anonymous'),
				'rating_disabled'     => !(bool)$this->request->getInt('rating'),
				'featured'            => (bool)$this->request->getInt('featured'),
				'archiving'           => (bool)$this->request->getInt('archiving'),
			    'author'              => App::user()->account->id
			));
			if(!$post) {
				$this->response->redirect(App::request()->uri->url());
				App::user()->addFlash('success', null, __('news', 'post-create-fail'));
			} else {
				$this->response->redirect(
					ac_build_url(array(
						'path'      => array( 'news' ),
						'action'    => 'edit',
						'arguments' => array( $post->uid )
					))
				);
				App::user()->addFlash('success', null, __('news', 'post-created'));
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->response->redirect(App::request()->uri->url());
			App::user()->addFlash('error', null, __('application', 'unexpected-error'));
		}
	}

	public function edit_action($id = '')
	{
		try {
			if(!($post = $this->contentType->get($id))) {
				$this->error(404);

				return;
			}
			if($this->request->data('x-delete')) {
				$this->response->status(302)->redirect(ac_build_url(array( 'path' => array( 'news' ) )));
				try {
					$title = $post->title;
					if($post->protected) {
						App::user()->addFlash('success', null, __('news', 'cannot-delete', htmlspecialchars($title)));
					} else {
						if($post->delete()) {
							App::user()->addFlash('success', null, __('news', 'post-deleted', htmlspecialchars($title)));
						} else {
							App::user()->addFlash('success', null, __('news', 'failed-to-delete', htmlspecialchars($title)));
						}
					}
				} catch(\Exception $exception) {
					ErrorLog::logSql($exception);
					App::user()->addFlash('error', null, __('application', 'unexpected-error'));
				}

				return;
			}
			$frm        = new Form($this->request);
			$categories = array();
			foreach($this->contentType->categories() as $category) {
				$categories[$category->id] = htmlspecialchars($category->name);
			}
			$frm->input('title', true)
				->type('text')
				->value(htmlspecialchars($post->title))
				->setLabel(__('content', 'title'));
			$frm->input('tags', true)
				->type('text')
				->value(implode(', ', array_map('htmlspecialchars', $post->tags() ? : array())))
				->setLabel(__('content', 'tags'))
				->setDescription(__('content', 'tags-desc'));
			$frm->checkbox('category', true)
				->value($categories)
				->multiple()
				->checked(array_keys($post->categories() ?: array()))
				->setLabel(__('content', 'category'));
			$frm->select('status', true)
				->value(array(
					Post::STATUS_PUBLISHED => __('news-status', Post::STATUS_PUBLISHED),
					Post::STATUS_DRAFT     => __('news-status', Post::STATUS_DRAFT),
					Post::STATUS_ARCHIVED  => __('news-status', Post::STATUS_ARCHIVED)
				))
				->required()
				->selected(in_array($post->status, array_keys($frm->field('status')->values)) ? $post->status : Post::STATUS_PUBLISHED)
				->setLabel(__('content', 'status'));
			$frm->input('publish_date', true)
				->type('text')
				->placeholder('YYYY-MM-DD hh:mm:ss')
				->attr('timestamp', $post->publishDate)
				->value(date('Y-m-d H:i:s', $post->publishDate))
				->setLabel(__('content', 'publish-date'));
			$archiveDate = $post->getMeta('archive-date') ?: '';
			$frm->input('archive_date', true)
			    ->type('text')
				->attr('timestamp', $archiveDate)
				->value($archiveDate ? date('Y-m-d H:i:s', $archiveDate) : '')
			    ->placeholder('YYYY-MM-DD hh:mm:ss')
			    ->setLabel(__('content', 'archive-date'));
			$frm->checkbox('featured', true)
				->value(array( '1' => '' ))
				->checked('1')
				->checked($post->isFeatured() ? '1' : null)
				->setLabel(__('content', 'featured'))
				->setDescription(__('content', 'featured-desc'));
			$frm->checkbox('comments', true)
				->value(array( '1' => '' ))
				->checked($post->getMeta('comments-disabled') ? null : '1')
				->setLabel(__('content', 'comments-enabled'));
			$frm->checkbox('anonymous', true)
				->value(array( '1' => '' ))
				->checked($post->getMeta('comment-anonymously') ? '1' : null)
				->setLabel(__('content', 'anonymous-enabled'));
			$frm->checkbox('rating', true)
				->value(array( '1' => '' ))
				->checked($post->getMeta('rating-disabled') ? null : '1')
				->setLabel(__('content', 'enable-rating'));
			$frm->checkbox('archiving', true)
			    ->value(array( '1' => '' ))
			    ->checked($post->getMeta('disable-archiving') ? null : '1')
			    ->setLabel(__('content', 'enable-archiving'));
			$frm->textarea('content', true)
				->append(htmlspecialchars($post->content));
			$frm->validate(function (Form $frm) {
					if(($date = trim($frm->request->getString('publish_date'))) &&
					   !\DateTime::CreateFromFormat('Y-m-d H:i:s', $date)) {
						$frm->field('publish_date')->setWarning(__('form', 'invalid-date'));

						return false;
					}
				if(($date = trim($frm->request->getString('archive_date'))) &&
				   !\DateTime::CreateFromFormat('Y-m-d H:i:s', $date)) {
					$frm->field('archive_date')->setWarning(__('form', 'invalid-date'));

					return false;
				}

					return true;
				});
			if($frm->status !== Form::VALIDATION_SUCCESS) {
				$this->theme->head->section = $this->title = __('news', 'edit-post');
				$this->theme->set('return', ac_build_url(array( 'path' => array( 'news' ) )));
				$tpl = new Template;
				$tpl->set('post', $post)
					->set('form', $frm)
					->set('page', $this);
				echo $tpl->render('admin/news/edit');

				return;
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));

			return;
		}
		$this->response->status(302)->redirect(App::request()->uri->url());
		try {
			if(($publishDate = trim($frm->request->getString('publish_date'))) &&
			   ($publishDate = \DateTime::CreateFromFormat('Y-m-d H:i:s', $publishDate))) {
				$publishDate = $publishDate->getTimestamp();
			} else {
				$publishDate = time();
			}
			if(($archiveDate = trim($frm->request->getString('archive_date'))) &&
			   ($archiveDate = \DateTime::CreateFromFormat('Y-m-d H:i:s', $publishDate))) {
				$archiveDate = $archiveDate->getTimestamp();
			} else {
				$archiveDate = null;
			}
			$updated = $post->update(array(
				'title'               => $this->request->getString('title'),
				'content'             => $this->request->getString('content'),
				'status'              => $this->request->getInt('status'),
				'publish_date'        => $publishDate,
				'archive_date'        => $archiveDate,
				'comments_disabled'   => !(bool)$this->request->getInt('comments'),
				'category'            => $this->request->getArray('category'),
				'tags'                => $this->request->getString('tags'),
				'comment_anonymously' => (bool)$this->request->getInt('anonymous'),
				'rating_disabled'     => !(bool)$this->request->getInt('rating'),
				'archiving'           => (bool)$this->request->getInt('archiving'),
				'featured'            => (bool)$this->request->getInt('featured'),
			));
			if($updated) {
				App::user()->addFlash('success', null, __('news', 'post-updated'));
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			App::user()->addFlash('error', null, __('application', 'unexpected-error'));
		}
	}
}
