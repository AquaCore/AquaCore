<?php
use Aqua\Core\App;
use Aqua\Log\ErrorLog;
use Aqua\Permission\PermissionSet;
use Aqua\Router\Router;
use Aqua\Site\Dispatcher;
use Aqua\UI\Form;
use Aqua\UI\Template;

define('Aqua\ROOT', str_replace('\\', '/', dirname(__DIR__)));
define('Aqua\SCRIPT_NAME', basename(__FILE__));
define('Aqua\PROFILE', 'INSTALLER');
define('Aqua\ENVIRONMENT', 'MINIMAL');
define('Aqua\REWRITE', false);

include __DIR__ . '/../lib/bootstrap.php';
include __DIR__ . '/AquaCoreSetup.php';
include __DIR__ . '/functions.php';

App::response()->capture();

function __setup($key) {
	$arguments = func_get_args();
	array_shift($arguments);
	return App::registryGet('setup')->translate($key, $arguments);
}

try {
	$setup = new AquaCoreSetup(App::request(), App::response());
	App::registrySet('setup', $setup);
	App::autoloader('Page')->addDirectory(__DIR__ . '/application/pages');
	$perm = new PermissionSet;
	$perm->set('setup')->allowAll();
	$router = new Router;
	$router->add('setup')->map('/*', '/setup/:path');
	$dispatcher = new Dispatcher($router, $perm);
	App::registrySet('ac_dispatcher', $dispatcher);
	echo $dispatcher->dispatch(App::user(), App::response());
	$setup->commit();
} catch(Exception $exception) {
	$error = ErrorLog::logText($exception);
	App::response()->endCapture(false)->capture();
	$tpl = new Template;
	$tpl->set('error', $error);
	echo $tpl->render('exception/layout');
}
App::response()->send();
