<?php
/**
 * @var $form \Aqua\UI\Form
 * @var $page \Page\Admin\Ragnarok\Server
 */
$page->theme->set('wrapper', $form->buildTag());
?>
<table class="ac-settings-form ac-login-settings">
	<?php if($form->message) : ?>
		<tr class="ac-form-error">
			<td colspan="3">
				<div><?php echo $form->message ?></div>
			</td>
		</tr>
	<?php endif ?>
	<?php echo $form->render(null, false, array( 'name', 'key', 'timezone', 'char-host', 'char-port', 'map-host', 'map-port' )) ?>
	<tr>
		<td colspan="3">
			<div class="ac-settings-section">
				<div class="title"><?php echo __('ragnarok-charmap-settings', 'section-sql-connection') ?></div>
				<div class="separator"></div>
			</div>
		</td>
	</tr>
	<?php echo $form->render(null, false, array( 'sql-host', 'sql-port', 'sql-database', 'sql-username', 'sql-password', 'sql-charset', 'sql-timezone' )) ?>
	<tr>
		<td colspan="3">
			<div class="ac-settings-section">
				<div class="title"><?php echo __('ragnarok-charmap-settings', 'section-log-connection') ?></div>
				<div class="separator"></div>
			</div>
		</td>
	</tr>
	<?php echo $form->render(null, false, array( 'log-host', 'log-port', 'log-database', 'log-username', 'log-password', 'log-charset', 'log-timezone' )) ?>
	<tr>
		<td colspan="3">
			<div class="ac-settings-section">
				<div class="title"><?php echo __('ragnarok-charmap-settings', 'section-settings') ?></div>
				<div class="separator"></div>
			</div>
		</td>
	</tr>
	<?php echo $form->render(null, false, array( 'renewal', 'fame', 'online-stats' )) ?>
	<tr>
		<td colspan="3">
			<div class="ac-settings-section">
				<div class="title"><?php echo __('ragnarok-charmap-settings', 'section-map-reset') ?></div>
				<div class="separator"></div>
			</div>
		</td>
	</tr>
	<?php echo $form->render(null, false, array( 'default-map', 'default-x', 'default-y', 'map-restrictions' )) ?>
	<tr>
		<td colspan="3">
			<?php echo $form->field('submit')->css('float', 'right')->render() ?>
		</td>
	</tr>
</table>
