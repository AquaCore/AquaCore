<?php
/**
 * @var $form \Aqua\UI\Form
 * @var $page \Page\Admin\Settings
 */
include __DIR__ . '/settings-sidebar.php';
$option = &$menu->get('account');
$option['class'] = 'active';
?>
<table class="ac-settings-form">
	<?php echo $form->render(null, false, array( 'default_avatar' )) ?>
	<tr>
		<td colspan="3">
			<div class="ac-settings-section">
				<div class="title"><?php echo __('settings', 'section-avatar') ?></div>
				<div class="separator"></div>
			</div>
		</td>
	</tr>
	<?php echo $form->render(null, false, array( 'avatar_size', 'avatar_width', 'avatar_height' )) ?>
	<tr>
		<td colspan="3">
			<div class="ac-settings-section">
				<div class="title"><?php echo __('settings', 'section-username') ?></div>
				<div class="separator"></div>
			</div>
		</td>
	</tr>
	<?php echo $form->render(null, false, array( 'username_max_len', 'username_min_len', 'username_pattern' )) ?>
	<tr>
		<td colspan="3">
			<div class="ac-settings-section">
				<div class="title"><?php echo __('settings', 'section-display-name') ?></div>
				<div class="separator"></div>
			</div>
		</td>
	</tr>
	<?php echo $form->render(null, false, array( 'display_max_len', 'display_min_len', 'display_pattern', 'display_limit', 'display_days' )) ?>
	<tr>
		<td colspan="3">
			<div class="ac-settings-section">
				<div class="title"><?php echo __('settings', 'section-password') ?></div>
				<div class="separator"></div>
			</div>
		</td>
	</tr>
	<?php echo $form->render(null, false, array( 'password_max_len', 'password_min_len', 'password_pattern', 'password_limit', 'password_days' )) ?>
	<tr>
		<td colspan="3">
			<div class="ac-settings-section">
				<div class="title"><?php echo __('settings', 'section-birthday') ?></div>
				<div class="separator"></div>
			</div>
		</td>
	</tr>
	<?php echo $form->render(null, false, array( 'birthday_min', 'birthday_limit', 'birthday_days' )) ?>
	<tr>
		<td colspan="3">
			<div class="ac-settings-section">
				<div class="title"><?php echo __('settings', 'section-email') ?></div>
				<div class="separator"></div>
			</div>
		</td>
	</tr>
	<?php echo $form->render(null, false, array( 'email_limit', 'email_days' )) ?>
</table>
