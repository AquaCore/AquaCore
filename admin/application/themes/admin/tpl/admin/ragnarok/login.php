<?php
use Aqua\UI\Dashboard;
use Aqua\Event\Event;
use Aqua\Ragnarok\Server\Login;
use Aqua\User\Role;
/**
 * @var $page   \Page\Admin\Ragnarok
 */
$base_acc_url = ac_build_url(array(
		'path' => array( 'user' ),
		'action' => 'view',
		'arguments' => array( '' )
	));
$base_ro_url = ac_build_url(array(
		'path' => array( 'ro', urlencode($page->server->key) ),
		'action' => 'view_account',
		'arguments' => array( '' )
	));
$dash = new Dashboard;
$servers = $accounts = array();
if($page->server->charmapCount === 0) {
	$servers = '<div style="text-align: center">' . __('application', 'no-search-results') . '</div>';
} else foreach($page->server->charmap as $key => $charmap) {
	$servers[] = array(
		'<a href="'. ac_build_url(array(
				'path' => array( 'ro', urlencode($page->server->key), 's', urlencode($charmap->key) )
			)) .  '">' .htmlspecialchars($charmap->name) . '</a>',
		((int)$charmap->getOption('base-exp', 100) / 100) . '<i>x</i>',
		((int)$charmap->getOption('job-exp', 100) / 100) . '<i>x</i>',
		$charmap->fetchCache('online')
	);
}
$cache = $page->server->login->fetchCache('last_registered');

if(count($cache) === 0) {
	$accounts = '<div style="text-align: center">' . __('application', 'no-search-results') . '</div>';
} else for($i = 0; $i < Login::CACHE_RECENT_ACCOUNTS; ++$i) {
	if(empty($cache[$i])) {
		$accounts[] = '';
	} else if(empty($cache[$i]['owner'])) {
		$accounts[] =
			'<a href="' . $base_acc_url . $cache[$i]['id'] . '">' .
			htmlspecialchars($cache[$i]['username']) .
			'</a>';
	} else {
		$accounts[] =
			'<a href="' . $base_ro_url . $cache[$i]['id'] . '">' .
			htmlspecialchars($cache[$i]['username']) .
			'</a><div class="ac-ro-user">' .
			__('ragnarok-login-dash', 'registered-under',
				'<a href="' . $base_acc_url . $cache[$i]['owner'] . '">' .
				Role::get($cache[$i]['role_id'])->display($cache[$i]['display_name'], 'ac-username') .
				'</a>'
			) .
			'</div>';
	}
}
$dash->get('server')->append('charmap', array(
		'title' => __('ragnarok-login-dash', 'charmap-servers'),
		'header' => array(
			__('ragnarok-login-dash', 'name'),
			__('ragnarok-login-dash', 'base-exp'),
			__('ragnarok-login-dash', 'job-exp'),
			__('ragnarok-login-dash', 'online'),
		),
		'footer' =>
		'<a href="' . ac_build_url(array( 'path' => array( 'ro', urlencode($page->server->key), 'server' ) )) .
		'"><button class="ac-button">' . __('ragnarok-server', 'add-charmap') . '</button></a>',
		'content' => $servers
	))->append('accounts', array(
		'title' => __('ragnarok-login-dash', 'latest-accounts'),
		'content' => $accounts
	));
$feedback = array( $page, $dash );
Event::fire('ragnarok.render-login-dashboard', $feedback);
echo '<div class="ac-dashboard">', $dash->render(), '</div>';
