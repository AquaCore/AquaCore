<?php
use Aqua\User\Account;
use Aqua\Core\App;
/**
 * @var $account \Aqua\User\Account
 * @var $form    \Aqua\UI\Form
 * @var $token   string
 * @var $page    \Page\Main\Account
 */
$page->theme->footer->enqueueScript('theme.account-preferences')
	->type('text/javascript')
	->src($page->theme->url . '/scripts/account-preferences.js');
?>
<form method="POST" enctype="multipart/form-data" id="" autocomplete="off">
<?php if(($error = $form->message) || ($error = $form->field('current_password')->getWarning())) : ?>
<div class="ac-form-error"><?php echo $error ?></div>
<?php endif; ?>
<table class="ac-table">
	<thead>
		<tr>
			<td colspan="4"><?php echo __('profile', 'account-info', htmlspecialchars($account->username))?></td>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td class="ac-form-warning">
			<td colspan="4">
				<?php if(($warning = $form->field('avatar_type')->getWarning()) ||
					($warning = $form->field('image')->getWarning()) ||
					($warning = $form->field('gravatar')->getWarning())) {
					echo $warning;
				} ?>
			<td colspan="4">
			</td>
		</tr>
		<tr>
			<td style="text-align: center; vertical-align: middle">
				<div class="ac-user-avatar-edit">
					<?php if($account->avatar) : ?>
					<a href="<?php ac_build_url(array(
							'path' => array( 'account' ),
							'action' => 'edit',
							'query' => array(
								'token' => $token,
								'x-action' => 'delete-avatar'
							)
						)) ?>">
						<div class="ac-delete-button"></div>
						</a>
					<?php endif; ?>
					<img src="<?php echo $account->avatar() ?>">
				</div>
			</td>
			<td colspan="3" style="vertical-align: middle">
				<table style="width: 100%">
					<tr>
						<td colspan="2" style="width: 50%">
							<?php echo $form->field('avatar_type')->option('image')->bool('checked')->render() ?>
						</td>
						<td colspan="2" style="width: 50%">
							<?php echo $form->field('avatar_type')->option('gravatar')->render() ?>
						</td>
					</tr>
					<tr>
						<td><?php echo $form->field('image')->getLabel() ?></td>
						<td><?php echo $form->field('image')->render() ?></td>
						<td><?php echo $form->field('gravatar')->getLabel() ?></td>
						<td><?php echo $form->field('gravatar')->render() ?></td>
					</tr>
					<tr>
						<td style="border-bottom: none"><?php echo $form->field('url')->getLabel() ?></td>
						<td style="border-bottom: none"><?php echo $form->field('url')->render() ?></td>
						<td style="border-bottom: none; font-size: 0.85em; text-align: center" colspan="2"><?php echo $form->field('gravatar')->getDescription() ?></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr class="ac-form-warning">
			<td colspan="2"><?php echo $form->field('password')->getWarning() ?></td>
			<td colspan="2"><?php echo $form->field('repeat_password')->getWarning() ?></td>
		</tr>
		<tr>
			<td style="width: 15%"><?php echo $form->field('password')->getLabel() ?>:</td>
			<td style="width: 35%"><?php echo $form->field('password')->render() ?></td>
			<td style="width: 15%"><?php echo $form->field('repeat_password')->getLabel() ?>:</td>
			<td style="width: 35%"><?php echo $form->field('repeat_password')->render() ?></td>
		</tr>
		<tr class="ac-form-warning">
			<td colspan="2"><?php echo $form->field('display_name')->getWarning() ?></td>
			<td colspan="2"></td>
		</tr>
		<tr>
			<td><?php echo $form->field('display_name')->getLabel() ?>:</td>
			<td><?php echo $form->field('display_name')->render() ?></td>
			<td colspan="2"></td>
		</tr>
		<tr class="ac-form-warning">
			<td colspan="2"><?php echo $form->field('email')->getWarning() ?></td>
			<td colspan="2"></td>
		</tr>
		<tr>
			<td><?php echo $form->field('email')->getLabel() ?>:</td>
			<td><?php echo $form->field('email')->render() ?></td>
			<td colspan="2"></td>
		</tr>
		<tr>
			<td><?php echo $form->field('birthday')->getLabel() ?>:</td>
			<td><?php echo $form->field('birthday')->render() ?></td>
			<td colspan="2"></td>
		</tr>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="4">
				<span style="float: right">
					<?php
					echo $form
					     ->field('current_password')
					     ->placeholder($form->field('current_password')->getLabel())
					     ->render()
					?>
					<input type="submit" name="x-edit" value="<?php echo __('application', 'submit')?>">
				</span>
			</td>
		</tr>
	</tfoot>
</table>
</form>
