<?php
namespace Page\Admin\News;

use Aqua\Content\ContentType;
use Aqua\Core\App;
use Aqua\Log\ErrorLog;
use Aqua\Site\Page;
use Aqua\UI\Form;
use Aqua\UI\Pagination;
use Aqua\UI\Template;
use Aqua\UI\Theme;
use Aqua\Util\ImageUploader;

class Category
extends Page
{
	/**
	 * @var \Aqua\Content\ContentType
	 */
	public $contentType;

	const CATEGORIES_PER_PAGE = 15;

	public function run()
	{
		$this->contentType = ContentType::getContentType(ContentType::CTYPE_POST);
	}

	public function index_action()
	{
		if(isset($this->request->data['x-delete'])) {
			$id = $this->request->getInt('x-delete');
			$this->response->status(302)->redirect(App::request()->uri->url());
			try {
				$category = $this->contentType->getCategory($id, 'id');
				if(!$category) {
					return;
				} else if($category->protected) {
					App::user()->addFlash('warning', null, __('content', 'category-cannot-delete',
					                                          htmlspecialchars($category->name)));
				} else if($category->delete()) {
					App::user()->addFlash('success', null, __('content', 'category-deleted',
					                                          htmlspecialchars($category->name)));
				}
			} catch(\Exception $exception) {
				ErrorLog::logSql($exception);
				App::user()->addFlash('error', null, __('application', 'unexpected-error'));
			}
			return;
		} else if(isset($this->request->data['x-bulk-action']) &&
		          ($ids = $this->request->getArray('categories', false))) {
		  $this->response->status(302)->redirect(App::request()->uri->url());
			try {
				if(empty($ids)) {
					return;
				}
				$deleted = 0;
				foreach($ids as $id) {
					$category = $this->contentType->getCategory($id, 'id');
					if(!$category) {
						continue;
					} else if($category->protected) {
						App::user()->addFlash('warning', null, __('content', 'category-cannot-delete',
						                                          htmlspecialchars($category->name)));
					} else if($category->delete()) {
						++$deleted;
					}
				}
				if($deleted) {
					App::user()->addFlash('success', null, __('content', 'categories-deleted', $deleted));
				}
			} catch(\Exception $exception) {
				ErrorLog::logSql($exception);
				App::user()->addFlash('error', null, __('application', 'unexpected-error'));
			}
			return;
		}
		try {
			$frm = new Form($this->request);
			$frm->enctype = "multipart/form-data";
			$frm->file('image')
				->attr('accept', 'image/jpeg, image/png, image/gif')
				->setLabel(__('content', 'category-image'));
			$frm->input('name', true)
				->required()
				->attr("maxlength", 255)
				->setLabel(__('content', 'category-name'));
			$frm->textarea('description', true)
				->setLabel(__('content', 'category-description'));
			$frm->validate();
			if($frm->status !== Form::VALIDATION_SUCCESS) {
				$this->theme->head->section = $this->title = __('content', 'categories');
				$current_page = $this->request->uri->getInt('page', 1, 1);
				$categories = $this->contentType->categories();
				$count = count($categories);
				$categories = array_slice($categories,
				                          ($current_page - 1) * self::CATEGORIES_PER_PAGE,
				                          self::CATEGORIES_PER_PAGE);
				$pgn = new Pagination(App::request()->uri, ceil($count / self::CATEGORIES_PER_PAGE), $current_page);
				$tpl = new Template;
				$tpl->set('form', $frm)
					->set('categories', $categories)
					->set('category_count', $count)
					->set('paginator', $pgn)
					->set('page', $this);
				echo $tpl->render('admin/news/category');
				return;
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
			return;
		}
		$this->response->status(302)->redirect(App::request()->uri->url());
		try {
			$category = array(
				'name' => $this->request->getString('name'),
				'description' => $this->request->getString('description')
			);
			if(ac_file_uploaded('image', false, $error, $error_str)) {
				$uploader = new ImageUploader;
				if(!$uploader->uploadLocal($_FILES['image']['tmp_name'], $_FILES['image']['name']) ||
				   !($path = $uploader->save('/uploads/content'))) {
					App::user()->addFlash('warning', null, $uploader->errorStr());
				} else {
					$category['image'] = $path;
				}
			} else if($error !== null) {
				App::user()->addFlash('warning', null, $error_str);
			}
			if($category = $this->contentType->createCategory($category)) {
				App::user()->addFlash('success', null, __('content', 'category-created',
				                                          htmlspecialchars($category->name)));
			} else {
				App::user()->addFlash('error', null, __('content', 'category-create-fail'));
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			App::user()->addFlash('error', null, __('application', 'unexpected-error'));
		}
	}

	public function edit_action($id = null)
	{
		try {
			if(!($category = $this->contentType->getCategory($id, 'id'))) {
				$this->error(404);
				return;
			}
			if(isset($this->request->data['x-delete-image'])) {
				try {
					$category->removeImage(true);
					$error = false;
					$message = __('content', 'category-updated');
				} catch(\Exception $exception) {
					$error = true;
					$message = __('application', 'unexpected-error');
				}
				if($this->request->ajax) {
					$this->theme = new Theme;
					$this->response->setHeader('Content-Type', 'application/json');
					echo json_encode(array(
							'message' => $message,
							'error'   => $error,
							'data'    => array(
								'image_url' => $category->imageUrl,
								'image' => $category->image
							)
						));
				} else {
					$this->response->status(302)->redirect(App::request()->uri->url());
					App::user()->addFlash($error ? 'error' : 'success', null, $message);
				}
				return;
			}
			$frm = new Form($this->request);
			$frm->file('image')
		        ->attr('accept', 'image/jpeg, image/png, image/gif')
				->setLabel(__('content', 'category-image'));
			$frm->input('name', true)
				->required()
		        ->attr('maxlength', 255)
				->value(htmlspecialchars($category->name))
		        ->setLabel(__('content', 'category-name'));
			$frm->textarea('description', true)
				->append(htmlspecialchars($category->description))
		        ->setLabel(__('content', 'category-description'));
			$frm->submit();
			$frm->validate(null, $this->request->ajax);
			if($frm->status !== Form::VALIDATION_SUCCESS) {
				$this->title = __('content', 'edit-category-x', htmlspecialchars($category->name));
				$this->theme->head->section = __('content', 'edit-category');
				$this->theme->set('return', ac_build_url(array( 'path' => array( 'news', 'category' ) )));
				$tpl = new Template;
				$tpl->set('category', $category)
					->set('form', $frm)
					->set('page', $this);
				echo $tpl->render('admin/news/edit-category');
				return;
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
			return;
		}
		$message = '';
		$error = false;
		try {
			$options = array();
			if(!$frm->field('name')->getWarning()) {
				$options['name'] = $this->request->getString('name');
			}
			if(!$frm->field('description')->getWarning()) {
				$options['description'] = $this->request->getString('description');
			}
			$error_num = $error_str = null;
			if(!$frm->field('image')->getWarning() && ac_file_uploaded('image', false, $error_num, $error_str)) {
				$uploader = new ImageUploader;
				if(!$uploader->uploadLocal($_FILES['image']['tmp_name'], $_FILES['image']['name']) ||
				   !($path = $uploader->save('/uploads/content'))) {
					$frm->field('image')->setWarning($uploader->errorStr());
				} else {
					$options['image'] = $path;
				}
			} else if($error_num !== null) {
				$frm->field('image')->setWarning($error_str);
			}
			if($category->update($options)) {
				$message = __('news', 'category-updated');
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
				'name'        => $category->name,
				'slug'        => $category->slug,
				'description' => $category->description,
				'image'       => $category->image()
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
	}
}
