<?php
use Aqua\Core\App;
/**
 * @var $logs      \Aqua\Ragnarok\Server\Logs\PasswordResetLog[]
 * @var $log_count int
 * @var $paginator \Aqua\UI\Pagination
 * @var $page      \Page\Admin\Ragnarok
 */
$datetime_format = App::settings()->get('datetime_format');
$base_url = ac_build_url(array(
		'path' => array( 'ro', $page->server->key ),
		'action' => 'viewaccount',
		'arguments' => array( '' )
	))
?>
<table class="ac-table">
	<thead>
	<tr class="alt">
		<td><?php echo __('ragnarok-password-reset-log', 'id') ?></td>
		<td><?php echo __('ragnarok-password-reset-log', 'account') ?></td>
		<td><?php echo __('ragnarok-password-reset-log', 'ip-address') ?></td>
		<td><?php echo __('ragnarok-password-reset-log', 'reset-key') ?></td>
		<td><?php echo __('ragnarok-password-reset-log', 'request-date') ?></td>
		<td><?php echo __('ragnarok-password-reset-log', 'reset-date') ?></td>
	</tr>
	</thead>
	<tbody>
	<?php if($log_count === 0) : ?>
		<tr><td colspan="6" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td></tr>
	<?php else : foreach($logs as $log) : ?>
		<tr>
			<td><?php echo $log->id ?></td>
			<td><a href="<?php echo $base_url . $log->accountId ?>"><?php echo htmlspecialchars($log->account()->username) ?></a></td>
			<td><?php echo htmlspecialchars($log->ipAddress) ?></td>
			<td><?php echo htmlspecialchars($log->key) ?></td>
			<td><?php echo $log->requestDate($datetime_format) ?></td>
			<td><?php echo ($log->resetDate ? $log->resetDate($datetime_format) : '--' )?></td>
		</tr>
	<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
	<tr><td colspan="6"><?php echo $paginator->render() ?></td></tr>
	</tfoot>
</table>
<span class="ac-search-result"><?php echo __('application', 'search-results-' . ($log_count === 1 ? 's' : 'p'), number_format($log_count)) ?></span>
