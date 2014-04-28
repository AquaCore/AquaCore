<!DOCTYPE html>
<?php
use Aqua\Core\App;
use Aqua\UI\ScriptManager;
use Aqua\UI\Tag;
/**
 * @var $body_class string
 * @var $head       \Aqua\UI\Theme\Head
 * @var $footer     \Aqua\UI\Theme\Footer
 * @var $admin_menu \Aqua\UI\Menu
 * @var $content    string
 */
?>
<!--[if lt IE 7 ]>
<html class="ie6 ie">
<![endif]-->
<!--[if IE 7 ]>
<html class="ie ie7">
<![endif]-->
<!--[if IE 8 ]>
<html class="ie ie8">
<![endif]-->
<!--[if (gte IE 9)|!(IE)]><!-->
<html>
<!--<![endif]-->
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<?php
	$head->enqueueScript(ScriptManager::script('jquery-ui'));
	echo $head->render();
	?>
	<link type="text/css" href="<?php echo $this->url?>/stylesheets/main.css" rel="stylesheet" />
</head>
<body class="<?php echo $body_class?> noscript">
<div id="main">
	<div id="main-menu">
		<a class="aquacore-logo" href="<?php echo Aqua\URL?>">
			<img src="<?php echo App::logo();?>">
		</a>
		<div id="menu">
			<?php echo $admin_menu->render('admin')?>
		</div>
		<div class="page-info">
			<?php
			$time = microtime(true) - App::registryGet('ac_time');
			echo __('application', 'page-time', round($time, 6)), '<br>',
			__('application', 'page-memory', round(memory_get_peak_usage(true) / 1048576, 6)), '<br>'
			?>
			<p/>
			<div style="text-align: center">
				Powered by AquaCore<br>
				Copyright &copy; 2014 Wynn
			</div>
		</div>
	</div>
	<?php
	$content = "<div id=\"body\">{$content}</div>";
	if(isset($wrapper) && $wrapper instanceof Tag) {
		echo $wrapper->append($content)->render();
	} else {
		echo $content;
	}
	?>
</div>
<?php echo $footer->render()?>
<script type="text/javascript" src="<?php echo $this->url?>/scripts/main.js"></script>
</body>
</html>
