<?php
namespace Page\Admin;

use Aqua\Content\ContentType;
use Aqua\Core\App;
use Aqua\Log\ErrorLog;
use Aqua\Content\Adapter\Page as P;
use Aqua\Site;
use Aqua\UI\Form;
use Aqua\UI\Pagination;
use Aqua\UI\Template;

class Page
extends Site\Page
{
	/**
	 * @var \Aqua\Content\ContentType
	 */
	public $contentType;

	const ENTRIES_PER_PAGE = 20;

	public function run()
	{
		$this->contentType = ContentType::getContentType(ContentType::CTYPE_PAGE);
	}

	public function index_action()
	{
		try {
			$this->title = $this->theme->head->section = __('page', 'pages');
			$current_page = $this->request->uri->getInt('page', 1, 1);
			$search = $this->contentType->search()
				->calcRows(true)
				->limit(($current_page - 1) * self::ENTRIES_PER_PAGE, self::ENTRIES_PER_PAGE)
				->order(array( 'uid' => 'DESC' ))
				->query();
			$pgn = new Pagination(App::request()->uri, ceil($search->rowsFound / self::ENTRIES_PER_PAGE), $current_page);
			$tpl = new Template();
			$tpl->set('pages', $search->results)
				->set('page_count', $search->rowsFound)
				->set('paginator', $pgn)
				->set('page', $this);
			echo $tpl->render('admin/page/main');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function new_action()
	{
		try {
			$frm = new Form($this->request);
			$frm->input('title')
		        ->type('text')
		        ->setLabel(__('content', 'title'));
			$this->buildParentField($frm);
			$frm->select('status')
				->required()
				->value(array(
						P::STATUS_PUBLISHED => __('page-status', P::STATUS_PUBLISHED),
						P::STATUS_DRAFT => __('page-status', P::STATUS_DRAFT),
					))
				->setLabel(__('page', 'status'));
			$frm->input('publish_date')
				->type('text')
				->placeholder('YYYY-MM-DD hh:mm:ss')
				->setLabel(__('page', 'publish-date'));
			$frm->checkbox('rating')
				->value(array( '1' => '' ))
				->checked(App::settings()->get('cms')->get('page')->get('enable_rating_by_default', false) ? '1' : null)
				->setLabel(__('content', 'enable-rating'));
			$frm->textarea('content');
			$frm->validate(function(Form $frm) {
					if(($date = trim($frm->request->getString('publish_date'))) && !\DateTime::createFromFormat('Y-m-d H:i:s', $date)) {
						$frm->field('publish_date')->setWarning(__('form', 'invalid-date'));
						return false;
					}
					return true;
				});
			if($frm->status !== Form::VALIDATION_SUCCESS) {
				$this->title = $this->theme->head->section = __('page', 'new-page');
				$this->theme->set('return', ac_build_url(array( 'path' => array( 'page' ) )));
				$tpl = new Template;
				$tpl->set('form', $frm)
					->set('page', $this);
				echo $tpl->render('admin/page/new');
				return;
			}
			try {
				if(($date = $this->request->getString('date')) && ($date = \DateTime::createFromFormat('Y-m-d H:i:s', $date))) {
					$date = $date->getTimestamp();
				} else {
					$date = time();
				}
				$page = $this->contentType->create(array(
					'title' => $this->request->getString('title'),
					'content' => $this->request->getString('content'),
					'publish_date' => $date,
					'parent' => $this->request->getInt('parent', ''),
					'disable_rating' => !$this->request->getInt('rating'),
					'author' => App::user()->account->id,
				));
				if($page) {
					$this->response->status(302)->redirect(ac_build_url(array(
								'path' => array( 'page' ),
								'action' => 'edit',
								'arguments' => array( $page->uid )
							)));
					App::user()->addFlash('success', null, __('page', 'page-created', htmlspecialchars($page->title)));
				} else {
					$this->response->status(302)->redirect(App::request()->uri->url());
					App::user()->addFlash('warning', null, __('page', 'page-not-created'));
				}
			} catch(\Exception $exception) {
				ErrorLog::logSql($exception);
				$this->response->status(302)->redirect(App::request()->uri->url());
				App::user()->addFlash('error', null, __('application', 'unexpected-error'));
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function edit_action($id = null)
	{
		try {
			if(!$id || (!$page = $this->contentType->get($id))) {
				$this->error(404);
				return;
			}
			if(isset($this->request->data['x-delete'])) {
				$this->response->status(302);
				try {
					if($page->protected) {
						App::user()->addFlash('warning', null, __('page', 'protected-page', htmlspecialchars($page->title)));
						$this->response->redirect(App::request()->uri->url());
					} else if($page->delete()) {
						App::user()->addFlash('success', null, __('page', 'page-deleted-s', htmlspecialchars($page->title)));
						$this->response->redirect(ac_build_url(array( 'path' => array( 'page' ) )));
					}
				} catch(\Exception $exception) {
					ErrorLog::logSql($exception);
					App::user()->addFlash('error', null, __('application', 'unexpected-error'));
					$this->response->redirect(ac_build_url(array( 'path' => array( 'page' ) )));
				}
				return;
			}
			$frm = new Form($this->request);
			$frm->input('title', true)
		        ->type('text')
				->value(htmlspecialchars($page->title), false)
				->setLabel(__('page', 'title'));
			$this->buildParentField($frm, $page);
			$frm->select('status', true)
				->required()
				->value(array(
						P::STATUS_PUBLISHED => __('page-status', P::STATUS_PUBLISHED),
						P::STATUS_DRAFT => __('page-status', P::STATUS_DRAFT),
					))
				->selected($page->status, false)
				->setLabel(__('page', 'status'));
			$frm->input('publish_date', true)
				->type('text')
				->attr('timestamp', $page->publishDate)
				->placeholder('YYYY-MM-DD hh:mm:ss')
				->value(date('Y-m-d H:i:s', $page->publishDate), false)
				->setLabel(__('page', 'publish-date'));
			$frm->checkbox('rating')
			    ->value(array( '1' => '' ))
			    ->checked($page->getMeta('rating-disabled', false) ? null : '1', false)
			    ->setLabel(__('content', 'enable-rating'));
			$frm->textarea('content', true)
				->append($this->request->getString('content', null) ?: $page->content);
			$frm->validate(function(Form $frm) {
					if(($date = trim($frm->request->getString('publish_date'))) && !\DateTime::createFromFormat('Y-m-d H:i:s', $date)) {
						$frm->field('publish_date')->setWarning(__('form', 'invalid-date'));
						return false;
					}
					return true;
				});
			if($frm->status !== Form::VALIDATION_SUCCESS) {
				$this->title = $this->theme->head->section = __('page', 'edit-page');
				$this->theme->set('return', ac_build_url(array( 'path' => array( 'page' ) )));
				$tpl = new Template;
				$tpl->set('form', $frm)
					->set('content', $page)
					->set('page', $this);
				echo $tpl->render('admin/page/edit');
				return;
			}
			$this->response->status(302)->redirect(App::request()->uri->url());
			try {
				$update = array(
					'title' => $this->request->getString('title'),
					'content' => $this->request->getString('content'),
					'parent' => $this->request->getInt('parent', ''),
					'disable_rating' => !$this->request->getInt('rating'),
					'last_editor' => App::user()->account->id
				);
				if(($date = $this->request->getString('date')) && ($date = \DateTime::createFromFormat('Y-m-d H:i:s', $date))) {
					$update['publish_date'] = $date->getTimestamp();
				} else {
					$update['publish_date'] = time();
				}
				if($page->update($update)) {
					App::user()->addFlash('success', null, __('page', 'page-updated'));
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

	public function buildParentField(Form &$form, P $page = null)
	{
		$tree = $this->contentType->relationshipTree(array( 'title' ));
		if(empty($tree)) {
			return null;
		}
		$field = $form->select('parent')
			->value(array( '' => __('content', 'select-parent') ))
			->setLabel(__('page', 'parent'));
		if($page && ($parent = $page->parent())) {
			$field->selected($parent->uid);
		} else {
			$field->selected('');
		}
		$depth = 0;
		$fn = function($branch) use (&$page, &$field, &$fn, &$depth) {
			foreach($branch as $id => $data) {
				if($page && $id === $page->uid) continue;
				$field->value(array( $id => htmlspecialchars($data['title']) ));
				$field->option($id)->attr('ac-tree-depth', $depth);
				++$depth;
				$fn($data['children']);
				--$depth;
			}
		};
		$fn($tree);
		if(empty($field->values)) {
			return null;
		} else {
			return $field;
		}
	}
}
