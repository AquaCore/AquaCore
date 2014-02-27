<?php
use Aqua\UI\Sidebar;
use Aqua\UI\Menu;
/**
 * @var $form \Aqua\UI\Form
 * @var $page \Page\Admin\Settings
 */
$sidebar = new Sidebar;
$menu = new Menu;
$menu->append('application', array(
		'title' => __('settings', 'application'),
		'url'   => ac_build_url(array( 'path' => array( 'settings' ) ))
	))->append('user', array(
		'title' => __('settings', 'user'),
		'url'   => ac_build_url(array( 'path' => array( 'settings' ), 'action' => 'user' ))
	))->append('account', array(
		'title' => __('settings', 'account'),
		'url'   => ac_build_url(array( 'path' => array( 'settings' ), 'action' => 'account' ))
	))->append('content', array(
		'title' => __('settings', 'content'),
		'url'   => ac_build_url(array( 'path' => array( 'settings' ), 'action' => 'cms' ))
	))->append('email', array(
		'title' => __('settings', 'email'),
		'url'   => ac_build_url(array( 'path' => array( 'settings' ), 'action' => 'email' ))
	))->append('donation', array(
		'title' => __('settings', 'donation'),
		'url'   => ac_build_url(array( 'path' => array( 'settings' ), 'action' => 'donation' ))
	))->append('captcha', array(
		'title' => __('settings', 'captcha'),
		'url'   => ac_build_url(array( 'path' => array( 'settings' ), 'action' => 'captcha' ))
	))->append('ragnarok', array(
		'title' => __('settings', 'ragnarok'),
		'url'   => ac_build_url(array( 'path' => array( 'settings' ), 'action' => 'ragnarok' ))
	))
;
$sidebar->append('menu', array( 'class' => 'ac-settings-menu ac-sidebar-navigation', array(
		'title' => __('settings', 'settings-categories'),
		'content' => $menu
	)))->append('submit', array('class' => 'ac-sidebar-action', array(
		'content' => '<input class="ac-sidebar-submit" type="submit" name="x-settings" value="' . __('application', 'submit') . '" ac-default-submit>'
	)));
$page->theme->template = 'sidebar-right';
$page->theme
	->set('sidebar', $sidebar)
	->set('wrapper', $form->buildTag());
