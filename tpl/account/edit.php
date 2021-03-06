<?php
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
		<tr class="ac-form-warning">
			<td>
			<td colspan="4">
				<?php if(($warning = $form->field('avatar_type')->getWarning()) ||
					($warning = $form->field('image')->getWarning()) ||
					($warning = $form->field('gravatar')->getWarning())) {
					echo $warning;
				} ?>
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
			<td colspan="3" style="vertical-align: middle; padding: 0">
				<table style="width: 100%; border-collapse: collapse">
					<tr>
						<td colspan="2" style="width: 50%">
							<?php echo $form->field('avatar_type')->label('image')->render(),
									   $form->field('avatar_type')->option('image')->bool('checked')->render() ?>
						</td>
						<td colspan="2" style="width: 50%">
							<?php echo $form->field('avatar_type')->label('gravatar')->render(),
									   $form->field('avatar_type')->option('gravatar')->render() ?>
						</td>
					</tr>
					<tr>
						<td><b><?php echo $form->field('image')->getLabel() ?></b></td>
						<td><?php echo $form->field('image')->render() ?></td>
						<td><b><?php echo $form->field('gravatar')->getLabel() ?></b></td>
						<td><?php echo $form->field('gravatar')->render() ?></td>
					</tr>
					<tr>
						<td style="border-bottom: none"><b><?php echo $form->field('url')->getLabel() ?></b></td>
						<td style="border-bottom: none"><?php echo $form->field('url')->render() ?></td>
						<td style="border-bottom: none; font-size: 0.85em; text-align: center" colspan="2"><?php echo $form->field('gravatar')->getDescription() ?></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr class="ac-form-warning">
			<td colspan="4"><?php echo $form->field('password')->getWarning() ?: $form->field('repeat_password')->getWarning() ?></td>
		</tr>
		<tr>
			<td style="width: 15%"><b><?php echo $form->field('password')->getLabel() ?></b></td>
			<td style="width: 35%"><?php echo $form->field('password')->render() ?></td>
			<td style="width: 15%"><b><?php echo $form->field('repeat_password')->getLabel() ?></b></td>
			<td style="width: 35%"><?php echo $form->field('repeat_password')->render() ?></td>
		</tr>
		<tr class="ac-form-warning">
			<td colspan="2"><?php echo $form->field('display_name')->getWarning() ?></td>
			<td colspan="2"></td>
		</tr>
		<tr>
			<td><b><?php echo $form->field('display_name')->getLabel() ?></b></td>
			<td><?php echo $form->field('display_name')->render() ?></td>
			<td colspan="2"></td>
		</tr>
		<tr class="ac-form-warning">
			<td colspan="2"><?php echo $form->field('email')->getWarning() ?></td>
			<td colspan="2"></td>
		</tr>
		<tr>
			<td><b><?php echo $form->field('email')->getLabel() ?></b></td>
			<td><?php echo $form->field('email')->render() ?></td>
			<td colspan="2"></td>
		</tr>
		<tr class="ac-form-warning">
			<td colspan="2"><?php echo $form->field('birthday')->getWarning() ?></td>
			<td colspan="2"></td>
		</tr>
		<tr>
			<td><b><?php echo $form->field('birthday')->getLabel() ?></b></td>
			<td><?php echo $form->field('birthday')->render() ?></td>
			<td colspan="2"></td>
		</tr>
		<?php if($form->field('report_threshold')) : ?>
			<tr class="ac-form-warning">
				<td colspan="2"><?php echo $form->field('report_threshold')->getWarning() ?></td>
				<td colspan="2"></td>
			</tr>
			<tr>
				<td><b><?php echo $form->field('report_threshold')->getLabel() ?></b></td>
				<td><?php echo $form->field('report_threshold')->render() ?></td>
				<td colspan="2"><small><?php echo $form->field('report_threshold')->getDescription() ?></small></td>
			</tr>
		<?php endif; ?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="4">
				<span style="float: right">
					<?php
					echo $form
							 ->field('account_preferences')
							 ->render(),
					     $form
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
