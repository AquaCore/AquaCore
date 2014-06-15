<?php
use Aqua\Core\App;
use Aqua\Log\ErrorLog;
use Aqua\Plugin\Plugin;
use Aqua\Util\PayPalIPNListener;

!empty($_POST) or die;

define('Aqua\ROOT',        str_replace('\\', '/', rtrim(__DIR__, DIRECTORY_SEPARATOR)));
define('Aqua\SCRIPT_NAME', basename(__FILE__));
define('Aqua\ENVIRONMENT', 'MINIMAL');
define('Aqua\PROFILE',     'PAYPAL_IPN_LISTENER');

include 'lib/bootstrap.php';

try {
	Plugin::init();
	$listener = new PayPalIPNListener(App::request(), App::settings()->get('donation'));
	$listener->process();
	$listener->output.= "----------------------------------------------------\r\n";
	if(App::settings()->get('donation')->get('pp_log_requests', true)) {
		$file = \Aqua\ROOT . sprintf('/tmp/paypal.%d.%d.log', date('Y'), date('j'));
		file_put_contents($file, $listener->output, FILE_APPEND);
	}
} catch (Exception $exception) {
	ErrorLog::logSql($exception);
}
