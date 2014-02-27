<?php
use Aqua\Core\App;
/**
 * @var $page \Page\Main\Donate
 */
$page->response->status(302)->redirect(\Aqua\URL);
if(App::user()->session->get('ac_donation', false) === true) {
	App::user()->addFlash('success', null, __('donation', 'thank-you'));
}
