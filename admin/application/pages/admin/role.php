<?php
namespace Page\Admin;

use Aqua\Core\App;
use Aqua\Log\ErrorLog;
use Aqua\Log\PayPalLog;
use Aqua\Log\TransferLog;
use Aqua\Site\Page;
use Aqua\UI\Form;
use Aqua\UI\Pagination;
use Aqua\UI\Template;
use Aqua\UI\Theme;
use Aqua\User\Account;
use Aqua\User\Role as R;

class Role
extends Page
{
	const ENTRIES_PER_PAGE = 15;

	public function index_action()
	{
		if($this->request->uri->getString('token') && $this->request->uri->getString('token') === App::user()->getToken('admin_role_action')) {
			$action = $this->request->uri->getString('x-action');
			$ids = array( $this->request->uri->getInt('role') );
		} else if(isset($this->request->data['x-bulk'])) {
			$action = $this->request->getString('action');
			$ids = $this->request->getArray('roles');
		}
		if(!empty($action) && !empty($ids) && $action === 'delete') {
			$this->response->status(302)->redirect(App::request()->uri->url());
			try {
				$updated = 0;
				foreach($ids as $id) {
					if(!($role = R::get($id)) || !R::delete($role)) {
						continue;
					}
					++$updated;
				}
				if($updated === 1) {
					App::user()->addFlash('success', null, __('role', 'role-' . $action . '-s', htmlspecialchars($role->name)));
				} else if($updated > 1) {
					App::user()->addFlash('success', null, __('role', 'role-' . $action . '-p', number_format($updated)));
				} else {
					return;
				}
				Account::rebuildCache('last_user');
				TransferLog::rebuildCache('last_transfer');
				PayPalLog::rebuildCache('last_donation');
			} catch(\Exception $exception) {
				ErrorLog::logSql($exception);
				App::user()->addFlash('error', null, __('application', 'unexpected-error'));
			}
			return;
		}
		try {
			$frm = new Form($this->request);
			$frm->input('name', true)
				->type('text')
				->required()
			    ->attr('maxlength', 255)
			    ->setLabel(__('role', 'name'));
			$frm->input('color', true)
				->type('color')
			    ->setLabel(__('role', 'color'));
			$frm->input('background', true)
				->type('color')
			    ->setLabel(__('role', 'background'));
			$permission = $frm->checkbox('permission')
				->multiple(true)
				->setLabel(__('role', 'permissions'));
			foreach(R::permissionList() as $name) {
				$permission->value(array( $name => htmlspecialchars(__('permission-name', $name)) ));
				$desc = htmlspecialchars(__('permission-desc', $name));
				$permission->label($name)
					->attr('class', 'ac-tooltip')
					->attr('title', $desc)
					->attr('alt', $desc);
			}
			$frm->textarea('description', true)
				->setLabel(__('role', 'description'));
			$frm->validate();
			if($frm->status !== Form::VALIDATION_SUCCESS) {
				$this->title = $this->theme->head->section = __('role', 'roles');
				$current_page = $this->request->uri->get('page', 1, 1);
				$count = count(R::$roles);
				$pages = ceil($count / self::ENTRIES_PER_PAGE);
				$roles = array_slice(R::$roles, ($current_page - 1) * self::ENTRIES_PER_PAGE, self::ENTRIES_PER_PAGE, false);
				$pgn = new Pagination(App::request()->uri, $pages, $current_page);
				$tpl = new Template;
				$tpl->set('roles', $roles)
			        ->set('role_count', $count)
			        ->set('paginator', $pgn)
			        ->set('form', $frm)
					->set('token', App::user()->setToken('admin_role_action', 16))
			        ->set('page', $this);
				echo $tpl->render('admin/role/main');
				return;
			}
			$this->response->status(302)->redirect(App::request()->uri->url());
			try {
				if(!($color = $this->request->getString('color'))) {
					$color = null;
				} else {
					$color = hexdec(substr($color, 1));
				}
				if(!($background = $this->request->getString('background'))) {
					$background = null;
				} else {
					$background = hexdec(substr($background, 1));
				}
				$role = R::create(
					trim($this->request->getString('name')),
					trim($this->request->getString('description')),
					$color,
					$background,
					$this->request->getArray('permission')
				);
				if($role) {
					App::user()->addFlash('success', null, __('role', 'role-created', htmlspecialchars($role->name)));
				} else {
					App::user()->addFlash('success', null, __('role', 'role-not-created'));
				}
			} catch(\Exception $exception) {
				ErrorLog::logSql($exception);
				App::user()->addFlash('error', null, __('application', 'unexpected-error'));
			}
			return;
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function edit_action($id = null)
	{
		try {
			if(!$id || !($role = R::get($id))) {
				$this->error(404);
				return;
			}
			$frm = new Form($this->request);
			$frm->input('name', true)
				->type('text')
				->required()
				->value(htmlspecialchars($role->name), false)
				->attr('maxlength', 255)
				->setLabel(__('role', 'name'));
			$frm->input('color', true)
				->type('color')
				->value($role->color ? sprintf('#%06x', $role->color) : null, false)
				->setLabel(__('role', 'color'));
			$frm->input('background', true)
				->type('color')
				->value($role->background ? sprintf('#%06x', $role->background) : null, false)
				->setLabel(__('role', 'background'));
			$permission = $frm->checkbox('permission')
				->multiple(true)
				->setLabel(__('role', 'permissions'));
			foreach(R::permissionList() as $name) {
				$permission->value(array( $name => htmlspecialchars(__('permission-name', $name)) ));
				$desc = htmlspecialchars(__('permission-desc', $name));
				$permission->label($name)
					->attr('class', 'ac-tooltip')
					->attr('title', $desc)
					->attr('alt', $desc);
				if($role->hasPermission($name) && $role->permission[$name] === 2) {
					$permission->option($name)->bool('disabled', true);
				}
			}
			$permission->checked($role->permissions());
			$frm->textarea('description', true)
				->setLabel(__('role', 'description'));
			$frm->submit();
			$frm->validate(null, !$this->request->ajax);
			if($frm->status !== Form::VALIDATION_SUCCESS) {
				if($this->request->ajax) {
					$this->error(204);
					return;
				}
				$this->title = __('role', 'x-edit-role', htmlspecialchars($role->name));
				$this->theme->set('return', ac_build_url(array( 'path' => array( 'role' ) )));
				$this->theme->head->section = __('role', 'edit-role');
				$tpl = new Template;
				$tpl->set('role', $role)
			        ->set('form', $frm)
			        ->set('page', $this);
				echo $tpl->render('admin/role/edit');
				return;
			}
			$error = false;
			$message = null;
			$updated = 0;
			try {
				$update = array();
				if(!$frm->field('name')->getWarning()) {
					$update['name'] = trim($this->request->getString('name'));
				}
				if(!$frm->field('description')->getWarning()) {
					$update['description'] = trim($this->request->getString('description'));
					if(!$update['description']) {
						$update['description'] = null;
					}
				}
				if(!$frm->field('color')->getWarning()) {
					if($color = $this->request->getString('color')) {
						$update['color'] = hexdec(substr($color, 1));
					} else {
						$update['color'] = null;
					}
				}
				if(!$frm->field('background')->getWarning()) {
					if($background = $this->request->getString('background')) {
						$update['background'] = hexdec(substr($background, 1));
					} else {
						$update['background'] = null;
					}
				}
				if(!empty($update) && $role->update($update)) {
					++$updated;
				}
				file_put_contents(\Aqua\ROOT . '/test.txt', print_r($update, true));
				if(!$frm->field('permission')->getWarning()) {
					$newPermissions    = $this->request->getArray('permission');
					$oldPermissions    = $role->permissions();
					$removePermissions = array_diff($oldPermissions, $newPermissions);
					$addPermissions    = array_diff($newPermissions, $oldPermissions);
					if(!empty($removePermissions) && $role->removePermission($removePermissions)) {
						++$updated;
					}
					if(!empty($addPermissions) && $role->addPermission($addPermissions)) {
						++$updated;
					}
				}
				if($updated) {
					$message = __('role', 'role-updated');
				}
			} catch(\Exception $exception) {
				ErrorLog::logSql($exception);
				$error = true;
				$message = __('application', 'unexpected-error');
			}
			if($this->request->ajax) {
				$this->theme = new Theme;
				$this->response->setHeader('Content-Type', 'application/json');
				$response = array( 'message' => $message, 'error' => $error, 'data' => array(), 'warning' => array() );
				foreach($frm->content as $key => $field) {
					if($field instanceof Form\FieldInterface && ($warning = $field->getWarning())) {
						$response['warning'][$key] = $warning;
					}
				}
				$response['data'] = array(
					'name'           => $role->name,
					'name_formatted' => $role->display($role->name, 'ac-username')->render(),
					'color'          => $role->color ? sprintf('#%06x', $role->color) : '',
					'background'     => $role->background ? sprintf('#%06x', $role->background) : '',
					'description'    => $role->description ?: '',
				    'permission'     => $role->permission
				);
				echo json_encode($response);
			} else {
				$this->response->status(302)->redirect(App::request()->uri->url());
				if($message) {
					App::user()->addFlash($error ? 'error' : 'success', null, $message);
				}
				foreach($frm->content as $field) {
					if($field instanceof Form\FieldInterface && ($warning = $field->getWarning())) {
						App::user()->addFlash('warning', null, $warning);
					}
				}
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}
}