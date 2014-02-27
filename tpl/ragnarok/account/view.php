<?php
/**
 * @var $account \Aqua\Ragnarok\Account
 * @var $page    \Page\Main\Ragnarok\Account
 */
?>
<table class="ac-table">
	<colgroup>
		<col style="width: 20%">
		<col style="width: 30%">
		<col style="width: 20%">
		<col style="width: 30%">
	</colgroup>
	<thead>
		<tr>
			<td colspan="4" style="text-align: justify">
				<?php echo __('ragnarok', 'account-info', htmlspecialchars($account->username))?>
			</td>
		</tr>
	</thead>
	<tbody>
	<tr>
		<td><b><?php echo __('ragnarok', 'username')?></b></td>
		<td><?php echo htmlspecialchars($account->username)?></td>
		<td><b><?php echo __('ragnarok', 'server')?></b></td>
		<td><?php echo $account->server->name?></td>
	</tr>
	<tr>
		<td><b><?php echo __('ragnarok', 'sex')?></b></td>
		<td><?php echo $account->gender()?></td>
		<td><b><?php echo __('ragnarok', 'group')?></b></td>
		<td><?php echo $account->groupName()?> <small>(<?php echo $account->groupId?>)</small></td>
	</tr>
	<tr>
		<td><b><?php echo __('ragnarok', 'state')?></b></td>
		<td><?php echo $account->state()?></td>
		<td><b><?php echo __('ragnarok', 'last-ip')?></b></td>
		<td><?php echo $account->lastIp ?: '--'?></td>
	</tr>
	<tr>
		<td><b><?php echo __('ragnarok', 'last-login')?></b></td>
		<td><?php echo $account->lastLogin(\Aqua\Core\App::settings()->get('datetime_format'))?></td>
		<td><b><?php echo __('ragnarok', 'login-count')?></b></td>
		<td><?php echo number_format($account->loginCount)?></td>
	</tr>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="4">
			</td>
		</tr>
	</tfoot>
</table>
