<?php
use Aqua\Core\App;
use Aqua\Content\ContentType;

App::settings()->delete('rss');
App::settings()->export(\Aqua\ROOT . '/settings/application.php');

ContentType::rebuildCache();
ContentType::getContentType(ContentType::CTYPE_POST)->filter('FeedFilter')->setOption(array( 'title' => App::settings()->get('title') ));
