<?php
use Aqua\Core\App;
/**
 * @var $accounts      \Aqua\Ragnarok\Account[]
 * @var $account_count int
 * @var $paginator     \Aqua\UI\Pagination
 * @var $page          \Page\Admin\Ragnarok
 */
$datetime_format = App::settings()->get('datetime_format');
$base_acc_url = ac_build_url(array(
		'path' => array( 'ro', urlencode($page->server->key) ),
		'action' => 'viewaccount',
		'arguments' => array( '' )
	));
$base_user_url = ac_build_url(array(
		'path' => array( 'user' ),
		'action' => 'view',
		'arguments' => array( '' )
	));
?>
<table class="ac-table">
	<thead>
		<tr class="alt">
			<td><?php echo __('ragnarok-account', 'id') ?></td>
			<td><?php echo __('ragnarok-account', 'username') ?></td>
			<td><?php echo __('ragnarok-account', 'owner') ?></td>
			<td><?php echo __('ragnarok-account', 'sex') ?></td>
			<td><?php echo __('ragnarok-account', 'group') ?></td>
			<td><?php echo __('ragnarok-account', 'state') ?></td>
			<td><?php echo __('ragnarok-account', 'last-ip') ?></td>
			<td><?php echo __('ragnarok-account', 'last-login') ?></td>
		</tr>
	</thead>
	<tbody>
	<?php if($account_count === 0) : ?>
		<tr><td colspan="8" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td></tr>
	<?php else : foreach($accounts as $acc) : ?>
		<tr>
			<td><?php echo $acc->id ?></td>
			<td><a href="<?php echo $base_acc_url . $acc->id ?>"><?php echo htmlspecialchars($acc->username) ?></a></td>
			<?php if($acc->owner) : ?>
				<td><a href="<?php echo $base_user_url . $acc->owner ?>"><?php echo $acc->user()->display() ?></a></td>
			<?php else : ?>
				<td>--</td>
			<?php endif; ?>
			<td><?php echo $acc->gender() ?></td>
			<td><?php echo $acc->groupName() ?> <small>(<?php echo $acc->groupId ?>)</small></td>
			<td><?php echo $acc->state() ?></td>
			<td><?php echo ($acc->lastIp ? htmlspecialchars($acc->lastIp) : '--') ?></td>
			<td><?php echo $acc->lastLogin($datetime_format) ?></td>
		</tr>
	<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
		<tr><td colspan="9"><?php echo $paginator->render() ?></td></tr>
	</tfoot>
</table>
<span class="ac-search-result"><?php echo __('application', 'search-results-' . ($account_count === 1 ? 's' : 'p'), number_format($account_count)) ?></span>
