<?php
namespace Page\Admin;

use Aqua\Core\App;
use Aqua\Log\ErrorLog;
use Aqua\Site\Page;
use Aqua\UI\Form;
use Aqua\UI\FormXML;
use Aqua\UI\Template;

class Theme
extends Page
{
	public function index_action($path = '')
	{
		try {
			if(!($dir = $this->getDir($path))) {
				$this->error(404);
				return;
			}
			if($this->request->method === 'POST' && ($theme = $this->request->data('theme'))) {
				$this->response->status(302)->redirect(App::request()->uri->url());
				try {
					$settings = App::settings()->get('themes')->get($path);
					if(is_dir("$dir/$theme") && file_exists("$dir/$theme/layout.php")) {
						if(isset($this->request->data['x-enable'])) {
							$settings->set('theme', $theme);
							$settings->set('options', array());
						} else if(isset($this->request->data['x-delete'])) {
							if($settings->get('theme') === $theme) {
								$settings->set('theme', '');
								$settings->set('options', array());
							}
							try {
								ac_delete_dir("$dir/$theme", true);
							} catch(\Exception $e) {
								App::user()->addFlash('error', null, __('theme', 'failed-to-delete', $e->getMessage()));
							}
						} else if(isset($this->request->data['x-disable']) && $settings->get('theme', '') === $theme) {
							$settings->set('theme', '');
							$settings->set('options', array());
						} else {
							return;
						}
						App::settings()->export(\Aqua\ROOT . '/settings/application.php');
					}
				} catch(\Exception $exception) {
					ErrorLog::logSql($exception);
					App::user()->addFlash('error', null, __('application', 'unexpected-error'));
				}
				return;
			}
			$frm = new Form($this->request);
			$frm->file('theme')
				->accept('application/x-tar', 'tar')
				->accept(array( 'application/gzip',
				                'application/x-gzip',
				                'application/x-gtar',
				                'application/x-gtar-compressed',
				                'application/x-compressed-tar' ), '/\.t(ar\.)?gz$/i' )
				->accept(array( 'application/x-bzip2',
				                'application/x-gtar',
				                'application/x-gtar-compressed',
				                'application/x-bzip2-compressed-tar' ), '/\.t(ar\.)?bz2$/i' )
				->accept(array( 'application/zip',
				                'application/x-zip',
				                'application/x-zip-compressed' ), 'zip')
				->setDescription(__('theme', 'upload-desc'));
			$frm->validate();
			if($frm->status == Form::VALIDATION_SUCCESS && ac_file_uploaded('theme', false)) {
				$this->response->status(302)->redirect(App::request()->uri->url());
				try {
					$this->importTheme($dir, 'theme');
					App::user()->addFlash('success', null, __('theme', 'theme-added'));
				} catch(\Exception $exception) {
					ErrorLog::logSql($exception);
					App::user()->addFlash('error', null, __('application', 'unexpected-error'));
				}
			}
			$themes = array();
			foreach(glob("$dir/*", GLOB_ONLYDIR) as $themeDir) {
				if(file_exists("$themeDir/layout.php")) {
					$theme = array(
						'name'      => basename($themeDir),
						'baseName'  => basename($themeDir),
					    'directory' => $themeDir,
					    'options'   => file_exists("$themeDir/settings.php") || file_exists("$themeDir/settings.xml"),
					    'author'    => null,
					    'authorUrl' => null,
					    'version'   => null,
					    'thumb'     => null
					);
					if(file_exists("$themeDir/theme.ini") && ($ini = @parse_ini_file("$themeDir/theme.ini"))) {
						foreach(array( 'name', 'author', 'authorUrl', 'version' ) as $key) {
							if(array_key_exists($key, $ini) && is_string($ini[$key])) {
								$theme[$key] = $ini[$key];
							}
						}
					}
					$thumb = glob("$themeDir/thumb.{png,gif,jpg,jpeg}", GLOB_BRACE);
					if(!empty($thumb)) {
						$theme['thumb'] = current($thumb);
						$theme['thumb'] = substr($theme['thumb'], strlen(\Aqua\ROOT));
					}
					$themes[] = $theme;
				}
			}
			$this->title = $this->theme->head->section = __('theme', 'themes');
			$tpl = new Template;
			$tpl->set('path', $path)
				->set('themes', $themes)
				->set('upload', $frm)
				->set('page', $this);
			echo $tpl->render('admin/theme/main');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function edit_action($path = '')
	{
		try {
			$settings = App::settings()->get('themes');
			if(!($dir = $this->getDir($path)) || !$settings->get($path)->get('theme', '')) {
				$this->error(404);
				return;
			}
			$this->title = $this->theme->head->section = __('theme', 'theme-options', __('theme', $path));
			$this->theme->set('return', ac_build_url(array( 'path' => array( 'theme' ) )));
			$settings = $settings->get($path);
			$themeDir = $dir . '/' . $settings->get('theme', '');
			if(file_exists("$themeDir/settings.php")) {
				$options = $settings->get('options');
				include "$themeDir/settings.php";
			} else if(file_exists("$themeDir/settings.xml")) {
				$frm = new FormXML($this->request,
				                   new \SimpleXMLElement(file_exists("$themeDir/settings.xml")),
				                   $settings->get('options'));
				$frm->validate();
				if($frm->status === FormXML::VALIDATION_SUCCESS) {
					$this->response->status(302)->redirect(App::request()->uri->url());
					try {
						$options = array();
						foreach($this->request->data as $key => $data) {
							if(array_key_exists($key, $frm->content)) {
								$options[$key] = $data;
							}
						}
						$settings->set('options', $options);
						App::settings()->export(\Aqua\ROOT . '/settings/application.php');
						App::user()->addFlash('success', null, __('settings', 'settings-saved'));
					} catch(\Exception $exception) {
						ErrorLog::logSql($exception);
						App::user()->addFlash('error', null, __('application', 'unexpected-error'));
					}
					return;
				} else {
					$tpl = new Template;
					$tpl->set('path', $path)
						->set('theme', $settings)
						->set('form', $frm)
						->set('page', $this);
					echo $tpl->render('admin/theme/options');
				}
			} else {
				$this->error(404);
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	protected function getDir(&$path)
	{
		$settings = App::settings()->get('themes');
		$path = '/' . trim($path, '/');
		if(!$settings->exists($path)) {
			return false;
		}
		if($settings->get($path)->exists('location')) {
			$dir = $settings->get($path)->exists('location');
		} else {
			$dir = \Aqua\ROOT . rtrim($path, '/') . '/application/themes';
		}
		if(!is_dir($dir)) {
			return false;
		}
		return $dir;
	}

	protected function importTheme($dir, $key)
	{
		preg_match('/\.(zipx?|tar|t(?:ar\.)?(?:gz|bz2))$/i', $_FILES[$key]['name'], $match);
		$tmp = \Aqua\ROOT . '/tmp/' . uniqid() . $match[0];
		if(!move_uploaded_file($_FILES[$key]['tmp_name'], $tmp)) {
			App::user()->addFlash('error', null, __('upload', 'failed-to-move'));
			return;
		}
		if(preg_match('/[<>:"\x00\/\\\|\?\*]/', $_FILES[$key]['name'])) {
			$name = uniqid();
		} else {
			$name = substr(trim(basename($_FILES[$key]['name'], $match[0]), '.'), 0, 255);
		}
		if(is_dir("$dir/$name")) {
			$max = 1;
			$len = strlen($name) + 1;
			foreach(glob("$dir/$name-?", GLOB_ONLYDIR) as $themeDir) {
				$baseName = basename($themeDir);
				$num = substr($baseName, $len);
				if(ctype_digit($num) && intval($num) > $max) {
					$max = intval($num);
				}
			}
			if($len >= 255) {
				$name = substr($name, 0, strlen($name) - strlen((string)$max) - 1);
			}
			$name.= "-$max";
		}
		$x = umask(0);
		mkdir("$dir/$name", \Aqua\PUBLIC_DIRECTORY_PERMISSION);
		$iter = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator(
				"phar://$tmp",
				\RecursiveDirectoryIterator::SKIP_DOTS
			), \RecursiveIteratorIterator::SELF_FIRST);
		foreach($iter as $item) {
			if($item->isDir()) {
				mkdir("$dir/$name/" . $iter->getSubPathName(), \Aqua\PUBLIC_DIRECTORY_PERMISSION);
			}
			else {
				$path = "$dir/$name/" . $iter->getSubPathName();
				copy($item, $path);
				chmod($path, \Aqua\PUBLIC_FILE_PERMISSION);
			}
		}
		umask($x);
		@unlink($tmp);
	}
}
