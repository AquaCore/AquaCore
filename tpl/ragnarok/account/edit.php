<?php
use Aqua\Ragnarok\Account;
use Aqua\Ragnarok\Ragnarok;
/**
 * @var $account \Aqua\Ragnarok\Account
 * @var $page    \Page\Main\Ragnarok\Account
 */
?>
<form method="POST" autocomplete="off">
	<table class="ac-table">
		<thead>
		<tr>
			<td colspan="4"><?php echo __('ragnarok', 'account-info', htmlspecialchars($account->username))?></td>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td style="width: 15%"><?php echo __('ragnarok', 'password')?>:</td>
			<td style="width: 35%"><input type="password" name="password" autocomplete="off"></td>
			<td style="width: 15%"><?php echo __('ragnarok', 'password-repeat')?>:</td>
			<td style="width: 35%"><input type="password" name="password_r" autocomplete="off"></td>
		</tr>
<?php if($account->server()->login->usePincode) : ?>
		<tr>
			<td><?php echo __('ragnarok', 'pincode')?>:</td>
			<td><input type="password" name="pincode" maxlength="<?php echo Ragnarok::$pincode_max_length ?>" size="<?php echo Ragnarok::$pincode_min_length ?>" autocomplete="off"></td>
			<td><?php echo __('ragnarok', 'pincode-repeat')?>:</td>
			<td><input type="password" name="pincode_r" maxlength="<?php echo Ragnarok::$pincode_max_length ?>" size="<?php echo Ragnarok::$pincode_min_length ?>" autocomplete="off"></td>
		</tr>
<?php endif; ?>
		<tr>
			<td title="<?php echo __('ragnarok', 'lockdown-desc')?>"><label for="acc-lockdown"><?php echo __('ragnarok', 'locked')?></label>:</td>
			<td><input type="checkbox" name="lockdown" id="acc-lockdown" <?php echo $account->state === Account::STATE_LOCKED ? 'checked="checked"' : ''?>></td>
			<td></td>
			<td></td>
		</tr>
		</tbody>
		<tfoot>
		<tr>
			<td colspan="4">
				<span style="float: right">
					<input type="password"
					       name="confirm_password"
					       placeholder="<?php echo __('ragnarok', 'current-password')?>"
					       autocomplete="off">
<?php if($account->server()->login->usePincode) : ?>
					<input type="password"
					       name="confirm_pincode"
					       placeholder="<?php echo __('ragnarok', 'current-pincode')?>"
					       maxlength="<?php echo Ragnarok::$pincode_max_length ?>"
					       size="<?php echo Ragnarok::$pincode_min_length ?>"
					       autocomplete="off">
<?php endif; ?>
					<input type="submit" value="<?php echo __('application', 'submit')?>">
				</span>
			</td>
		</tr>
		</tfoot>
	</table>
</form>
