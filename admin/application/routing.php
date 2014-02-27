<?php
use Aqua\Core\App;
use Aqua\Ragnarok\Server;
use Aqua\Router\Router;

$router = new Router;

$router->add('Admin')->map('/*', '/admin/:path');
if(Server::$serverCount > 0) {
	$servers = implode('|', array_keys(Server::$servers));
	// /ro/<server>
	$router->add('Ragnarok Server Name')
		->map("/ro/:server[$servers]/*", '/admin/ragnarok/:path')
		->attach('parse_ok', function($event, $match) {
				App::$activeServer = Server::get($match['server']);
			});
	foreach(Server::$servers as $server) {
		if($server->charmapCount) {
			$charmap = implode('|', array_map('preg_quote', array_keys($server->charmap), array( '/' )));
			$router->add('Ragnarok Server Name (' . $server->key . ') - CharMap')
			->map("/ro/:server[{$server->key}]/s/:charmap[$charmap]/*", '/admin/ragnarok/server/:path')
			->attach('parse_ok', function($event, $match) {
						if(App::$activeServer = Server::get($match['server'])) {
							App::$activeCharMapServer = App::$activeServer->charmap($match['charmap']);
						}
					});
		}
	}
}

return $router;