<?php
use Aqua\Ragnarok\Server;
use Aqua\Core\App;
/**
 * @var $ragnarok_accounts \Aqua\Ragnarok\Account[]
 * @var $account           \Aqua\User\Account
 * @var $page              \Page\Main\Account
 */
$multiserver = (Server::$serverCount > 1 ? 1 : 0);
$datetime_format = App::settings()->get('datetime_format');
$rows = 5 + min(1, Server::$serverCount);
?>
<table class="ac-table">
	<thead>
	<tr>
		<td colspan="<?php echo $rows ?>"></td>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td style="width: 20%; vertical-align: middle; text-align: center; border-right: 1px solid #d9e1e9" colspan="1" rowspan="6"><img style="max-width: 150px" src="<?php echo $account->avatar() ?>"></td>
	</tr>
	<tr>
		<td colspan="1" style="width: 120px"><b><?php echo __('profile', 'display-name')?>:</b></td>
		<td colspan="4"><?php echo $account->display()->render() ?></td>
	</tr>
	<tr style="width: 120px">
		<td colspan="1"><b><?php echo __('profile', 'username')?>:</b></td>
		<td colspan="4"><?php echo htmlspecialchars($account->username) ?></td>
	</tr>
	<tr>
		<td colspan="1"><b><?php echo __('profile', 'email')?>:</b></td>
		<td colspan="4"><?php echo $account->email ?></td>
	</tr>
	<tr>
		<td colspan="1"><b><?php echo __('donation', 'credits')?>:</b></td>
		<td colspan="4"><?php echo __('donation', 'credit-points', number_format($account->credits)) ?></td>
	</tr>
	<tr>
		<td colspan="1"><b><?php echo __('profile', 'birthday')?>:</b></td>
		<td colspan="4"><?php echo $account->birthDate(App::settings()->get('date_format', ''))?></td>
	</tr>
	<?php if(Server::$serverCount > 0) : ?>
	<tr class="ac-table-header alt">
		<td><?php echo __('ragnarok', 'username')?></td>
		<td><?php echo __('ragnarok', 'sex')?></td>
		<td><?php echo __('ragnarok', 'group')?></td>
		<td><?php echo __('ragnarok', 'state')?></td>
		<td><?php echo __('ragnarok', 'last-login')?></td>
		<?php if(Server::$serverCount > 1) : ?>
			<td><?php echo __('ragnarok', 'server')?></td>
		<?php endif; ?>
	</tr>
	<?php if(empty($ragnarok_accounts)) : ?>
		<tr>
			<td colspan="<?php echo $rows ?>" style="text-align: center; font-style: italic;">
				<?php echo __('ragnarok', 'no-accounts-registered')?>
			</td>
		</tr>
	<?php else : foreach($ragnarok_accounts as $acc) : ?>
		<tr>
			<td><a href="<?php echo $acc->url()?>"><?php echo htmlspecialchars($acc->username)?></a></td>
			<td><?php echo $acc->gender()?></td>
			<td><?php echo $acc->groupName()?><small> (<?php echo $acc->groupId?>)</small></td>
			<td><?php echo $acc->state()?></td>
			<td><?php echo $acc->lastLogin($datetime_format)?></td>
			<?php if(Server::$serverCount > 1) : ?>
				<td><a href="<?php echo $acc->server->url()?>"><?php echo $acc->server->name?></a></td>
			<?php endif; ?>
		</tr>
	<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
	<tr>
		<td colspan="<?php echo $rows ?>">
			<a href="<?php echo ac_build_url(array(
					'path' => array( 'ragnarok' ),
					'action' => 'register'
				))?>">
				<button type="button" class="ac-button ac-register-ragnarok-account">
					<?php echo __('ragnarok', 'register-account')?>
				</button>
			</a>
		</td>
	</tr>
	</tfoot>
	<?php else : ?>
	<tfoot>
		<tr>
			<td colspan="<?php echo $rows?>"></td>
		</tr>
	</tfoot>
	<?php endif; ?>
</table>
