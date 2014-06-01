<?php
use Aqua\Core\App;
use Aqua\Core\L10n;

try {
	App::connection()->exec(str_replace('#', \Aqua\TABLE_PREFIX, file_get_contents(\Aqua\ROOT . '/schema/disablePlugin.sql')));
} catch(\Exception $e) { }

App::settings()->set('language', array(
		'name' => 'English',
	    'code' => 'en',
	    'direction' => 'LTR',
	    'locales' => array(
		    'en.UTF-8',
		    'en_US.UTF-8',
		    'English',
	    )
	));
App::settings()->export(\Aqua\ROOT . '/settings/application.php');
L10n::rebuildCache();
