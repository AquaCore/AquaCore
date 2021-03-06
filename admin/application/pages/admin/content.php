<?php
namespace Page\Admin;

use Aqua\Content\ContentData;
use Aqua\Content\ContentType;
use Aqua\Content\Filter\ArchiveFilter;
use Aqua\Content\Filter\ScheduleFilter;
use Aqua\Core\App;
use Aqua\Core\Settings;
use Aqua\Log\ErrorLog;
use Aqua\Plugin\Plugin;
use Aqua\Site\Page;
use Aqua\UI\Form;
use Aqua\UI\Pagination;
use Aqua\UI\Search\Input;
use Aqua\UI\Search;
use Aqua\UI\Template;
use Aqua\User\Account;
use Aqua\Util\DataPreload;
use Aqua\Util\ImageUploader;

class Content
extends Page
{
	/**
	 * @var \Aqua\Content\ContentType
	 */
	public $contentType;

	public function run()
	{
		$this->contentType = &App::$activeContentType;
		$pgn = &$this;
		$this->attach('call_action', function() use($pgn) {
			if(!$pgn->contentType) {
				$pgn->error(404);
				return;
			}
		});
	}

	public function index_action()
	{
		if(isset($this->request->data['x-bulk']) &&
		   ($ids = $this->request->getArray('content', null)) &&
		   !empty($ids)) {
			$action  = $this->request->getString('action');
			if($this->call('bulkAction', array( $action, $ids ), true) !== false && $action === 'delete') {
				$this->response->status(302)->redirect(App::request()->uri->url());
				try {
					$deleted = 0;
					foreach($ids as $id) {
						if(!($content = $this->contentType->get($id))) {
							continue;
						}
						if($content->protected) {
							App::user()->addFlash('warning', null, __('content', 'cannot-delete', htmlspecialchars($content->title)));
							continue;
						}
						if($content->delete()) {
							++$deleted;
						} else {
							App::user()->addFlash('warning', null, __('content', 'content-not-deleted', htmlspecialchars($content->title)));
						}
					}
					if($deleted) {
						App::user()->addFlash('success', null, __('content',
						                                          'content-deleted-' . ($deleted === 1 ? 's' : 'p'),
						                                          number_format($deleted)));
					}
				} catch(\Exception $exception) {
					ErrorLog::logSql($exception);
					App::user()->addFlash('error', null, __('application', 'unexpected-error'));
				}
				return;
			}
		}
		try {
			$this->theme->head->section = $this->title = htmlspecialchars($this->contentType->name);
			$currentPage = $this->request->uri->getInt('page', 1, 1);
			$frm = new Search(App::request(), $currentPage);
			$frm->order(array(
					'id'     => 'uid',
			        'title'  => 'title',
			        'pdate'  => 'publish_date',
			        'edate'  => 'edit_date',
			        'status' => 'status',
				))
				->limit(0, 6, 20, 5)
				->defaultOrder('id', Search::SORT_DESC)
				->defaultLimit(20)
				->persist("admin.ctype.{$this->contentType->key}");
			$frm->input('id')
				->setColumn('uid')
				->searchType(Input::SEARCH_EXACT)
				->setLabel(__('content', 'id'))
				->type('number')
				->attr('min', 0);
			$frm->input('t')
				->setColumn('title')
				->setLabel(__('content', 'title'));
			if($this->contentType->hasFilter('CategoryFilter')) {
				$categories = array();
				foreach($this->contentType->categories() as $category) {
					$categories[$category->id] = htmlspecialchars($category->name);
				}
				if(!empty($categories)) {
					$frm->select('cat')
						->setColumn('category_id')
						->setLabel(__('content', 'category'))
						->multiple()
						->value($categories);
				}
			}
			$status = array(
				ContentData::STATUS_PUBLISHED => __('content-status', ContentData::STATUS_PUBLISHED),
				ContentData::STATUS_DRAFT     => __('content-status', ContentData::STATUS_DRAFT)
			);
			if($this->contentType->hasFilter('ScheduleFilter')) {
				$status[ScheduleFilter::STATUS_SCHEDULED] = __('content-status', ScheduleFilter::STATUS_SCHEDULED);
			}
			if($this->contentType->hasFilter('ArchiveFilter')) {
				$status[ArchiveFilter::STATUS_ARCHIVED] = __('content-status', ArchiveFilter::STATUS_ARCHIVED);
			}
			$frm->select('s')
				->setColumn('status')
				->setLabel(__('content', 'status'))
				->multiple()
				->value($status);
			if(($categories = $frm->getArray('cat', null)) && !empty($categories)) {
				$search = $this->contentType->categorySearch();
			} else {
				$search = $this->contentType->search();
			}
			$frm->apply($search);
			$search->calcRows(true)->query();
			$load = new DataPreload('Aqua\\User\\Account::search', Account::$users);
			$load
				->add($search, array( 'author', 'last_editor' ))
				->run();
			$pgn = new Pagination(App::request()->uri,
			                      ceil($search->rowsFound / $frm->getLimit()),
			                      $currentPage);
			$tpl = new Template;
			$tpl->set('content', $search->results)
			    ->set('contentCount', $search->rowsFound)
			    ->set('paginator', $pgn)
			    ->set('search', $frm)
			    ->set('page', $this);
			echo $tpl->render("admin/content/{$this->contentType->key}/search",
			                  'admin/content/search');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function new_action()
	{
		try {
			$frm = $this->buildForm();
			$frm->validate();
			if($frm->status !== Form::VALIDATION_SUCCESS) {
				$this->theme->head->section = $this->title = __('content', 'new-title',
				                                                htmlspecialchars($this->contentType->itemName));
				$this->theme->set('return', $this->contentType->url());
				$tpl = new Template;
				$tpl->set('form', $frm)
					->set('page', $this);
				echo $tpl->render("admin/content/{$this->contentType->key}/edit",
				                  'admin/content/edit');
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
			$options = array(
				'title'               => $this->request->getString('title'),
			    'content'             => $this->request->getString('content'),
			    'status'              => $this->request->getInt('status'),
			    'parent'              => $this->request->getInt('parent', null) ?: null,
			    'category'            => $this->request->getArray('category'),
			    'tags'                => $this->request->getString('tags'),
			    'publish_date'        => $publishDate,
			    'archive_date'        => $archiveDate,
			    'comments_disabled'   => !(bool)$this->request->getInt('comments'),
			    'comment_anonymously' => (bool)$this->request->getInt('anonymous'),
			    'rating_disabled'     => !(bool)$this->request->getInt('rating'),
			    'featured'            => (bool)$this->request->getInt('featured'),
			    'archiving'           => (bool)$this->request->getInt('archiving'),
			    'author'              => App::user()->account->id
			);
			$arguments = array( &$options, $frm );
			if($this->call('parseForm', $arguments, true) !== false) {
				$content = $this->contentType->create($options);
				if(!$content) {
					$this->response->redirect($this->contentType->url());
					App::user()->addFlash('warning', null, __('content', 'content-create-fail', htmlspecialchars($this->contentType->name)));
				} else {
					$this->response->redirect($this->contentType->url(array(
						'action' => 'edit',
					    'arguments' => array( $content->uid )
					)));
					App::user()->addFlash('success', null, __('content', 'content-create-success',
					                                          htmlspecialchars($this->contentType->name),
					                                          htmlspecialchars($content->title)));
				}
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			App::user()->addFlash('error', null, __('application', 'unexpected-error'));
			$this->response->redirect($this->contentType->url());
		}
	}

	public function edit_action($id = null)
	{
		try {
			if(!($content = $this->contentType->get($id, 'id'))) {
				$this->error(404);
				return;
			}
			$frm = $this->buildForm($content);
			$frm->validate();
			if($frm->status !== Form::VALIDATION_SUCCESS) {
				$this->theme->head->section = $this->title = __('content', 'edit-title',
				                                                htmlspecialchars($this->contentType->itemName));
				$this->theme->set('return', $this->contentType->url());
				$tpl = new Template;
				$tpl->set('form', $frm)
					->set('content', $content)
				    ->set('page', $this);
				echo $tpl->render("admin/content/{$this->contentType->key}/edit",
				                  'admin/content/edit');
				return;
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
			return;
		}
		$this->response->status(302)->redirect(App::request()->uri->url());
		try {
			if(isset($this->request->data['x-delete'])) {
				if($content->protected) {
					App::user()->addFlash('warning', null, __('content', 'cannot-delete', htmlspecialchars($content->title)));
				} else if($content->delete()) {
					App::user()->addFlash('success', null, __('content', 'content-deleted', htmlspecialchars($content->title)));
					$this->response->redirect($this->contentType->url());
				} else {
					App::user()->addFlash('warning', null, __('content', 'content-not-deleted', htmlspecialchars($content->title)));
				}
				return;
			}
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
			$options = array(
				'title'               => $this->request->getString('title'),
				'content'             => $this->request->getString('content'),
				'status'              => $this->request->getInt('status'),
				'parent'              => $this->request->getInt('parent', ''),
				'category'            => $this->request->getArray('category'),
				'tags'                => $this->request->getString('tags'),
				'publish_date'        => $publishDate,
				'archive_date'        => $archiveDate,
				'comments_disabled'   => !(bool)$this->request->getInt('comments'),
				'comment_anonymously' => (bool)$this->request->getInt('anonymous'),
				'rating_disabled'     => !(bool)$this->request->getInt('rating'),
				'featured'            => (bool)$this->request->getInt('featured'),
				'archiving'           => (bool)$this->request->getInt('archiving'),
				'last_editor'         => App::user()->account->id
			);
			$arguments = array( &$options, $frm, $content );
			if($this->call('parseForm', $arguments, true) !== false && $content->update($options)) {
				App::user()->addFlash('success', null, __('content', 'content-updated',
				                                          htmlspecialchars($this->contentType->name),
				                                          htmlspecialchars($content->title)));
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			App::user()->addFlash('error', null, __('application', 'unexpected-error'));
			$this->response->redirect($this->contentType->url());
		}
	}

	public function feed_action()
	{
		try {
			if(!$this->contentType->hasFilter('FeedFilter')) {
				$this->error(404);
				return;
			}
			$filter = $this->contentType->filter('FeedFilter');
			if($this->request->method === 'POST' && isset($this->request->data['x-delete-icon'])) {
				$this->response->status(302)->redirect(App::request()->uri->url());
				try {
					if($filter->getOption('icon')) {
						if(file_exists(\Aqua\ROOT . $filter->getOption('icon'))) {
							@unlink(\Aqua\ROOT . $filter->getOption('icon'));
						}
						$filter->setOption(array( 'icon' => null ));
					}
				} catch(\Exception $exception) {
					ErrorLog::logSql($exception);
					App::user()->addFlash('error', null, __('application', 'unexpected-error'));
				}
				return;
			} else if($this->request->method === 'POST' && isset($this->request->data['x-delete-image'])) {
				$this->response->status(302)->redirect(App::request()->uri->url());
				try {
					if($filter->getOption('image')) {
						if(file_exists(\Aqua\ROOT . $filter->getOption('image'))) {
							@unlink(\Aqua\ROOT . $filter->getOption('image'));
						}
						$filter->setOption(array( 'image' => null ));
					}
				} catch(\Exception $exception) {
					ErrorLog::logSql($exception);
					App::user()->addFlash('error', null, __('application', 'unexpected-error'));
				}
				return;
			}
			$frm = new Form($this->request);
			$frm->enctype = 'multipart/form-data';
			$frm->input('title')
				->type('text')
				->value(htmlspecialchars($filter->getOption('title', '')))
				->setLabel(__('content', 'feed-title'));
			$frm->input('description')
				->type('text')
				->value(htmlspecialchars($filter->getOption('description', '')))
				->setLabel(__('content', 'feed-description'));
			$frm->input('length')
				->type('number')
				->value($filter->getOption('length', null) ?: 0)
				->setLabel(__('content', 'feed-length'))
				->setDescription(__('content', 'feed-length-desc'));
			$frm->input('category')
				->type('text')
				->value(htmlspecialchars(implode(', ', $filter->getOption('categories', array()))))
				->setLabel(__('content', 'feed-category'))
				->setDescription(__('content', 'feed-category-desc'));
			$frm->input('copyright')
				->type('text')
				->value(htmlspecialchars($filter->getOption('copyright', '')))
				->setLabel(__('content', 'feed-copyright'));
			$frm->input('ttl')
				->type('number')
				->attr('min', 0)
				->value($filter->getOption('ttl', 1440))
				->setLabel(__('content', 'feed-ttl'))
				->setDescription(__('content', 'feed-ttl-desc'));
			$frm->input('limit')
				->type('number')
				->attr('min', 1)
				->attr('max', 100)
				->value($filter->getOption('limit', 10))
				->setLabel(__('content', 'feed-limit'))
				->setDescription(__('content', 'feed-limit-desc'));
			$frm->file('img')
				->accept('image/png', 'png')
				->accept('image/gif', 'gif')
				->accept('image/jpeg', array( 'jpeg', 'jpg' ))
				->accept('image/svg+xml', array( 'svg', 'svgx' ))
				->setLabel(__('content', 'feed-image'))
				->setDescription(__('content', 'feed-image-desc'));
			$frm->file('icon')
				->accept('image/x-icon', 'ico')
				->accept('image/png', 'png')
				->accept('image/gif', 'gif')
				->accept('image/jpeg', array( 'jpeg', 'jpg' ))
				->accept('image/svg+xml', array( 'svg', 'svgx' ))
				->setLabel(__('content', 'feed-icon'))
				->setDescription(__('content', 'feed-icon-desc'));
			$frm->validate();
			if($frm->status !== Form::VALIDATION_SUCCESS) {
				$this->title = __('content', 'feed-settings', htmlspecialchars($this->contentType->name));
				$tpl = new Template;
				$tpl->set('form', $frm)
					->set('page', $this);
				echo $tpl->render('admin/content/feed');
				return;
			}
			$this->response->status(302)->redirect(App::request()->uri->url());
			try {
				$options = array(
					'title'       => $this->request->getString('title'),
				    'description' => $this->request->getString('description'),
				    'copyright'   => $this->request->getString('copyright'),
				    'categories'  => array_unique(preg_split('/\s*,\s*/m', $this->request->getString('category'))),
				    'limit'       => $this->request->getInt('limit'),
				    'length'      => $this->request->getInt('length'),
				    'ttl'         => $this->request->getInt('ttl')
				);
				$x = umask(0);
				if(ac_file_uploaded('img', false)) {
					$path = '/uploads/content/' . uniqid() . '.' . pathinfo($_FILES['img']['name'], PATHINFO_EXTENSION);
					if(move_uploaded_file($_FILES['img']['tmp_name'], \Aqua\ROOT . $path)) {
						chmod(\Aqua\ROOT . $path, \Aqua\PUBLIC_FILE_PERMISSION);
						if($filter->getOption('image') && file_exists(\Aqua\ROOT . $filter->getOption('image'))) {
							@unlink(\Aqua\ROOT . $filter->getOption('image'));
						}
						$options['image'] = $path;
					}
				}
				if(ac_file_uploaded('icon', false)) {
					$path = '/uploads/content/' . uniqid() . '.' . pathinfo($_FILES['icon']['name'], PATHINFO_EXTENSION);
					if(move_uploaded_file($_FILES['icon']['tmp_name'], \Aqua\ROOT . $path)) {
						chmod(\Aqua\ROOT . $path, \Aqua\PUBLIC_FILE_PERMISSION);
						if($filter->getOption('icon') && file_exists(\Aqua\ROOT . $filter->getOption('icon'))) {
							@unlink(\Aqua\ROOT . $filter->getOption('icon'));
						}
						$options['icon'] = $path;
					}
				}
				umask($x);
				$filter->setOption($options);
			} catch(\Exception $exception) {
				ErrorLog::logSql($exception);
				App::user()->addFlash('error', null, __('application', 'unexpected-error'));
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function buildForm(ContentData $content = null)
	{
		switch($this->contentType->id) {
			case ContentType::CTYPE_POST:
				$settings = App::settings()->get('cms')->get('post');
				break;
			case ContentType::CTYPE_PAGE:
				$settings = App::settings()->get('cms')->get('page');
				break;
			default:
				if($this->contentType->pluginId) {
					$settings = Plugin::get($this->contentType->pluginId)->settings;
				} else if(!($settings = $this->call('settings', array(), true))) {
					$settings = new Settings;
				}
				break;
		}
		$options = array();
		if($this->contentType->hasFilter('FeaturedFilter')) {
			$options['featured'] = ($content ? $content->isFeatured() :
			                                   $settings->get('featured_by_default', false));
		}
		if($this->contentType->hasFilter('CommentFilter')) {
			$options+= array(
				'comments' => ($content ? !$content->meta->get('comments-disabled', false) :
						                  $settings->get('enable_comments_by_default', false)),
				'anonymous' => ($content ? $content->meta->get('comment-anonymously', false) :
						                   $settings->get('enable_anonymous_by_default', false))
			);
		}
		if($this->contentType->hasFilter('RatingFilter')) {
			$options['rating'] = ($content ? $content->meta->get('ratingDisabled', false) :
				                             $settings->get('enable_rating_by_default', false));
		}
		$frm = new Form($this->request);
		$frm->input('title', true)
		    ->type('text')
		    ->required()
			->value($content ? htmlspecialchars($content->title) : null, false)
		    ->setLabel(__('content', 'title'));
		$frm->textarea('content', false);
		if(($str = $frm->getString('content'))) {
			$frm->field('content')->append($str);
		} else if($content) {
			$frm->field('content')->append($content->content);
		}
		if($this->contentType->hasFilter('RelationshipFilter')) {
			$tree = $this->contentType->relationshipTree(array( 'title' ));
			if(!empty($tree)) {
				$field = $frm->select('parent')
					->value(array( '' => __('content', 'select-parent') ))
					->setLabel(__('content', 'parent'));
				if($content && ($parent = $content->parent())) {
					$field->selected($parent->uid);
				} else {
					$field->selected('');
				}
				$depth = 0;
				$fn = function($branch) use (&$content, &$field, &$fn, &$depth) {
					foreach($branch as $id => $data) {
						if($content && $id === $content->uid) continue;
						$field->value(array( $id => htmlspecialchars($data['title']) ));
						$field->option($id)->attr('ac-tree-depth', $depth);
						++$depth;
						$fn($data['children']);
						--$depth;
					}
				};
				$fn($tree);
			}
		}
		if($this->contentType->hasFilter('TagFilter')) {
			$frm->input('tags', true)
			    ->type('text')
				->value($content ? implode(', ', array_map('htmlspecialchars', $content->tags() ?: array())) : null, false)
			    ->setLabel(__('content', 'tags'))
			    ->setDescription(__('content', 'tags-desc'));
		}
		if($this->contentType->hasFilter('CategoryFilter')) {
			$categories = array();
			foreach($this->contentType->categories() as $category) {
				$categories[$category->id] = htmlspecialchars($category->name);
			}
			if(!empty($categories)) {
				$frm->checkbox('category', true)
				    ->multiple()
				    ->value($categories)
					->checked($content ? array_keys($content->categories() ?: array()) : null, false)
				    ->setLabel(__('content', 'category'));
			}
		}
		$status = $frm->select('status')
		    ->required()
		    ->value(array(
			            ContentData::STATUS_PUBLISHED => __('content-status', ContentData::STATUS_PUBLISHED),
			            ContentData::STATUS_DRAFT     => __('content-status', ContentData::STATUS_DRAFT)
		            ))
		    ->setLabel(__('content', 'status'));
		if($this->contentType->hasFilter('ScheduleFilter')) {
			$publishDate = ($content ? $content->publishDate : '');
			$frm->input('publish_date', true)
				->type('datetime')
				->attr('timestamp', $publishDate)
				->value($publishDate ? date('Y-m-d H:i:s', $publishDate) : null, false)
				->placeholder('YYYY-MM-DD HH:MM:SS')
				->setLabel(__('content', 'publish-date'));
		}
		if($this->contentType->hasFilter('ArchiveFilter')) {
			$archiveDate = ($content ? ($content->meta->get('archive-date') ?: '') : '');;
			$frm->field('status')->value(array(
					ArchiveFilter::STATUS_ARCHIVED => __('content-status', ArchiveFilter::STATUS_ARCHIVED)
				));
			$frm->input('archive_date', true)
			    ->type('datetime')
				->attr('timestamp', $archiveDate)
				->value($archiveDate ? date('Y-m-d H:i:s', $archiveDate) : null, false)
			    ->placeholder('YYYY-MM-DD HH:MM:SS')
			    ->setLabel(__('content', 'archive-date'));
			$options['archiving'] = ($content ? $content->meta->get('disable-archiving', false) :
			                                    $settings->get('enable_archiving_by_default', false));
		}
		if($content && array_key_exists($content->status, $status->values)) {
			$status->selected($content->status, false);
		}
		foreach($options as $name => $checked) {
			$frm->checkbox($name)
				->value(array( '1' => '' ))
				->setLabel(__('content', "enable-$name"))
				->checked($checked ? '1' : null, false);
		}
		$this->call('formData', array( $frm, $content ), true);
		return $frm;
	}
}
