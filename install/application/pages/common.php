<?php
namespace Page;

use Aqua\Site\Page;

class Common
extends Page
{
	public function index_action() {}

	public function error_action($code, $title, $message)
	{
		if(!$title) {
			$this->title = $code;
		} else {
			$this->title = $title;
		}
		if(!$message) {
			switch($code) {
				case 404:
				case 403:
				case 401: echo __setup("error-$code"); break;
			}
		} else {
			echo $message;
		}
		$this->response->status($code);
	}
}
 