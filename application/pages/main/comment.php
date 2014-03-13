<?php
namespace Page\Main;

use Aqua\Content\ContentType;
use Aqua\Core\App;
use Aqua\Log\ErrorLog;
use Aqua\Site\Page;
use Aqua\UI\Form;
use Aqua\UI\Template;

class Comment
extends Page
{
	public function index_action($cType = null, $id = null)
	{
		if(!$this->request->method !== 'POST') {
			$this->error(405);
			return;
		}
		$this->response->redirect(302);
		if(!$cType || !$id ||
		   !($cType = ContentType::getContentType($cType)) ||
		   !($content = $cType->get($id, 'id'))) {
			$this->response->redirect(App::request()->previousUrl());
			return;
		}
		if(!array_key_exists('content', $this->request->data)) {
			return;
		}
	}

	public function reply_action($cType = null, $contentId = null, $commentId = null)
	{
		try {
			if(!App::user()->role()->hasPermission('comment')) {
				$this->error(403);

				return;
			}
			if(!$cType || !$contentId ||
			   !($cType = ContentType::getContentType($cType, 'key')) ||
			   !$cType->hasFilter('commentFilter') ||
			   !($content = $cType->get($contentId)) ||
			   ($commentId && !($comment = $cType->getComment($commentId)))) {
				$this->error(404);

				return;
			}
			if(empty($comment) && $this->request->method !== 'POST')  {
				$this->error(405);

				return;
			}
			$frm = new Form($this->request);
			$frm->textarea('content')
				->required();
			if($content->getMeta('comment-anonymously')) {
				$frm->checkbox('anonymous')
					->value(array( '1' => '' ))
					->setLabel(__('comment', 'comment-anonymously'));
			}
			$frm->submit();
			$frm->validate();
			if($frm->status !== Form::VALIDATION_SUCCESS) {
				if(empty($comment)) {
					$title = __('comment', 'comment');
				} else {
					$title = __('comment', 'reply-comment');
				}
				$this->title = $this->theme->head->section = $title;
				$tpl = new Template;
				$tpl->set('content', $content)
					->set('comment', isset($comment) ? $comment : null)
					->set('form', $frm)
					->set('page', $this);
				echo $tpl->render('content/comment-reply');
				return;
			}
			$this->response->redirect(302);
			try {
				if(($url = base64_decode($this->request->uri->getString('return', ''))) && parse_url($url, PHP_URL_HOST) === \Aqua\DOMAIN) {
					$this->response->redirect($url);
				} else {
					$this->response->redirect(\Aqua\URL);
				}
				$arguments = array(
					App::user()->account,
					$this->request->getString('content'),
					$content->getMeta('comment-anonymously') && $this->request->getInt('anonymous'),
					0,
					\Aqua\Content\Filter\CommentFilter\Comment::STATUS_PUBLISHED
				);
				if(isset($comment)) {
					$arguments[] = $comment;
				}
				$comment = call_user_func_array(array( $content, 'addComment' ), $arguments);
				if(!$comment) {
					return;
				} else if($comment->status === \Aqua\Content\Filter\CommentFilter\Comment::STATUS_FLAGGED) {
					App::user()->addFlash('warning', null, __('comment', 'flagged'));
				} else {
					App::user()->addFlash('success', null, __('comment', 'saved'));
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

	public function report_action($id = null)
	{

	}
}
 