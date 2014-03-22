<?php
namespace Page\Admin\news;

use Aqua\Content\ContentType;
use Aqua\Content\Filter\CommentFilter\Comment;
use Aqua\Core\App;
use Aqua\Log\ErrorLog;
use Aqua\Site\Page;
use Aqua\UI\Form;
use Aqua\UI\Pagination;
use Aqua\UI\Template;

class Comments
extends Page
{
	/**
	 * @var \Aqua\Content\ContentType
	 */
	public $contentType;

	public static $commentsPerPage = 7;

	public function run()
	{
		$this->contentType = ContentType::getContentType(ContentType::CTYPE_POST);
	}

	public function index_action($id = null)
	{
		try {
			$where = array();
			if($id) {
				if(!($content = $this->contentType->get($id))) {
					$this->error(404);
					return;
				}
				$search = $content->commentSearch();
			} else {
				$search = $this->contentType->commentSearch();
			}
			$this->title = $this->theme->head->section = __('content', 'comments');
			$current_page = $this->request->uri->getInt('page', 1, 1);
			$search
				->calcRows(true)
				->order(array( 'id' => 'DESC' ))
				->limit(($current_page - 1) * self::$commentsPerPage, self::$commentsPerPage)
				->where($where)
				->query();
			$pgn = new Pagination(App::request()->uri,
			                      ceil($search->rowsFound / self::$commentsPerPage),
			                      $current_page);
			$tpl = new Template;
			$tpl->set('comments', $search->results)
				->set('comment_count', $search->rowsFound)
				->set('paginator', $pgn)
				->set('post', isset($post) ? $post : null)
				->set('page', $this);
			echo $tpl->render('admin/news/comments');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function edit_action($id = null)
	{
		try {
			if(!$id || !($comment = $this->contentType->getComment($id))) {
				$this->error(404);
				return;
			}
			/**
			 * @var $comment \Aqua\Content\Filter\CommentFilter\Comment
			 */
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
				echo $tpl->render('admin/news/edit-comment');
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
