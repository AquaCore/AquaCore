<?php
namespace Page\Admin\news;

use Aqua\Content\ContentType;
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

	const COMMENTS_PER_PAGE = 15;

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
				->limit(($current_page - 1) * self::COMMENTS_PER_PAGE, self::COMMENTS_PER_PAGE)
				->where($where)
				->query();
			$pgn = new Pagination(App::request()->uri, ceil($search->rowsFound / 15), $current_page);
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
			$this->title = $this->theme->head->section = __('content', 'edit-comment');
			$frm = new Form($this->request);
			$frm->textarea('content')
				->required();
			$frm->checkbox('anonymous')
				->value(array( '1' => '' ))
			    ->setLabel(__('news', 'comment-anonymously'));
			$frm->checkbox('anonymous')
				->value(array( '1' => '' ))
			    ->setLabel(__('news', 'comment-anonymously'));
			$tpl = new Template;
			$tpl->set('comment', $comment)
		        ->set('page', $this);
			$tpl->render('admin/news/edit_comment');
			return;
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}
}
