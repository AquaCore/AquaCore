<?php
use Aqua\Core\App;
use Aqua\Content\ContentType;
use Aqua\Ragnarok\Server;
use Aqua\Router\Router;

$setActiveContentType = function($e, $match)
{
	App::$activeContentType = ContentType::getContentType($match['ctype'], 'name');
};
$setActiveServer = function($e, $match)
{
	App::$activeServer = Server::get($match['server']);
	if(App::$activeServer && isset($match['charmap'])) {
		App::$activeCharMapServer = App::$activeServer->charmap($match['charmap']);
	}
};

$router = new Router;

$router->add('Admin')->map('/*', '/admin/:path');
$cType = ContentType::contentTypes();
foreach(ContentType::contentTypes() as $cType) {
	$contentTypes[] = $cType->key;
}
$contentTypes = implode('|', $contentTypes);
$router->add('Comment')
	->map("/comments/*", '/admin/content/comments/:path');
$router->add('Content')
	->map("/:ctype[$contentTypes]/*", '/admin/content/:path')
	->attach('parse_ok', $setActiveContentType);
if(Server::$serverCount > 0) {
	$servers = implode('|', array_keys(Server::$servers));
	// /ro/<server>
	$router->add('Ragnarok Server Name')
		->map("/r/:server[$servers]/*", '/admin/ragnarok/:path')
		->attach('parse_ok', $setActiveServer);
	foreach(Server::$servers as $server) {
		if($server->charmapCount) {
			$charmap = implode('|', array_map('preg_quote', array_keys($server->charmap), array( '/' )));
			$router->add('Ragnarok Server Name (' . $server->key . ') - CharMap')
			->map("/r/:server[{$server->key}]/:charmap[$charmap]/*", '/admin/ragnarok/server/:path')
			->attach('parse_ok', $setActiveServer);
		}
	}
}
$router->add('404')->map('content', '404');

return $router;