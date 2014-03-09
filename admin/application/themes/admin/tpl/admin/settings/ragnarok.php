<?php
/**
 * @var $form \Aqua\UI\Form
 * @var $page \Page\Admin\Settings
 */
include __DIR__ . '/settings-sidebar.php';
$option = &$menu->get('ragnarok');
$option['class'] = 'active';
?>
<table class="ac-settings-form">
	<?php echo $form->render(null, false, array( 'acc', 'char', 'script', 'pincode_min', 'pincode_max', 'shop_max' )) ?>
	<tr>
		<td colspan="3">
			<div class="ac-settings-section">
				<div class="title"><?php echo __('settings', 'section-emblem') ?></div>
				<div class="separator"></div>
			</div>
		</td>
	</tr>
	<?php echo $form->render(null, false, array( 'emblem_cache', 'emblem_ttl', 'emblem_compress' )) ?>
	<tr>
		<td colspan="3">
			<div class="ac-settings-section">
				<div class="title"><?php echo __('settings', 'section-sprite') ?></div>
				<div class="separator"></div>
			</div>
		</td>
	</tr>
	<?php echo $form->render(null, false, array( 'sprite_cache', 'sprite_ttl', 'sprite_compress', 'head_pos', 'body_pos', 'body_act' )) ?>
</table>
