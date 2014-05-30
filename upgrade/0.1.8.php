<?php
use Aqua\Core\App;

try {
	App::connection()->exec(str_replace('#', \Aqua\TABLE_PREFIX, file_get_contents(\Aqua\ROOT . '/schema/disablePlugin.sql')));
} catch(\Exception $e) { }
