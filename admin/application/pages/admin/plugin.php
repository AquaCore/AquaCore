<?php
namespace Page\Admin;

use Aqua\Core\App;
use Aqua\Core\Settings;
use Aqua\Log\ErrorLog;
use Aqua\Plugin\Exception\PluginException;
use Aqua\Plugin\Exception\PluginManagerException;
use Aqua\Plugin\Plugin as P;
use Aqua\Site\Page;
use Aqua\SQL\Search;
use Aqua\UI\Form;
use Aqua\UI\Pagination;
use Aqua\UI\Template;
use Aqua\UI\Theme;

class Plugin
extends Page
{
	const PLUGINS_PER_PAGE = 10;

	public function index_action()
	{
		$action = null;
		if($this->request->method === 'POST') {
			if($this->request->data('x-bulk')) {
				$action = $this->request->getString('action');
				$id     = $this->request->getArray('plugins');
			} else foreach(array( 'delete', 'activate', 'deactivate' ) as $act) {
				if($id = $this->request->getInt("x-$act")) {
					$action = $act;
					$id = array( $id );
					break;
				}
			}
		}
		if(in_array($action, array( 'activate', 'deactivate', 'delete' ), true) && !empty($id)) {
			$this->response->status(302)->redirect(App::request()->uri->url(array( 'query' => array() )));
			try {
				$plugins = array();
				foreach($id as $i) {
					if($plugin = P::get($i)) {
						try {
							switch($action) {
								case 'activate':
									if(!$plugin->enable()) {
										continue;
									}
									break;
								case 'deactivate':
									if(!$plugin->disable()) {
										continue;
									}
									break;
								case 'delete':
									if(!$plugin->delete()) {
										continue;
									}
									break;
							}
							$plugins[] = $plugin->name;
						} catch(PluginException $exception) {
							ErrorLog::logSql($exception);
							App::user()->addFlash('error', null, $exception->getMessage());
						}
					}
				}
				if(!empty($plugins)) {
					$count   = count($plugins);
					$plugins = implode(', ', $plugins);
					App::user()->addFlash('success', null,
					                      __('plugin', 'x-' . $action . '-' . ($count > 1 ? 'p' : 's'), $plugins,
					                         number_format($count)));
				}
			} catch(\Exception $exception) {
				ErrorLog::logSql($exception);
				App::user()->addFlash('error', null, __('application', 'unexpected-error'));
			}

			return;
		}
		try {
			$this->title = $this->theme->head->section = __('plugin', 'plugins');
			$upload = new Form($this->request);
			$upload->file('import')
			       ->accept(array(
		                'application/zip'    => '/\.zipx?$/i',
		                'application/x-tar'  => '/\.tar$/i',
		                'application/x-gtar' => '/\.t(ar\.)?(gz|bz2)$/i'
	                ))
			       ->setDescription(__('plugin', 'import-desc'));
			$upload->submit(__('upload', 'upload'));
			$upload->validate();
			if($upload->status === Form::VALIDATION_SUCCESS && ac_file_uploaded('import', false)) {
				$this->response->status(302)->redirect(App::request()->uri->url());
				try {
					preg_match('/\.(zipx?|tar|t(?:ar\.)?(?:gz|bz2))$/i', $_FILES['import']['name'], $match);
					$tmp = \Aqua\ROOT . '/tmp/' . uniqid() . $match[0];
					if(!move_uploaded_file($_FILES['import']['tmp_name'], $tmp)) {
						App::user()->addFlash('error', null, __('plugin', 'failed-to-import', __('upload', 'failed-to-move')));
						return;
					}
					$plugin = P::import("phar://$tmp");
					@unlink($tmp);
					App::user()->addFlash('success', null, __('plugin', 'plugin-imported', $plugin->name));
				} catch(PluginManagerException $exception) {
					ErrorLog::logSql($exception);
					App::user()->addFlash('error', null, __('plugin', 'failed-to-import', $exception->getMessage()));
				} catch(\Exception $exception) {
					ErrorLog::logSql($exception);
					App::user()->addFlash('error', null, __('application', 'unexpected-error'));
				}
				return;
			}
			if(\Aqua\ENVIRONMENT === 'DEVELOPMENT') {
				P::scanDir();
			}
			$currentPage = $this->request->uri->getInt('page', 1, 1);
			$search       = P::search()
				->calcRows(true)
				->order(array( 'id' => 'ASC' ))
				->limit(($currentPage - 1) * self::PLUGINS_PER_PAGE, self::PLUGINS_PER_PAGE)
				->query();
			$pgn = new Pagination(App::request()->uri, ceil($search->rowsFound / 10), $currentPage);
			$tpl = new Template;
			$tpl->set('plugins', $search->results)
			    ->set('plugin_count', $search->rowsFound)
				->set('upload', $upload)
			    ->set('paginator', $pgn)
			    ->set('page', $this);
			echo $tpl->render('admin/plugin/main');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function settings_action($id = null)
	{
		try {
			if(!$id || !($plugin = P::get($id)) || !($frm = $plugin->settings->buildForm($this->request))) {
				$this->error(404);

				return;
			}
			$frm->submit();
			$frm->validate();
			if($frm->status !== Form::VALIDATION_SUCCESS) {
				$this->title =
				$this->theme->head->section = __('plugin', 'x-plugin-settings', htmlspecialchars($plugin->name));
				$this->theme->set('return', ac_build_url(array( 'path' => array( 'plugin' ) )));
				$tpl         = new Template;
				$tpl->set('plugin', $plugin)
				    ->set('form', $frm)
				    ->set('page', $this);
				echo $tpl->render('admin/plugin/settings');

				return;
			}
			try {
				$error   = array();
				$message = '';
				if($frm->status === Form::VALIDATION_SUCCESS) {
					$updated = $plugin->settings->update(new Settings($this->request->data), $error, $message);
				} else {
					$updated = 0;
				}
				if($this->request->ajax) {
					$this->theme = new Theme;
					$this->response->setHeader('Content-Type', 'application/json');
					$response = array(
							'message' => '',
							'error' => $frm->status !== Form::VALIDATION_SUCCESS,
							'warning' => array()
						);
					foreach($frm->content as $key => $field) {
						if($field instanceof Form\FieldInterface && ($warning = $field->getWarning())) {
							$response['warning'][$key] = $warning;
						}
					}
					foreach($error as $key => $mes) {
						if($field = $frm->field($key)) {
							$response['warning'][$key] = $mes;
						}
					}
					if($frm->message) {
						$response['message'] = $frm->message;
					} else if($updated) {
						$response['message'] = __('plugin', 'settings-saved');
					}
					$response['data'] = $plugin->settings->toArray();
					echo json_encode($response);
				} else {
					$this->response->status(302)->redirect(App::request()->uri->url());
					$user = App::user();
					if(!empty($error)) {
						foreach($error as $key => $mes) {
							if($field = $frm->field($key)) {
								$user->addFlash('warning', null, $mes);
							} else {
								$user->addFlash('warning', $field->getLabel(), $mes);
							}
						}
					}
					if($message) {
						$user->addFlash('warning', null, $message);
					} else if($updated) {
						$user->addFlash('success', null, __('plugin', 'settings-saved'));
					}
				}
			} catch(\Exception $exception) {
				ErrorLog::logSql($exception);
				if($this->request->ajax) {
					$this->theme = new Theme;
					$this->response->setHeader('Content-Type', 'application/json');
					echo json_encode(array(
							'message' => __('application', 'unexpected-error'),
							'error'   => true,
							'warning' => array(),
							'data'    => $plugin->settings->toArray()
						));
				} else {
					App::user()->addFlash('error', null, __('application', 'unexpected-error'));
				}
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}
}
