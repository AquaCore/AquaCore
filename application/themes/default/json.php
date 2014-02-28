<?php
use Aqua\Core\App;

/**
 * @var $__url      string
 * @var $__file     string
 * @var $body_class string
 * @var $head       \Aqua\UI\Theme\Head
 * @var $footer     \Aqua\UI\Theme\Footer
 * @var $content    string
 * @var $js_lang    string
 */

App::response()->setHeader('Content-Type', 'application/json');
echo json_encode(array(
		'title'       => $head->title(),
		'content'     => $content,
		'scripts'     => $head->renderScripts() . $footer->renderScripts(),
		'links'       => $head->renderLinks() . $footer->renderLinks(),
		'stylesheets' => $head->renderStylesheets() . $footer->renderStylesheets(),
        'cart'        => include __DIR__ . '/partial/cart.php'
	));
