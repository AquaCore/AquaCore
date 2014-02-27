<?php
/**
 * @var $depth   int
 * @var $options array
 * @var $class   string
 */
assert('isset($depth) && isset($options) && isset($class)');
$optClass = '';
$class.= ' menu-depth-' . $depth;
if($depth) {
	$class.= ' menu-option-submenu';
} else {
	$optClass = 'menu-option';
}
?>
<ul class="<?php echo $class?>">
	<?php
	$parity = array('even', 'odd');
	foreach($options as $option) {
		if($option['submenu']) {
			$option['class'].= ' has-submenu';
		}
		?>
		<li class="<?php echo $optClass . ' ' . $option['class'] . ' ' . $parity[0]?>">
			<a <?php if($option['url']) { echo "href=\"{$option['url']}\""; } ?> class="menu-option-link">
				<div class="menu-option-icon"></div>
				<div class="menu-option-title"><?php echo $option['title']?></div>
				<?php if($option['warnings']) { ?>
					<div class="menu-option-warning"><?php echo $option['warnings']?></div>
				<?php } ?>
			</a>
			<?php echo $option['submenu']?>
		</li>
		<?php
		$parity = array_reverse($parity);
	}
	?>
</ul>
