<?php
use Aqua\Core\App;
use Aqua\Http\Request;
use Aqua\Ragnarok\Server\CharMap;
use Aqua\Ragnarok\Server\Login;
use Aqua\Ragnarok\Server;
use Aqua\Router\Router;

$router = new Router;

$router->add('Main Site')
	->map('/*', '/main/:path');
$router->add('Pages')
	->map("/page/:slug/", '/main/page/action/index/:slug');
$router->add('News')
	->map("/news/:slug/", '/main/news/action/view/:slug');
$router->add('News Tags')
	->map("/news/tagged/:tag/", '/main/news/action/tagged/:tag');
$router->add('News Categories')
	->map("/news/category/:category/", '/main/news/action/category/:category');
$_404 = 'ragnarok/account|ragnarok/server/char';
if(Server::$serverCount > 0) {
	$servers = implode('|', array_keys(Server::$servers));
	// /ro/<server>
	$router->add('Ragnarok Server Name')
		->map("/ro/:server[$servers]/*", '/main/ragnarok/:path')
		->attach('parse_ok', function($event, $match, Request $request) {
			App::$activeServer = Server::get($match['server']);
		});
	// /ro/<server>/a/<username|id>/*
	$router->add('Ragnarok Server Name - Account')
		->map("/ro/:server[$servers]/a/:username/*", '/main/ragnarok/account/:path')
		->attach('parse_ok', function($event, $match, Request $request) {
			if(App::$activeServer = Server::get($match['server'])) {
				App::$activeRagnarokAccount = App::$activeServer->login->get(
					$match['username'],
					App::settings()->get('ragnarok')->get('acc_username_url', false) ? 'username' : 'id'
				);
			}
		});
	foreach(Server::$servers as $server) {
	// /ro/<server>/c/<char server>/*
		if($server->charmapCount) {
			$charmap = implode('|', array_keys($server->charmap));
		$router->add('Ragnarok Server Name (' . $server->key . ') - Char')
			->map("/ro/:server[{$server->key}]/s/:charmap[$charmap]/*", '/main/ragnarok/server/:path')
			->attach('parse_ok', function($event, $match, Request $request) {
				if(App::$activeServer = Server::get($match['server'])) {
					App::$activeCharMapServer = App::$activeServer->charmap($match['charmap']);
				}
			});
		}
	}
	reset(Server::$servers);

	// Single server
	if(Server::$serverCount === 1) {
		App::$activeServer = current(Server::$servers);
	// /ragnarok/a/<username|id>/*
		$router->add('Ragnarok - Account')
			->map("/ragnarok/a/:username/*", '/main/ragnarok/account/:path')
			->attach('parse_ok', function($event, $match, Request $request) {
				App::$activeRagnarokAccount = App::$activeServer->login->get(
					$match['username'],
					App::settings()->get('ragnarok')->get('acc_username_url', false) ?
						'username' : 'id'
				);
			});
	// /ragnarok/c/<char server>/*
		if(App::$activeServer->charmapCount > 1) {
			$charmap = implode('|', array_keys(App::$activeServer->charmap));
			$router->add('Ragnarok - CharMap')
				->map("/ragnarok/s/:charmap[$charmap]/*", '/main/ragnarok/server/:path')
				->attach('parse_ok', function($event, $match, Request $request) {
					App::$activeCharMapServer = App::$activeServer->charmap($match['charmap']);
				});
			$router->add('Ragnarok - Char')
				->map("/ragnarok/s/:charmap[$charmap]/c/:char/*", '/main/ragnarok/server/char/:path')
				->attach('parse_ok', function($event, $match, Request $request) {
					if(App::$activeCharMapServer = App::$activeServer->charmap($match['charmap'])) {
						App::$activeRagnarokCharacter = App::$activeCharMapServer->character(
							$match['char'],
							App::settings()->get('ragnarok')->get('char_name_url', false) ?
								'name' : 'id'
						);
					}
				});
		} else if(App::$activeServer->charmapCount) {
			App::$activeCharMapServer = current(App::$activeServer->charmap);
			$router->add('Ragnarok - Char')
				->map("/ragnarok/server/c/:char/*", '/main/ragnarok/server/char/:path')
				->attach('parse_ok', function($event, $match, Request $request) {
					App::$activeRagnarokCharacter = App::$activeCharMapServer->character(
						$match['char'],
						App::settings()->get('ragnarok')->get('char_name_url', false) ?
							'name' : 'id'
					);
				});
		}
	} else {
		$_404 .= '|ragnarok/server';
	}
}

$router->add('404')
	->map('/:n[' . $_404 . ']/*', '404');

return $router;
