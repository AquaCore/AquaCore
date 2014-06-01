<?php
namespace Page\Admin\Content;

use Aqua\Content\Filter\CommentFilter;
use Aqua\Content\Filter\CommentFilter\Comment;
use Aqua\Core\App;
use Aqua\Core\L10n;
use Aqua\Log\ErrorLog;
use Aqua\Site\Page;
use Aqua\UI\Form;
use Aqua\UI\Pagination;
use Aqua\UI\Search;
use Aqua\UI\Template;

class Comments
extends Page
{
	/**
	 * @var \Aqua\Content\ContentType
	 */
	public $contentType;

	public function run()
	{
		$this->contentType = &App::$activeContentType;
	}

	public function index_action($id = null)
	{
		try {
			$currentPage = $this->request->uri->getInt('page', 1, 1);
			$frm = new Search(App::request(), $currentPage);
			$frm->order(array(
					'id' => 'id',
					'parent' => 'parent_id',
			        'pdate' => 'publish_date',
			        'edate' => 'edit_date',
			        'reports' => 'reports',
			        'rating' => 'rating',
			        'status' => 'status'
				))
				->limit(0, 6, 10, 5)
				->defaultOrder('id', Search::SORT_DESC)
				->defaultLimit(10);
			$frm->input('id')
				->setColumn('id')
				->searchType(Search\Input::SEARCH_EXACT)
				->setLabel(__('comment', 'id'))
				->type('number')
				->attr('min', 0);
			$frm->input('user')
				->setColumn('author_display')
				->setLabel(__('content', 'author'));
			$frm->range('pdate')
				->setColumn('publish_date')
				->setLabel(__('content', 'publish-date'))
				->type('datetime')
				->attr('placeholder', 'YYYY-MM-DD HH:MM:SS');
			$frm->range('edate')
				->setColumn('edit_date')
				->setLabel(__('content', 'edit-date'))
				->type('datetime')
				->attr('placeholder', 'YYYY-MM-DD HH:MM:SS');
			$frm->range('rate')
				->setColumn('rating')
				->setLabel(__('content', 'rating'))
				->type('number');
			$frm->select('status')
				->setColumn('status')
				->setLabel(__('content', 'status'))
				->multiple()
				->value(L10n::rangeList('comment-status', range(0, 2)));
			if(!$this->contentType) {
				$search = CommentFilter::commentSearch();
			} else if(!$id) {
				$search = $this->contentType->commentSearch();
			} else if($content = $this->contentType->get($id)) {
				$search = $content->commentSearch();
			} else {
				$this->error(404);
				return;
			}
			$this->title = $this->theme->head->section = __('content', 'comments');
			$frm->apply($search);
			if(isset($search->where['author_display'])) {
				$search
					->whereOptions(array( 'author_display' => 'u._display_name' ))
					->innerJoin(ac_table('users'), 'u.id = co._author_id', 'u');
			}
			$search->calcRows(true)->query();
			$pgn = new Pagination(App::request()->uri,
			                      ceil($search->rowsFound / $frm->getLimit()),
			                      $currentPage);
			$tpl = new Template;
			$tpl->set('comments', $search->results)
			    ->set('commentCount', $search->rowsFound)
			    ->set('paginator', $pgn)
			    ->set('paginator', $pgn)
			    ->set('search', $frm)
			    ->set('content', isset($content) ? $content : null)
			    ->set('page', $this);
			if(!$this->contentType) {
				echo $tpl->render('admin/comments/search');
			} else {
				echo $tpl->render("admin/comments/{$this->contentType->key}/search",
				                  'admin/comments/search');
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function edit_action($id = null)
	{
		try {
			if(!$id || !($comment = CommentFilter::getComment($id))) {
				$this->error(404);
				return;
			}
			$frm = new Form($this->request);
			$frm->textarea('content')
			    ->append(htmlspecialchars($comment->bbCode))
			    ->required();
			$frm->checkbox('anonymous')
			    ->value(array( '1' => '' ))
			    ->checked($comment->anonymous ? '1' : null)
			    ->setLabel(__('comment', 'anonymous'));
			$frm->select('status')
			    ->value(array(
				            Comment::STATUS_PUBLISHED => __('comment-status', Comment::STATUS_PUBLISHED),
				            Comment::STATUS_HIDDEN => __('comment-status', Comment::STATUS_HIDDEN),
				            Comment::STATUS_FLAGGED => __('comment-status', Comment::STATUS_FLAGGED)
			            ))
			    ->selected($comment->status)
			    ->setLabel(__('content', 'status'));
			$frm->input('delete')
			    ->type('submit')
			    ->setLabel(__('application', 'delete'));
			$frm->input('submit')
			    ->type('submit')
			    ->setLabel(__('application', 'submit'));
			$frm->validate();
			if($frm->status !== Form::VALIDATION_SUCCESS) {
				$this->theme->set('return', ac_build_url(array( 'path' => array( 'news', 'comments' ) )));
				$this->title = $this->theme->head->section = __('content', 'edit-comment');
				$tpl = new Template;
				$tpl->set('comment', $comment)
				    ->set('reports', $comment
					    ->reportSearch()
					    ->query()
					    ->results)
				    ->set('form', $frm)
				    ->set('page', $this);
				echo $tpl->render("admin/comments/{$comment->contentType->key}/edit",
				                  'admin/comments/edit');
				return;
			}
			try {
				$this->response->status(302)->redirect(App::request()->uri->url());
				if($this->request->data('submit')) {
					$comment->update(array(
						                 'bbcode_content' => $this->request->getString('content'),
						                 'anonymous' => (bool)$this->request->getInt('anonymous'),
						                 'status' => $this->request->getInt('status'),
						                 'last_editor' => App::user()->account->id
					                 ));
				} else if($this->request->data('delete')) {
					$comment->delete();
				}
			} catch(\Exception $exception) {
				ErrorLog::logSql($exception);
				App::user()->addFlash('error', null, __('application', 'unexpected-error'));
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}
}
