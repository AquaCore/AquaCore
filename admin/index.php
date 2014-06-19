<?php
use Aqua\Core\App;
use Aqua\Log\ErrorLog;
use Aqua\Plugin\Plugin;
use Aqua\Ragnarok\Server;
use Aqua\UI\Menu;

define('Aqua\ROOT', str_replace('\\', '/', rtrim(dirname(__DIR__), DIRECTORY_SEPARATOR)));
define('Aqua\SCRIPT_NAME', basename(__FILE__));
define('Aqua\PROFILE', 'ADMINISTRATION');
define('Aqua\ENVIRONMENT', 'DEVELOPMENT');

require_once '../lib/bootstrap.php';
$response = App::response();
$response->capture()->compression(true);
try {
	if(!\Aqua\HTTPS && App::settings()->get('ssl', 0) >= 1) {
		$response->status(301)->redirect(App::request()->uri->url(array( 'protocol' => 'https://' )));
	} else {
		$role = App::user()->role();
		App::autoloader('Page')->addDirectory(__DIR__ . '/application/pages');
		Server::init();
		$viewRagnarokMenuAdmin = $role->hasPermission('edit-server-settings');
		$viewRagnarokMenu      = $viewRagnarokMenuAdmin;
		if(!$viewRagnarokMenuAdmin) foreach(array( 'edit-server-settings',
		                                           'edit-server-user',
		                                           'view-server-acc',
		                                           'view-user-items',
		                                           'ban-server-user',
		                                           'view-server-logs' ) as $permission) {
			if($role->hasPermission($permission)) {
				$viewRagnarokMenu = true;
				break;
			}
		}
		if($viewRagnarokMenuAdmin) {
			$ragnarokSubmenu = array(array(
				'title' => __('admin-menu', 'ragnarok-add-server'),
				'url'   => ac_build_url(array( 'path' => array( 'ragnarok' ) ))
			));
		}
		if($viewRagnarokMenu) foreach(Server::$servers as $server) {
			$serverSubmenu = array();
			if($server->charmapCount) {
				$serverSubmenu['url'] = ac_build_url(array( 'path' => array( 'r', $server->key ) ));
				$charmaps = array();
				foreach($server->charmap as $charmap) {
					$charmaps[] = array(
						'title' => htmlspecialchars($charmap->name),
						'url' => ac_build_url(array( 'path' => array( 'r', $server->key, $charmap->key ) ))
					);
				}
				$serverSubmenu['submenu'] = $charmaps;
			} else if(!$viewRagnarokMenuAdmin) {
				continue;
			} else {
				$serverSubmenu['url'] = ac_build_url(array( 'path' => array( 'r', $server->key ) ));
			}
			$serverSubmenu['title'] = htmlspecialchars($server->name);
			$ragnarokSubmenu[] = $serverSubmenu;
		}
		$menu = new Menu;
		$menu->append('dashboard', array(
				'class' => array( 'option-dashboard' ),
				'title' => __('admin-menu', 'dashboard'),
				'url'   => \Aqua\WORKING_URL
			));
		if(App::user()->role()->hasPermission('edit-comments')) {
			$menu->append('comments', array(
				'class' => array( 'option-comments' ),
			    'title' => __('admin-menu', 'comments'),
			    'url'   => ac_build_url(array( 'path' => array( 'content', 'comments' ) ))
			));
		}
		if($role->hasPermission('create-pages')) {
			$menu->append('pages', array(
					'class'   => array( 'option-pages' ),
					'title'   => __('admin-menu', 'pages'),
					'url'     => ac_build_url(array( 'path' => array( 'page' ) )),
					'submenu' => array(
					array(
						'title' => __('admin-menu', 'pages'),
						'url'   => ac_build_url(array( 'path' => array( 'page' ) )),
					),
					array(
						'title' => __('admin-menu', 'new-page'),
						'url'   => ac_build_url(array( 'path' => array( 'page' ), 'action' => 'new' )),
					)
				)));
		}
		if($role->hasPermission('publish-posts')) {
			$menu->append('posts', array(
					'class'   => array( 'option-posts' ),
					'title'   => __('admin-menu', 'news'),
					'url'     => ac_build_url(array( 'path' => array( 'news' ) )),
					'submenu' => array(
					array(
						'title' => __('admin-menu', 'news-posts'),
						'url'   => ac_build_url(array( 'path' => array( 'news' ) )),
					),
					array(
						'title' => __('admin-menu', 'news-new-post'),
						'url'   => ac_build_url(array( 'path' => array( 'news' ), 'action' => 'new' )),
					),
					array(
						'title' => __('admin-menu', 'news-categories'),
						'url'   => ac_build_url(array( 'path' => array( 'news', 'category' ) )),
					)
				)));
			if(App::user()->role()->hasPermission('edit-comments')) {
				$item = $menu->get('posts');
				$item['submenu']->append('comments', array(
					'title' => __('admin-menu', 'comments'),
					'url'   => ac_build_url(array( 'path' => array( 'news', 'comments' ) )),
				));
			}
		}
		$submenu = array(array(
			'title' => __('admin-menu', 'users'),
			'url'   => ac_build_url(array( 'path' => array( 'user' ), 'action' => 'index' )),
		));
		if($role->hasPermission('manage-roles')) {
			$submenu[] = array(
				'title' => __('admin-menu', 'users-roles'),
				'url'   => ac_build_url(array( 'path' => array( 'role' ) )),
			);
		}
		$menu->append('users', array(
				'class'   => array( 'option-users' ),
				'title'   => __('admin-menu', 'users'),
				'url'     => ac_build_url(array( 'path' => array( 'user' ) )),
				'submenu' => $submenu
			));
		if(!empty($ragnarokSubmenu)) {
			$menu->append('ragnarok', array(
					'class'   => array( 'option-ragnarok' ),
					'title'   => __('admin-menu', 'ragnarok'),
					'url'     => '#',
					'submenu' => $ragnarokSubmenu
				));
		}
		if($role->hasPermission('manage-plugins')) {
			$menu->append('plugins', array(
					'class' => array( 'option-plugins' ),
					'title' => __('admin-menu', 'plugins'),
					'url'   => ac_build_url(array( 'path' => array( 'plugin' ) ))
				));
		}
		if($role->hasPermission('manage-tasks')) {
			$menu->append('tasks', array(
					'class' => array( 'option-tasks' ),
					'title' => __('admin-menu', 'tasks'),
					'url'   => ac_build_url(array( 'path' => array( 'task' ) )),
			        'submenu' => array(
				        array(
					        'title' => __('admin-menu', 'tasks'),
					        'url'   => ac_build_url(array( 'path' => array( 'task' ) )),
				        ),
				        array(
					        'title' => __('admin-menu', 'task-log'),
					        'url'   => ac_build_url(array( 'path' => array( 'task' ), 'action' => 'log' )),
				        )
			        )
				));
		}
		if($role->hasPermission('view-cp-logs')) {
			$menu->append('logs', array(
					'class'   => array( 'option-logs' ),
					'title'   => __('admin-menu', 'logs'),
					'url'     => '#',
					'submenu' => array(
					array(
						'title' => __('admin-menu', 'login-log'),
						'url'   => ac_build_url(array( 'path' => array( 'log' ), 'action' => 'login' ))
					),
					array(
						'title' => __('admin-menu', 'ban-log'),
						'url'   => ac_build_url(array( 'path' => array( 'log' ), 'action' => 'ban' ))
					),
					array(
						'title' => __('admin-menu', 'pp-log'),
						'url'   => ac_build_url(array( 'path' => array( 'log' ), 'action' => 'paypal' ))
					),
					array(
						'title' => __('admin-menu', 'credit-log'),
						'url'   => ac_build_url(array( 'path' => array( 'log' ), 'action' => 'credit' ))
					),
					array(
						'title' => __('admin-menu', 'error-log'),
						'url'   => ac_build_url(array( 'path' => array( 'log' ), 'action' => 'error' ))
					)
				)));
		}
		if($role->hasPermission('edit-cp-settings')) {
			$menu->append('settings', array(
					'class' => array( 'option-settings' ),
					'title' => __('admin-menu', 'settings'),
					'url'   => ac_build_url(array( 'path' => array( 'settings' ) )),
			        'submenu' => array(
				        array(
					        'title' => __('admin-menu', 'settings'),
				            'url' => ac_build_url(array( 'path' => array( 'settings' ) )),
				        ),
			            array(
				            'title' => __('admin-menu', 'smileys'),
				            'url' => ac_build_url(array( 'path' => array( 'bbcode' ), 'action' => 'smiley' )),
			            ),
			            array(
				            'title' => __('admin-menu', 'emails'),
				            'url' => ac_build_url(array( 'path' => array( 'mail' ) )),
			            )
			        )
				));
		}
		App::registrySet('adminMenu', $menu);
		Plugin::init();
		echo App::dispatcher()->dispatch(App::user(), App::response());
	}
} catch(Exception $exception) {
	$error = ErrorLog::logSql($exception);
	if(!headers_sent()) {
		$response->endCapture(false)->capture();
		$tpl = new \Aqua\UI\Template;
		$tpl->set('error', $error);
		echo $tpl->render('exception/layout');
	}
}
$response->send(true, App::request()->header('Accept-encoding'));
ignore_user_abort(true);
