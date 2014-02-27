<?php
/**
 * @var $form \Aqua\UI\Form
 * @var $page \Page\Admin\Ragnarok
 */
$page->theme->set('wrapper', $form->buildTag());
$page->theme->footer->enqueueScript('theme.login-settings')
	->type('text/javascript')
	->src($page->theme->url . '/scripts/login-settings.js');
?>
<table class="ac-settings-form ac-login-settings">
	<?php if($form->message) : ?>
		<tr class="ac-form-error">
			<td colspan="3">
				<div><?php echo $form->message ?></div>
			</td>
		</tr>
	<?php endif ?>
	<?php echo $form->render(null, false, array( 'name', 'key', 'emulator', 'host', 'port' )) ?>
	<tr>
		<td colspan="3">
			<div class="ac-settings-section">
				<div class="title"><?php echo __('ragnarok-server', 'section-login-connection') ?></div>
				<div class="separator"></div>
			</div>
		</td>
	</tr>
	<?php echo $form->render(null, false, array( 'login-host', 'login-port', 'login-database', 'login-username', 'login-password', 'login-charset', 'login-timezone' )) ?>
	<tr>
		<td colspan="3">
			<div class="ac-settings-section">
				<div class="title"><?php echo __('ragnarok-server', 'section-log-connection') ?></div>
				<div class="separator"></div>
			</div>
		</td>
	</tr>
	<?php echo $form->render(null, false, array( 'log-host', 'log-port', 'log-database', 'log-username', 'log-password', 'log-charset', 'log-timezone' )) ?>
	<tr>
		<td colspan="3">
			<div class="ac-settings-section">
				<div class="title"><?php echo __('ragnarok-server', 'section-settings') ?></div>
				<div class="separator"></div>
			</div>
		</td>
	</tr>
	<?php echo $form->render(null, false, array( 'md5', 'pincode', 'link', 'default-group-id', 'slots', 'timeout', 'cache', 'max-accounts' )) ?>
	<tr>
		<td colspan="3">
			<div class="ac-settings-section">
				<div class="title"><?php echo __('ragnarok-server', 'section-username') ?></div>
				<div class="separator"></div>
			</div>
		</td>
	</tr>
	<?php echo $form->render(null, false, array( 'username-min', 'username-max', 'username-regex' )) ?>
	<tr>
		<td colspan="3">
			<div class="ac-settings-section">
				<div class="title"><?php echo __('ragnarok-server', 'section-password') ?></div>
				<div class="separator"></div>
			</div>
		</td>
	</tr>
	<?php echo $form->render(null, false, array( 'password-min', 'password-max', 'password-regex' )) ?>
	<tr>
		<td colspan="3">
			<div class="ac-settings-section">
				<div class="title"><?php echo __('ragnarok-server', 'section-group-id') ?></div>
				<div class="separator"></div>
			</div>
		</td>
	</tr>
	<?php
	$ids = $page->request->getArray('group-id', null);
	$names = $page->request->getArray('group-name', null);
	$descriptions = $page->request->getArray('group-desc', null);
	$count = count($ids);
	if($ids && $names && $descriptions && $count === count($names) && $count === count($descriptions)) {
		for($i = 0; $i > $count; ++$i) {
			if(empty($ids[$i]) && empty($names[$i]) && empty($descriptions[$i])) { continue; } ?>
	<tr class="ac-group-settings">
		<td></td>
		<td colspan="2">
			<input type="number" min="0" max="99" placeholder="<?php echo __('ragnarok-server', 'group-id-label') ?>" value="<?php echo $ids[$i] ?>">
			<input type="text" placeholder="<?php echo __('ragnarok-server', 'group-name-label') ?>" value="<?php echo htmlspecialchars($names[$i]) ?>">
			<div class="ac-server-group-options ac-script">
				<button type="button" class="ac-delete-button" tabindex="-1"></button>
			</div>
		</td>
	</tr>
	<?php } } ?>
	<tr class="ac-group-settings">
		<td></td>
		<td colspan="2">
			<input class="ac-server-group-id"  name="group-id[]" type="number" min="0" max="99" placeholder="<?php echo __('ragnarok-server', 'group-id-label') ?>">
			<input class="ac-server-group-name" name="group-name[]" type="text" placeholder="<?php echo __('ragnarok-server', 'group-name-label') ?>">
			<div class="ac-server-group-options ac-script">
				<button type="button" class="ac-delete-button" tabindex="-1"></button>
			</div>
		</td>
	</tr>
	<tr class="ac-noscript">
		<td></td>
		<td colspan="2">
			<input class="ac-server-group-id"  name="group-id[]" type="number" min="0" max="99" placeholder="<?php echo __('ragnarok-server', 'group-id-label') ?>">
			<input class="ac-server-group-name" name="group-name[]" type="text" placeholder="<?php echo __('ragnarok-server', 'group-name-label') ?>">
			<div class="ac-server-group-options ac-script">
				<button type="button" class="ac-delete-button" tabindex="-1"></button>
			</div>
		</td>
	</tr>
	<tr class="ac-noscript">
		<td></td>
		<td colspan="2">
			<input class="ac-server-group-id"  name="group-id[]" type="number" min="0" max="99" placeholder="<?php echo __('ragnarok-server', 'group-id-label') ?>">
			<input class="ac-server-group-name" name="group-name[]" type="text" placeholder="<?php echo __('ragnarok-server', 'group-name-label') ?>">
			<div class="ac-server-group-options ac-script">
				<button type="button" class="ac-delete-button" tabindex="-1"></button>
			</div>
		</td>
	</tr>
	<tr class="ac-noscript">
		<td></td>
		<td colspan="2">
			<input class="ac-server-group-id"  name="group-id[]" type="number" min="0" max="99" placeholder="<?php echo __('ragnarok-server', 'group-id-label') ?>">
			<input class="ac-server-group-name" name="group-name[]" type="text" placeholder="<?php echo __('ragnarok-server', 'group-name-label') ?>">
			<div class="ac-server-group-options ac-script">
				<button type="button" class="ac-delete-button" tabindex="-1"></button>
			</div>
		</td>
	</tr>
	<tr class="ac-noscript">
		<td></td>
		<td colspan="2">
			<input class="ac-server-group-id"  name="group-id[]" type="number" min="0" max="99" placeholder="<?php echo __('ragnarok-server', 'group-id-label') ?>">
			<input class="ac-server-group-name" name="group-name[]" type="text" placeholder="<?php echo __('ragnarok-server', 'group-name-label') ?>">
			<div class="ac-server-group-options ac-script">
				<button type="button" class="ac-delete-button" tabindex="-1"></button>
			</div>
		</td>
	</tr>
	<tr class="ac-script">
		<td colspan="3">
			<button type="button" class="disabled ac-add-group"><?php echo __('ragnarok-server', 'add-group') ?></button>
		</td>
	</tr>
	<tr>
		<td colspan="3">
			<?php echo $form->field('submit')->css('float', 'right')->render() ?>
		</td>
	</tr>
</table>
