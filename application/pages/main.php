<?php
namespace Page;

use Aqua\Core\App;
use Aqua\Forum\Platform\IPB;
use Aqua\Ragnarok\MapMarker;
use Aqua\Site\Page;
use Aqua\UI\Form;
use Aqua\UI\ScriptManager;
use Aqua\UI\Template;
use Aqua\UI\Theme;
use Aqua\User\Account;
use Aqua\User\Exception\AccountException;
use Aqua\User\Role;
use CharGen\Client;
use CharGen\DB;
use Phpass\Hash;

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