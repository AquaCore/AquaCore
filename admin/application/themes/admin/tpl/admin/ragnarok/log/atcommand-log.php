<?php
/**
 * @var $log       \Aqua\Ragnarok\Server\Logs\AtcommandLog[]
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
		<td><?php echo __('ragnarok', 'account') ?></td>
		<td><?php echo __('ragnarok', 'character-id') ?></td>
		<td><?php echo __('ragnarok', 'character') ?></td>
		<td><?php echo __('ragnarok', 'command') ?></td>
		<td><?php echo __('ragnarok', 'parameters') ?></td>
	</tr>
	</thead>
	<tbody>
	<?php if(empty($log)) : ?>
		<tr><td colspan="8" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td></tr>
	<?php else : foreach($log as $atcmd) : ?>
		<tr>
			<td><?php echo $atcmd->id ?></td>
			<td><?php echo $atcmd->date($datetimeFormat) ?></td>
			<td><?php echo htmlspecialchars($atcmd->map) ?: '--' ?></td>
			<td><?php echo htmlspecialchars($atcmd->account()->username) ?></td>
			<td><?php echo $atcmd->charId ?: '--' ?></td>
			<td><?php echo htmlspecialchars($atcmd->charName) ?: '--' ?></td>
			<td><?php echo htmlspecialchars($atcmd->command()) ?></td>
			<td><?php echo htmlspecialchars($atcmd->parameters()) ?: '--' ?></td>
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
