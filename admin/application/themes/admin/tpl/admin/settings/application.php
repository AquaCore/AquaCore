<?php
/**
 * @var $form \Aqua\UI\Form
 * @var $page \Page\Admin\Settings
 */
include __DIR__ . '/settings-sidebar.php';
$option = &$menu->get('application');
$option['class'] = 'active';
?>
<table class="ac-settings-form">
	<?php echo $form->render(null, false, array( 'title', 'domain', 'base_dir', 'rewrite_url', 'ob', 'language', 'ssl' )) ?>
	<tr>
		<td colspan="3">
			<div class="ac-settings-section">
				<div class="title"><?php echo __('settings', 'section-datetime') ?></div>
				<div class="separator"></div>
			</div>
		</td>
	</tr>
	<?php echo $form->render(null, false, array( 'timezone', 'date_format', 'time_format', 'datetime_format' )) ?>
</table>
