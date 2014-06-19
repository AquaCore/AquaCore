<?php
use Aqua\Core\App;
use Aqua\Plugin\Plugin;
use Aqua\Ragnarok\Server;
use Aqua\Log\ErrorLog;

/**
 * Absolute path to AquaCore's root directory
 * @name \Aqua\ROOT
 */
define('Aqua\ROOT', str_replace('\\', '/', rtrim(__DIR__, DIRECTORY_SEPARATOR)));
/**
 * Base name of the currently running script
 * @name \Aqua\SCRIPT_NAME
 */
define('Aqua\SCRIPT_NAME', basename(__FILE__));
/**
 * Current script's identifier ("MAIN", "ADMINISTRATION", ...)
 * @name \Aqua\PROFILE
 */
define('Aqua\PROFILE', 'MAIN');
/**
 * Application's environment: "STANDARD", "DEVELOPMENT" or "MINIMAL"
 * @name \Aqua\ENVIRONMENT
 */
define('Aqua\ENVIRONMENT', 'STANDARD');

require_once 'lib/bootstrap.php';

$response = App::response();
$response->capture()->compression((bool)App::settings()->get('output_compression', false));

try {
	if(!\Aqua\HTTPS && App::settings()->get('ssl', 0) >= 2) {
		$response->status(301)->redirect(App::request()->uri->url(array( 'protocol' => 'https://' )));
	} else {
		App::autoloader('Page')->addDirectory(__DIR__ . '/application/pages');
		Server::init();
		Plugin::init();
		echo App::dispatcher()->dispatch(App::user(), App::response());
	}
} catch (Exception $exception) {
	$error = ErrorLog::logSql($exception);
	if(!headers_sent()) {
		$response->endCapture(false)->capture();
		$tpl = new \Aqua\UI\Template;
		$tpl->set('error', $error);
		echo $tpl->render('exception/layout');
	}
}
$response->send(true, App::request()->header('Accept-encoding'));
ignore_user_abort(true);
