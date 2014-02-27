<?php foreach($requirements as $section => $list) : ?>
<h3><?php echo __setup($section) ?></h3>
<ul class="requirements">
	<?php foreach($list as $name => $passed) : ?>
		<li class="<?php echo ($passed ? 'pass' : 'fail') ?>">
			<?php echo __setup($name) ?>
		</li>
	<?php endforeach ?>
</ul>
<?php endforeach ?>
