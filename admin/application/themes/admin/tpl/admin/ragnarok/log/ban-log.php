<?php
use Aqua\Core\App;
use Aqua\Ragnarok\Server\Logs\BanLog;
/**
 * @var $logs      \Aqua\Ragnarok\Server\Logs\BanLog[]
 * @var $log_count int
 * @var $paginator \Aqua\UI\Pagination
 * @var $page      \Page\Admin\Ragnarok
 */
$datetime_format = App::settings()->get('datetime_format');
?>
<table class="ac-table">
	<thead>
		<tr class="alt">
			<td><?php echo __('ragnarok-ban-log', 'id') ?></td>
			<td><?php echo __('ragnarok-ban-log', 'account') ?></td>
			<td><?php echo __('ragnarok-ban-log', 'banned-by') ?></td>
			<td><?php echo __('ragnarok-ban-log', 'type') ?></td>
			<td><?php echo __('ragnarok-ban-log', 'date') ?></td>
			<td><?php echo __('ragnarok-ban-log', 'unban-date') ?></td>
		</tr>
		</tr>
	</thead>
	<tbody>
	<?php if($log_count === 0) : ?>
		<tr><td colspan="6" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td></tr>
	<?php else : foreach($logs as $log) : ?>
	<tr>
		<td><?php echo $log->id ?></td>
		<td><?php echo $log->account()->display() ?></td>
		<td><?php echo htmlspecialchars($log->banned()->username) ?></td>
		<td class="ac-ban-type <?php if($log->type === BanLog::TYPE_UNBAN) echo 'ac-unban' ?>"><?php echo $log->type() ?></td>
		<td><?php echo $log->banDate($datetime_format) ?></td>
		<td><?php echo $log->unbanDate($datetime_format) ?></td>
	</tr>
	<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
		<tr><td colspan="6"><?php echo $paginator->render() ?></td></tr>
	</tfoot>
</table>
<span class="ac-search-result"><?php echo __('application', 'search-results-' . ($log_count === 1 ? 's' : 'p'), number_format($log_count)) ?></span>
