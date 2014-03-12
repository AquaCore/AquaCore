<?php
namespace Page;

use Aqua\Core\App;
use Aqua\Site\Page;
use Aqua\UI\Form;
use Aqua\UI\Template;
use Aqua\UI\Theme;

class Admin
extends Page
{
	public function run()
	{
		$this->theme = new Theme('admin');
		$this->theme->set('admin_menu',    App::registryGet('ac_admin_menu'));
		$this->theme->head->title = __('application', 'cp-title');
		$this->response->setHeader('Cache-Control', 'no-store, co-cache, must-revalidate, max-age=0');
		$this->response->setHeader('Expires', time() - 1);
	}

	public function index_action()
	{
		$this->title = __('dashboard', 'dashboard');
		$tpl = new Template;
		$tpl->set('page', $this);
		echo $tpl->render('admin/dashboard');
	}
}
