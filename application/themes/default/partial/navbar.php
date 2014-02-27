<?php
use Aqua\Core\App;
use Aqua\Ragnarok\Ragnarok;
/**
 * @var $request \Aqua\Http\Request
 */

echo '<div class="nav-bar">';

if(isset($navbar_alt) && !empty($navbar_alt)) {
	echo '<ul class="ac-nav-bar-alt">';
	foreach($navbar_alt as $path) {
		echo '<li><a href="' . $path['url'] . '">' . $path['title'] .'</a></li>';
	}
	echo '</ul>';
}
if(isset($navbar) && !empty($navbar)) {
	echo '<ul class="ac-nav-bar">';
	foreach($navbar as $path) {
		echo '<li><a href="' . $path['url'] . '">' . $path['title'] .'</a></li>';
	}
	echo '</ul>';
}
echo '</div>';
