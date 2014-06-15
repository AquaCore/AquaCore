<?php
use Aqua\Core\App;

App::settings()->get('donation')
	->set('pp_log_requests', true)
	->set('pp_txn_types', array( 'web_accept' ));
App::settings()->export(\Aqua\ROOT . '/settings/application.php');
