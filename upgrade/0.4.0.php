<?php
use Aqua\Core\App;
use Aqua\Content\ContentType;

if(!App::settings()->exists('themes')) {
	App::settings()->set('themes', array(
		'/' => array(
			'theme' => 'default',
		    'options' => array()
		)
	));
	App::settings()->export(\Aqua\ROOT . '/application/settings.php');
}

ContentType::rebuildCache();
foreach(ContentType::contentTypes() as $cType) {
	if($cType->hasFilter('FeedFilter')) $cType->filter('FeedFilter')->clearCache();
}