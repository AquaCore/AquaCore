<?php
use Aqua\Ragnarok\Account;
use Aqua\Ragnarok\Ragnarok;
/**
 * @var $account \Aqua\Ragnarok\Account
 * @var $form    \Aqua\UI\Form
 * @var $page    \Page\Main\Ragnarok\Account
 */
?>
<form method="POST" autocomplete="off">
	<?php if(($error = $form->message) ||
	         ($error = $form->field('confirm_password')->getWarning())) : ?>
		<div class="ac-form-error"><?php echo $error ?></div>
	<?php endif; ?>
	<table class="ac-table" style="table-layout: fixed">
		<colgroup>
			<col style="width: 25%">
			<col style="width: 25%">
			<col style="width: 25%">
			<col style="width: 25%">
		</colgroup>
		<thead>
		<tr>
			<td colspan="4"><?php echo __('ragnarok', 'account-info', htmlspecialchars($account->username))?></td>
		</tr>
		</thead>
		<tbody>
		<tr class="ac-form-warning">
			<td colspan="2"><?php echo $form->field('password')->getWarning() ?></td>
			<td colspan="2"><?php echo $form->field('password_r')->getWarning() ?></td>
		</tr>
		<tr>
			<td><b><?php echo $form->field('password')->getLabel()?></b></td>
			<td style="text-align: left"><?php echo $form->field('password')->render()?></td>
			<td><b><?php echo $form->field('password_r')->getLabel()?></b></td>
			<td style="text-align: left"><?php echo $form->field('password_r')->render()?></td>
		</tr>
	<?php if($account->server->login->getOption('use-pincode')) : ?>
		<tr class="ac-form-warning">
			<td colspan="2"><?php echo $form->field('pincode')->getWarning() ?></td>
			<td colspan="2"><?php echo $form->field('pincode_r')->getWarning() ?></td>
		</tr>
		<tr>
			<td><b><?php echo $form->field('pincode')->getLabel()?></b></td>
			<td style="text-align: left"><?php echo $form->field('pincode')->render()?></td>
			<td><b><?php echo $form->field('pincode_r')->getLabel()?></b></td>
			<td style="text-align: left"><?php echo $form->field('pincode_r')->render()?></td>
		</tr>
	<?php endif; ?>
		<tr class="ac-form-warning">
			<td colspan="2"><?php echo $form->field('locked')->getWarning() ?></td>
			<td colspan="2"></td>
		</tr>
		<tr>
			<td><b><?php echo $form->field('locked')->getLabel() ?></b></td>
			<td style="text-align: left"><?php echo $form->field('locked')->option('1')->render() ?></td>
			<td></td>
			<td></td>
		</tr>
		</tbody>
		<tfoot>
		<tr>
			<td colspan="4">
				<span style="float: right">
					<?php
					echo $form->field('ragnarok_edit_account')->render(),
					     $form->field('confirm_password')
					          ->placeholder($form->field('confirm_password')->getLabel())
					          ->css('margin-right', '10px')
					          ->render();
					if($account->server->login->getOption('use-pincode')) {
						echo $form->field('confirm_pincode')
						          ->placeholder($form->field('confirm_pincode')->getLabel())
						          ->css('margin-right', '10px')
						          ->render();
					}
					?>
					<input type="submit" value="<?php echo __('application', 'submit')?>">
				</span>
			</td>
		</tr>
		</tfoot>
	</table>
</form>
