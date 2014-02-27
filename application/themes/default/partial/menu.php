<?php
use Aqua\Core\App;
use Aqua\Ragnarok\Ragnarok;
use Aqua\Ragnarok\Server;
use Aqua\Ragnarok\Server\CharMap;
use Aqua\Http\Uri;
use Aqua\UI\Menu;
$menu = new Menu;
$menu->append('home', array(
		'title' => __('menu', 'home'),
		'url' => \Aqua\URL
	));
if(App::user()->loggedIn()) {
	$menu->append('account', array(
			'title' => __('menu', 'my-account'),
			'url' => ac_build_url(array( 'path' => array( 'account' ) ))
		));
} else {
	$menu->append('register', array(
			'title' => __('menu', 'register'),
			'url' => ac_build_url(array( 'path' => array( 'account' ), 'action' => 'register' ))
		));
}
$menu->append('donation', array(
		'title' => __('menu', 'donate'),
		'url' => ac_build_url(array( 'path' => array( 'donate' ) ))
	))->append('news', array(
		'title' => __('menu', 'news'),
		'url' => ac_build_url(array( 'path' => array( 'news' ) ))
	));
if(Server::$serverCount) {
	$servers = array();
	foreach(Server::$servers as $server) {
		if(!$server->charmapCount) continue;
		$servers[] = array(
			'title' => $server->name,
			'url' => $server->url(),
			'submenu' => array(
				array(
					'title' => __('menu', 'whos-online'),
					'url' => $server->url(array( 'action' => 'online' )),
				),
				array(
					'title' => __('menu', 'mob-db'),
					'url' => $server->url(array( 'path' => array( 'mob' ) )),
				),
				array(
					'title' => __('menu', 'item-db'),
					'url' => $server->url(array( 'path' => array( 'item' ) )),
				),
				array(
					'title' => __('menu', 'item-shop'),
					'url' => $server->url(array( 'path' => array( 'item' ), 'action' => 'shop' )),
				),
				array(
					'title' => __('menu', 'rankings'),
					'url' => $server->url(array( 'path' => array( 'ranking' ) )),
				),
			)
		);
	}
	reset(Server::$servers);
	if(!empty($servers)) {
		if(Server::$serverCount > 1) {
			$menu->append('server', array(
					'title' => __('menu', 'servers'),
					'url' => '#',
					'submenu' => $servers
				));
		} else {
			$menu->append('server', current($servers));
		}
	}
}
echo $menu->render();