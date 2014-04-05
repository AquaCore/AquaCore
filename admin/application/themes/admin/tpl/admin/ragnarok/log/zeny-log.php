<?php
/**
 * @var $log       \Aqua\Ragnarok\Server\Logs\ZenyLog[]
 * @var $count     int
 * @var $paginator \Aqua\UI\Pagination
 * @var $page      \Page\Admin\Ragnarok\Server
 */

use Aqua\Core\App;

$datetimeFormat = App::settings()->get('datetime_format');

?>
<table class="ac-table">
	<thead>
	<tr class="alt">
		<td><?php echo __('ragnarok', 'id') ?></td>
		<td><?php echo __('ragnarok', 'date') ?></td>
		<td><?php echo __('ragnarok', 'map') ?></td>
		<td><?php echo __('ragnarok', 'type') ?></td>
		<td><?php echo __('ragnarok', 'target') ?></td>
		<td><?php echo __('ragnarok', 'source') ?></td>
		<td><?php echo __('ragnarok', 'amount') ?></td>
	</tr>
	</thead>
	<tbody>
	<?php if(empty($log)) : ?>
		<tr><td colspan="7" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td></tr>
	<?php else : foreach($log as $zeny) : ?>
		<tr>
			<td><?php echo $zeny->id ?></td>
			<td><?php echo $zeny->date($datetimeFormat) ?></td>
			<td><?php echo htmlspecialchars($zeny->map) ?: '--' ?></td>
			<td><?php echo $zeny->type() ?></td>
			<td><?php echo htmlspecialchars($zeny->target()->name) ?></td>
			<td><?php echo htmlspecialchars($zeny->source()->name) ?></td>
			<td><?php echo number_format($zeny->amount) ?></td>
		</tr>
	<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
	<tr><td colspan="8"><?php echo $paginator->render() ?></td></tr>
	</tfoot>
</table>
<span class="ac-search-result"><?php echo __('application',
                                             'search-results-' . ($count === 1 ? 's' : 'p'),
                                             number_format($count)) ?></span>
