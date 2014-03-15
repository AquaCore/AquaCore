<?php
use Aqua\Core\App;
use Aqua\Content\ContentType;
use Aqua\Log\ErrorLog;

(isset($_GET['ctype']) && isset($_GET['id']) && isset($_GET['weight'])) or die;

define('Aqua\ROOT',         str_replace('\\', '/', rtrim(__DIR__, DIRECTORY_SEPARATOR)));
define('Aqua\SCRIPT_NAME',  basename(__FILE__));
define('Aqua\ENVIRONMENT', 'MINIMAL');
define('Aqua\PROFILE',     'RATING');

require_once 'lib/bootstrap.php';

$response = App::response();
$response->capture();
$response->setHeader('Content-Type', 'application/json');
$response->setHeader('Cache-Control', 'no-store, co-cache, must-revalidate, max-age=0');
$response->setHeader('Expires', time() - 1);
$weight  = App::request()->uri->getInt('weight');
$ctype   = App::request()->uri->getInt('ctype');
$cid     = App::request()->uri->getInt('id');
$rating  = null;
$success = false;
$status  = 200;
try {
	if(!App::user()->role()->hasPermission('rate')) {
		$status = 403;
	} else if(!($ctype = ContentType::getContentType($ctype)) ||
	          !($content = $ctype->get($cid))) {
		$status = 404;
	} else if($ctype->hasFilter('ArchiveFilter') &&
	          $content->isArchived()) {
		$status = 403;
	} else {
		$weight = $content->rate(App::user()->account, $weight);
		$success = true;
		$rating = $content->ratingAverage();
	}
} catch(\Exception $exception) {
	ErrorLog::logSql($exception);
	var_dump($exception);
	$status = 500;
}
$response->status($status);
echo json_encode(array(
		'success' => $success,
		'weight' => $weight,
		'average' => $rating
	));
$response->send();