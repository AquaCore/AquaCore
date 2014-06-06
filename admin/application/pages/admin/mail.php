<?php
namespace Page\Admin;

use Aqua\Core\App;
use Aqua\Log\ErrorLog;
use Aqua\Site\Page;
use Aqua\SQL\Query;
use Aqua\UI\Form;
use Aqua\UI\Pagination;
use Aqua\UI\Template;
use Aqua\Util\Email;

class Mail
extends Page
{
	public static $templatesPerPage = 20;

	public function index_action()
	{
		$this->title = $this->theme->head->section = __('email', 'templates');
		try {
			$currentPage = $this->request->getInt('page', 1, 1);
			$templates = Query::select(App::connection())
				->columns(array(
					'key'       => '_key',
				    'name'      => '_name',
				    'plugin_id' => '_plugin_id'
				))
				->from(ac_table('email_templates'))
				->limit(($currentPage - 1) * self::$templatesPerPage, self::$templatesPerPage)
				->calcRows(true)
				->setKey('key')
				->query();
			$pgn = new Pagination(App::request()->uri,
			                      ceil($templates->rowsFound / self::$templatesPerPage),
			                      $currentPage);
			$tpl = new Template;
			$tpl->set('templates', $templates->results)
				->set('templateCount', $templates->rowsFound)
				->set('paginator', $pgn)
				->set('page', $this);
			echo $tpl->render('admin/email/templates');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function edit_action($key = null)
	{
		try {
			if(!$key || !($template = Email::getTemplate($key, true))) {
				$this->error(404);
				return;
			}
			$frm = new Form($this->request);
			$frm->input('subject', true)
				->type('text')
				->setLabel(__('email', 'subject'))
				->value($template['subject']);
			$body = $frm->textarea('body', true)->required(true);
			$altBody = $frm->textarea('altbody', true);
			if(empty($body->content[0])) {
				$body->append($template['body']);
			}
			if(empty($altBody->content[0])) {
				$body->append($template['alt_body']);
			}
			$frm->validate();
			if($frm->status !== Form::VALIDATION_SUCCESS) {
				$this->title = $this->theme->head->section = __('email', 'edit-x-template', htmlspecialchars($template['name']));
				$this->theme->set('return', ac_build_url(array( 'path' => array( 'mail' ) )));
				$tpl = new Template;
				$tpl->set('template', $template)
					->set('form', $frm)
					->set('page', $this);
				echo $tpl->render('admin/email/edit');
				return;
			}
			$this->response->status(302)->redirect(App::request()->uri->url());
			try {
				Email::editTemplate($key,
				                    $this->request->getString('subject') ?: null,
				                    $this->request->getString('body')    ?: null,
				                    $this->request->getString('altbody') ?: null);
				App::user()->addFlash('success', null, __('email', 'template-saved', $template['name']));
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
