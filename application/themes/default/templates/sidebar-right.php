<div id="menu">
	<div class="menu-bar"></div>
	<div class="menu-content">
		<?php include __DIR__ . '/../partial/menu.php'; ?>
	</div>
</div>
<?php if($title) : ?>
	<div class="page-title">
		<?php if(isset($return)) : ?>
			<a class="ac-page-return" href="<?php echo $return ?>">
				<div class="ac-return-icon"></div>
			</a>
		<?php endif; echo $title?>
	</div>
<?php endif; ?>
<?php
if(isset($menu) && $menu instanceof \Aqua\UI\Menu) {
	$menu->addClass('ac-navigation-menu');
	echo $menu->render('default');
}
?>
<div class="content">
	<?php echo $content?>
</div>