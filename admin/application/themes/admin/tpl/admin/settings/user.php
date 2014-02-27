<?php
/**
 * @var $form \Aqua\UI\Form
 * @var $page \Page\Admin\Settings
 */
include __DIR__ . '/settings-sidebar.php';
$option = &$menu->get('user');
$option['class'] = 'active';
?>
<table class="ac-settings-form">
	<?php echo $form->render(null, false, array( 'email_login' )) ?>
	<tr>
		<td colspan="3">
			<div class="ac-settings-section">
				<div class="title"><?php echo __('settings', 'section-registration') ?></div>
				<div class="separator"></div>
			</div>
		</td>
	</tr>
	<?php echo $form->render(null, false, array( 'require_tos', 'require_captcha', 'email_validation', 'email_validation_expire', 'validation_key_len' )) ?>
	<tr>
		<td colspan="3">
			<div class="ac-settings-section">
				<div class="title"><?php echo __('settings', 'section-user-session') ?></div>
				<div class="separator"></div>
			</div>
		</td>
	</tr>
	<?php echo $form->render(null, false, array( 'session_name', 'session_http_only', 'session_secure', 'session_match_ip', 'session_match_user_agent', 'session_expire', 'session_regenerate_id', 'session_collision', 'session_gc' )) ?>
	<tr>
		<td colspan="3">
			<div class="ac-settings-section">
				<div class="title"><?php echo __('settings', 'section-persistent-login') ?></div>
				<div class="separator"></div>
			</div>
		</td>
	</tr>
	<?php echo $form->render(null, false, array( 'pl_enabled', 'pl_name', 'pl_http_only', 'pl_secure', 'pl_timeout', 'pl_expire', 'pl_len' )) ?>
</table>
