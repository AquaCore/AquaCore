<div class="content">
	<?php if($title) : ?>
		<div class="page-title">
			<?php echo $title?>
		</div>
	<?php endif; ?>
	<?php if(isset($nav) && $nav instanceof \Aqua\UI\Menu) : ?>
		<div class="navigation"><?php echo $nav->render() ?><div class="clear-fix"></div></div>
	<?php endif; ?>
	<?php echo $content; ?>
</div>
<div id="sidebar">
<?php
if(isset($sidebar) && $sidebar instanceof \Aqua\UI\Sidebar) {
	echo $sidebar->render();
}
?>
</div>
