<?php
/**
 * @var $server \Aqua\Ragnarok\Server
 * @var $form   \Aqua\UI\Form
 * @var $page   \Page\Admin\Ragnarok
 */
$page->theme->set('wrapper', $form->buildTag());
$page->theme->addWordGroup('ragnarok-server', 'confirm-delete');
$page->theme->footer->enqueueScript('theme.login-settings')
	->type('text/javascript')
	->src($page->theme->url . '/scripts/login-settings.js');
$server->login->groups !== null or $server->login->fetchGroups();
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
	<?php foreach($server->login->groups as $id => $group) { ?>
			<tr class="ac-group-settings">
				<td></td>
				<td colspan="2">
					<input class="ac-server-group-id" name="group-id[]" type="number" min="0" max="99" placeholder="<?php echo __('ragnarok-server', 'group-id-label') ?>" value="<?php echo $id ?>">
					<input class="ac-server-group-name" name="group-name[]" type="text" placeholder="<?php echo __('ragnarok-server', 'group-name-label') ?>" value="<?php echo htmlspecialchars($group) ?>">
					<div class="ac-server-group-options ac-script">
						<button type="button" class="ac-delete-button" tabindex="-1"></button>
					</div>
				</td>
			</tr>
	<?php } ?>
	<tr class="ac-group-settings">
		<td></td>
		<td colspan="2">
			<input class="ac-server-group-id" name="group-id[]" type="number" min="0" max="99" placeholder="<?php echo __('ragnarok-server', 'group-id-label') ?>">
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
			<?php echo $form->field('submit')->bool('ac-default-submit')->css('float', 'right')->render() ?>
			<?php echo $form->field('delete')->attr('class', 'ac-delete-server red')->css('float', 'right')->render() ?>
		</td>
	</tr>
</table>
