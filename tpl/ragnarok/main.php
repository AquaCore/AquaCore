<?php
use Aqua\Core\App;
/**
 * @var $page \Page\Main\Ragnarok
 */
$page->response->status(301);
if($page->server->charmapCount === 1 || !App::user()->loggedIn()) {
	$charmap = current($page->server->charmap);
	$page->response->redirect($charmap->url());
} else {
	$page->response->redirect($page->server->url(array( 'action' => 'register' )));
}
