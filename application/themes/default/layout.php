<!DOCTYPE html>
<?php
use Aqua\Core\App;
use Aqua\UI\StyleManager;
use Aqua\UI\ScriptManager;
/**
 * @var $__url      string
 * @var $__file     string
 * @var $body_class string
 * @var $head       \Aqua\UI\Theme\Head
 * @var $footer     \Aqua\UI\Theme\Footer
 * @var $content    string
 * @var $js_lang    string
 */
$head->enqueueScript(ScriptManager::script('jquery-ui'));
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
	$head->enqueueLink(StyleManager::style('aquacore-ui'));
	echo $head->render()
	?>
	<link type="text/css" href="<?php echo $__url?>/stylesheets/main.css" rel="stylesheet" />
	<script src="<?php echo $__url?>/scripts/main.js"></script>
</head>
<body class="<?php echo $body_class?>">
<div id="main">
	<!---- Header -->
	<div id="header">
		<div class="header-content">
			<a href="<?php echo Aqua\URL?>" class="logo">
				<img src="<?php echo App::logo()?>">
			</a>
			<?php include __DIR__ . '/partial/header.php'; ?>
			<?php include __DIR__ . '/partial/cart.php'; ?>
		</div>
	</div>
	<!---- Menu -->
	<div id="menu">
		<?php include __DIR__ . '/partial/menu.php'; ?>
	</div>
	<!---- Body -->
	<div id="body" tabindex="-1">
		<?php include __DIR__ . '/partial/navbar.php'; ?>
		<div class="body-content">
			<?php echo $content?>
		</div>
	</div>
	<!---- Footer -->
	<div id="footer">
		<div class="footer-content">
			<div class="page-info">
				<?php
				$time = microtime(true) - App::registryGet('ac_time');
				echo __('application', 'page-time', round($time, 6)), '<br>',
					 __('application', 'page-memory', round(memory_get_peak_usage(true) / 1048576, 6)), '<br>'
				?>
			</div>
			<div class="credits">
				Powered by AquaCore<br>
				Copyright &copy; 2014 Wynn
			</div>
		</div>
	</div>
</div>
<?php echo $footer->render()?>
</body>
</html>
