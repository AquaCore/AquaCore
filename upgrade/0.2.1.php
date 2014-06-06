<?php
use Aqua\Core\App;
use Aqua\Content\ContentType;
use Aqua\User\Role;

try {
	App::connection()->exec(str_replace('#', \Aqua\TABLE_PREFIX, file_get_contents(\Aqua\ROOT . '/schema/disablePlugin.sql')));
} catch(\Exception $e) { }

Role::rebuildRoleCache();