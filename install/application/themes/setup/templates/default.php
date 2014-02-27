<?php if($title) : ?>
	<div class="page-title">
		<?php echo $title?>
	</div>
<?php endif; ?>
<div class="content" dir="<?php echo \Aqua\Core\App::registryGet('setup')->languageDirection() ?>">
	<?php echo $content?>
</div>