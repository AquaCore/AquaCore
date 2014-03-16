<div class="content">
	<?php if($title) : ?>
		<div class="page-title">
			<?php if(isset($return)) : ?>
				<a class="ac-page-return" href="<?php echo $return ?>">
					<div class="ac-return-icon"></div>
				</a>
			<?php endif; echo $title?>
		</div>
	<?php endif; ?>
	<?php if(isset($nav) && $nav instanceof \Aqua\UI\Menu) : ?>
		<div class="navigation"><?php echo $nav->render() ?><div class="clear-fix"></div></div>
	<?php endif; ?>
	<?php echo $content?>
</div>