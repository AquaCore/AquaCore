<?php
use Aqua\Core\App;
use Aqua\Content\ContentType;
use Aqua\Log\ErrorLog;

(isset($_GET['ctype']) && isset($_GET['comment']) && isset($_GET['weight'])) or die;

define('Aqua\ROOT',         str_replace('\\', '/', rtrim(__DIR__, DIRECTORY_SEPARATOR)));
define('Aqua\SCRIPT_NAME', basename(__FILE__));
define('Aqua\ENVIRONMENT', 'MINIMAL');
define('Aqua\PROFILE',     'RATING');

require_once 'lib/bootstrap.php';

$response = App::response();
$response->capture();
$response->setHeader('Content-Type', 'application/json');
$response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate');
$response->setHeader('Expires', time() - 100);
$weight      = App::request()->uri->getInt('weight');
$cType       = App::request()->uri->getInt('ctype');
$comment_id  = App::request()->uri->getInt('comment');
$rating  = null;
$success = false;
$status  = 200;
try {
	if(!App::user()->role()->hasPermission('rate')) {
		$status = 403;
	} else if(!($cType = ContentType::getContentType($cType)) ||
	          !($comment = $cType->getComment($comment_id))) {
		$status = 404;
	} else if($cType->hasFilter('ArchiveFilter') &&
	          $comment->content()->isArchived()) {
		$status = 403;
	} else if(($weight = $comment->rate(App::user()->account, $weight)) !== false) {
		$success = true;
		$rating = $comment->rating;
	}
} catch(\Exception $exception) {
	ErrorLog::logSql($exception);
	$status = 500;
}
$response->status($status);
echo json_encode(array(
		'success' => $success,
		'weight' => $weight,
		'rating' => $rating
	));
$response->send();
