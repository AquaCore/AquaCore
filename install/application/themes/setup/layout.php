<!DOCTYPE html>
<?php
use Aqua\Core\App;
use Aqua\UI\ScriptManager;
/**
 * @var $body_class string
 * @var $head       \Aqua\UI\Theme\Head
 * @var $footer     \Aqua\UI\Theme\Footer
 * @var $content    string
 */
$setup = App::registryGet('setup');
$head->enqueueScript(ScriptManager::script('jquery-ui'));
$direction = $setup->languageDirection();
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
	<?php echo $head->render() ?>
	<link type="text/css" href="<?php echo $this->url ?>/style.css" rel="stylesheet" />
	<script src="<?php echo $this->url ?>/main.js"></script>
</head>
<body class="<?php echo $body_class?>">
<div id="main">
	<div id="body">
		<div id="menu">
			<div class="logo">
				<img src="<?php echo $this->url ?>/images/logo.png">
			</div>
			<?php echo $menu->render() ?>
		</div>
			<div id="content">
				<div class="language">
					<form method="POST" action="<?php echo ac_build_url(array( 'action' => 'setlang' )) ?>">
						<select name="language" onchange="this.form.submit()">
							<?php
							$default = $setup->languageCode;
							foreach($setup->languagesAvailable as $code => $language) {
								echo "<option value=\"{$code}\" ",
								(strcasecmp($default, $code) === 0 ? 'selected="selected"' : ''),
								">{$language[0]}</option>";
							}
							?>
						</select>
					</form>
				</div>
				<form method="POST">
				<?php echo $content ?>
				<div class="buttons">
					<?php
					$current_url = App::request()->uri->url();
					if($setup->currentStep === (count($setup->steps) - 1)) {
						echo '<button type="button" class="prev" disabled="disabled">', __setup('prev'), '</button>';
					} else {
						if($setup->currentStep > 0 && isset($setup->steps[$setup->currentStep - 1])) {
							echo '<a href="',
							ac_build_url(array( 'action' => $setup->steps[$setup->currentStep - 1] )),
							'"><button type="button" class="prev">', __setup('prev'), '</button></a>';
						}
						if($setup->currentStep < count($setup->steps)) {
							echo '<button type="submit" class="next">', __setup('next'), '</button>';
						}
					}
					?>
				</div>
				</form>
			</div>
		<div style="clear: both; height: 0"></div>
	</div>
</div>
<?php echo $footer->render()?>
</body>
</html>
