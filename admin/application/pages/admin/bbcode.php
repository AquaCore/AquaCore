<?php
namespace Page\Admin;

use Aqua\BBCode\Smiley;
use Aqua\Core\App;
use Aqua\Log\ErrorLog;
use Aqua\Site\Page;
use Aqua\UI\Form;
use Aqua\UI\Template;

class BBCode
extends Page
{
	public function smiley_action()
	{
		try {
			if(isset($this->request->data['x-bulk']) &&
			   $this->request->method === 'POST' &&
			   ($action = $this->request->getString('action'))) {
				$this->response->status(302)->redirect(App::request()->uri->url());
				try {
					switch($action) {
						case 'save':
							$smileys = Smiley::smileys();
							$newTexts = $this->request->getArray('smileytext');
							if(empty($newTexts)) {
								break;
							}
							$edit = array();
							foreach($newTexts as $id => $text) {
								if(isset($smileys[$id]) &&
								   $smileys[$id]['text'] !== $text) {
									$edit[$id] = $text;
								}
							}
							$updatedSmileys = Smiley::edit($edit);
							if($updatedSmileys && ($count = count($updatedSmileys))) {
								App::user()->addFlash('success', null, __('bbcode', 'smiley-updated-' . ($count === 1 ? 's' : 'p'), $count));
							}
							break;
						case 'delete':
							$smileys = $this->request->getArray('smileys');
							if(empty($smileys)) {
								break;
							}
							$deletedSmileys = Smiley::delete($smileys);
							if($deletedSmileys && ($count = count($deletedSmileys))) {
								App::user()->addFlash('success', null, __('bbcode', 'smiley-deleted-' . ($count === 1 ? 's' : 'p'), $count));
							}
							break;
						case 'order':
							$order = array();
							$i     = 0;
							foreach($this->request->getArray('order') as $id) {
								$order[$id] = ++$i;
							}
							if(empty($order)) {
								break;
							} else if(Smiley::order($order)) {
								App::user()->addFlash('success', null, __('bbcode', 'smiley-order-saved'));
							}
							break;
					}
				} catch(\Exception $exception) {
					ErrorLog::logSql($exception);
					App::user()->addFlash('error', null, __('application', 'unexpected-error'));
				}
				return;
			}
			$frm = new Form($this->request);
			$frm->enctype = 'multipart/form-data';
			$frm->file('smileys')
			    ->multiple(true)
			    ->attr('accept', 'image/png,image/jpeg,image/gif,application/zip,application/x-tar,application/x-gtar')
			    ->required();
			$frm->submit();
			$frm->validate();
			if($frm->status === Form::VALIDATION_SUCCESS && !empty($_FILES['smileys'])) {
				$this->response->status(302)->redirect(App::request()->uri->url());
				try {
					$newTexts = Smiley::upload('smileys', true);
					$count = count($newTexts);
					if(!$count) {
						App::user()->addFlash('warning', null, __('bbcode', 'smiley-uploaded-fail'));
					} else {
						App::user()->addFlash('success', null, __('bbcode', 'smiley-uploaded-' . ($count === 1 ? 's' : 'p'), $count));
					}
				} catch(\Exception $exception) {
					ErrorLog::logSql($exception);
					App::user()->addFlash('error', null, __('application', 'unexpected-error'));
				}
				return;
			}
			$this->title = $this->theme->head->section = __('bbcode', 'smileys');
			$tpl = new Template;
			$tpl->set('smileys', Smiley::smileys())
			    ->set('form', $frm)
			    ->set('page', $this);
			echo $tpl->render('admin/settings/smiley');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}
}
 