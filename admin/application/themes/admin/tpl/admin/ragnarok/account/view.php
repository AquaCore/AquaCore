<?php
use Aqua\Core\App;
/**
 * @var $account    \Aqua\Ragnarok\Account
 * @var $characters \Aqua\Ragnarok\Character[]
 * @var $page       \Page\Admin\Ragnarok
 */
$datetimeFormat = App::$settings->get('datetime_format', '');
?>
<table class="ac-table">
	<thead>
		<tr><td colspan="7"></td></tr>
	</thead>
	<tbody>
		<tr>
			<td><b><?php echo __('ragnarok', 'account-id') ?></b></td>
			<td><?php echo $account->id ?></td>
			<td><b><?php echo __('ragnarok', 'username') ?></b></td>
			<td ac-field="username"><?php echo htmlspecialchars($account->username) ?></td>
			<td colspan="2"><b><?php echo __('ragnarok', 'owner') ?></b></td>
			<td><?php echo ($account->owner ? '<a href="' . ac_build_url(array(
					'path' => array( 'user' ),
					'action' => 'view',
					'arguments' => array($account->owner)
				)) . '">' . $account->user()->display() . '</a>' : '--') ?></td>
		</tr>
		<tr>
			<td><b><?php echo __('ragnarok', 'state') ?></b></td>
			<td ac-field="state_name"><?php echo $account->state() ?></td>
			<td><b><?php echo __('ragnarok', 'sex') ?></b></td>
			<td ac-field="gender_name"><?php echo $account->gender() ?></td>
			<td colspan="2"><b><?php echo __('ragnarok', 'group') ?></b></td>
			<td ac-field="group_name"><?php echo $account->groupName() ?> <small>(<?php echo $account->groupId ?>)</small></td>
		</tr>
		<tr>
			<td><b><?php echo __('ragnarok', 'email') ?></b></td>
			<td ac-field="email"><?php echo htmlspecialchars($account->email) ?></td>
			<td><b><?php echo __('ragnarok', 'birthday') ?></b></td>
			<td><?php echo $account->birthday(App::$settings->get('date_format', '')) ?></td>
			<td colspan="2"><b><?php echo __('ragnarok', 'login-count') ?></b></td>
			<td><?php echo number_format($account->loginCount) ?></td>
		</tr>
		<tr>
			<td><b><?php echo __('ragnarok', 'last-login') ?></b></td>
			<td><?php echo $account->lastLogin($datetimeFormat) ?></td>
			<td><b><?php echo __('ragnarok', 'last-ip') ?></b></td>
			<td><?php echo ($account->lastIp ? htmlspecialchars($account->lastIp) : '--') ?></td>
			<td colspan="2"><b><?php echo __('ragnarok', 'unban-time') ?></b></td>
			<td><?php echo ($account->unbanTime ? $account->unbanTime($datetimeFormat) : '--') ?></td>
		</tr>
		<?php
		$row = array();
		if(!$account->server->login->getOption('use-md5')) {
			$row[] = array('password', __('ragnarok', 'password'), htmlspecialchars($account->password));
		}
		if($account->server->login->getOption('use-pincode')) {
			$row[] = array('pincode', __('ragnarok', 'pincode'), htmlspecialchars($account->pinCode));
		}
		if((int)$account->server->login->getOption('default-slots')) {
			$row[] = array('slots', __('ragnarok', 'slots'), number_format($account->slots));
		}
		if(!empty($row)) {
			echo '<tr>';
			for($i = 0; $i < 3; ++$i) {
				if($i === 2) echo '<td colspan="2">';
				else echo '<td>';
				if(empty($row[$i])) {
					echo '</td><td></td>';
					continue;
				}
				echo '<b>', $row[$i][1], '</b></td><td ac-field="', $row[$i][0], '">', $row[$i][2], '</td>';
			}
			echo '</tr>';
		}
		?>
		<tr class="ac-table-header">
			<td colspan="7"></td>
		</tr>
		<tr class="ac-table-header alt">
			<td></td>
			<td><?php echo __('ragnarok', 'name') ?></td>
			<td><?php echo __('ragnarok', 'base-level') ?></td>
			<td><?php echo __('ragnarok', 'job-level') ?></td>
			<td colspan="2"><?php echo __('ragnarok', 'guild') ?></td>
			<td><?php echo __('ragnarok', 'server') ?></td>
		</tr>
		<?php if(empty($characters)) : ?>
		<tr><td colspan="7" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td></tr>
	<?php else : foreach($characters as $char) : ?>
		<tr>
			<td><img src="<?php echo ac_char_head($char) ?>"></td>
			<td><a href="<?php echo ac_build_url(array(
				'path' => array( 'r', $char->charmap->server->key, $char->charmap->key ),
				'action' => 'viewchar',
				'arguments' => array( $char->id )
			)) ?>"><?php echo htmlspecialchars($char->name) ?></a></td>
			<td><?php echo number_format($char->baseLevel) ?></td>
			<td><?php echo number_format($char->jobLevel) ?></td>
			<?php if($char->guildId) : ?>
			<td><img src="<?php echo ac_guild_emblem($account->server->key, $char->charmap->key, $char->guildId) ?>"></td>
			<td><?php echo htmlspecialchars($char->guildName) ?></td>
			<?php else : ?>
			<td colspan="2"></td>
			<?php endif; ?>
			<td><a href="<?php echo ac_build_url(array(
				'path' => array( 'r', $char->charmap->server->key, $char->charmap->key )
			)) ?>"><?php echo htmlspecialchars($char->charmap->name) ?></a></td>
		</tr>
	<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="7">
				<?php if($account->owner !== 1 || App::user()->account->id === 1) : ?>
				<a class="ac-edit-account" href="<?php echo ac_build_url(array(
						'path' => array( 'r', $account->server->key ),
						'action' => 'editaccount',
						'arguments' => array( $account->id )
					)) ?>"><button class="ac-button"><?php echo __('ragnarok', 'edit-account') ?></button></a>
				<?php endif; ?>
			</td>
		</tr>
	</tfoot>
</table>
