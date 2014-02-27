<?php
use Aqua\Captcha\Captcha;
use Aqua\Core\App;
use Aqua\Log\ErrorLog;

define('Aqua\ROOT',         str_replace('\\', '/', rtrim(__DIR__, DIRECTORY_SEPARATOR)));
define('Aqua\SCRIPT_NAME', basename(__FILE__));
define('Aqua\ENVIRONMENT', 'MINIMAL');
define('Aqua\PROFILE',     'CAPTCHA');

require_once 'lib/bootstrap.php';

$settings = App::settings()->get('captcha');

if($settings->get('use_recaptcha', false)) { die; }

$response = App::response();
$request = App::request();
$response->capture();
$response->setHeader('Content-Type', 'image/png');
$response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate');
$response->setHeader('Expires', time() - 100);
$error_image = \Aqua\ROOT . '/assets/images/captcha_error.png';
try {
	if(!($id = $request->uri->getString('id'))) {
		echo file_get_contents($error_image);
	} else if($request->uri->getString('x') === 'refresh') {
		App::captcha()->refresh($id, App::request()->ip);
		die;
	} else if(!App::captcha()->render($id, App::request()->ip)) {
		echo file_get_contents($error_image);
	}
} catch(\Exception $exception) {
	ErrorLog::logSql($exception);
	echo file_get_contents($error_image);
}
$response->send();
