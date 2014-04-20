<?php
use Aqua\Core\App;

App::settings()->set('cron_key', bin2hex(secure_random_bytes(32)));
App::settings()->set('tasks', true);
App::settings()->export(\Aqua\ROOT . '/settings/application.php');