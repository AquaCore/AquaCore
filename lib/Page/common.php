<?php
namespace Page;

use Aqua\Site\Page;

class Common
extends Page
{
	public function index_action() {}

	public function error_action($code, $title, $message)
	{
		$this->theme->reset();
		if(!$title) {
			$this->title = $code;
		} else {
			$this->title = $title;
		}
		if(!$message) {
			switch($code) {
				case 404:
				case 403:
				case 401: echo __('http-error', $code); break;
				default: echo __('application', 'error', $code), '<br>';
			}
		} else {
			echo $message;
		}
		$this->response->status($code);
	}
}
