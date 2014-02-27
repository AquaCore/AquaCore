<?php
use Aqua\Core\App;
/**
 * @var $logs      \Aqua\Ragnarok\Server\Logs\LoginLog[]
 * @var $log_count int
 * @var $paginator \Aqua\UI\Pagination
 * @var $page      \Page\Admin\Ragnarok
 */
$datetime_format = App::settings()->get('datetime_format');
?>
<table class="ac-table">
	<thead>
		<tr class="alt">
			<td><?php echo __('ragnarok-login-log', 'username') ?></td>
			<td><?php echo __('ragnarok-login-log', 'ip-address') ?></td>
			<td><?php echo __('ragnarok-login-log', 'response') ?></td>
			<td><?php echo __('ragnarok-login-log', 'message') ?></td>
			<td><?php echo __('ragnarok-login-log', 'date') ?></td>
		</tr>
	</thead>
	<tbody>
	<?php if($log_count === 0) : ?>
		<tr><td colspan="5" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td></tr>
	<?php else : foreach($logs as $log) : ?>
	<tr>
		<td><?php echo htmlspecialchars($log->username) ?></td>
		<td><?php echo htmlspecialchars($log->ipAddress) ?></td>
		<td class="ac-login-status ac-login-<?php echo ($log->code === 100 ? 'ok' : 'fail') ?>"><?php echo $log->response() ?></td>
		<td><?php echo htmlspecialchars($log->message) ?></td>
		<td><?php echo $log->date($datetime_format) ?></td>
	</tr>
	<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
		<tr><td colspan="5"><?php echo $paginator->render() ?></td></tr>
	</tfoot>
</table>
<span class="ac-search-result"><?php echo __('application', 'search-results-' . ($log_count === 1 ? 's' : 'p'), number_format($log_count)) ?></span>
