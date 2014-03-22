<?php
namespace Page;

use Aqua\Core\App;
use Aqua\Site\Page;
use Aqua\UI\Template;
use Aqua\UI\Theme;

class Main
extends Page
{
	public function run()
	{
		$this->theme = new Theme(App::settings()->get('theme', 'default'));
		$this->theme->head->title = htmlspecialchars(App::settings()->get('title', 'AquaCore'));
	}

	public function index_action()
	{
		$tpl = new Template;
		$tpl->set('page', $this);
		echo $tpl->render('main');
	}
}
