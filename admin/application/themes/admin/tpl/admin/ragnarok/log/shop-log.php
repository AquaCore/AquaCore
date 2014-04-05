<?php
/**
 * @var $log       \Aqua\Ragnarok\Server\Logs\CashSHopLog[]
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
			<td><?php echo __('ragnarok', 'account') ?></td>
			<td><?php echo __('ragnarok', 'items-purchased') ?></td>
			<td><?php echo __('ragnarok', 'subtotal') ?></td>
		</tr>
	</thead>
	<tbody>
	<?php if(empty($log)) : ?>
		<tr><td colspan="5" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td></tr>
	<?php else : foreach($log as $purchase) : ?>
		<tr>
			<td><?php echo $purchase->id ?></td>
			<td><?php echo $purchase->date($datetimeFormat) ?></td>
			<td><a href="<?php echo ac_build_url(array(
				'path' => array( 'r', $purchase->charmap->server->key ),
			    'action' => 'viewaccount',
			    'arguments' => array( $purchase->accountId )
			)) ?>"><?php echo htmlspecialchars($purchase->account()->username) ?></a></td>
			<td><?php echo number_format($purchase->amount) ?></td>
			<td><?php echo __('donation', 'credit-points', number_format($purchase->total)) ?></td>
		</tr>
	<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
		<tr><td colspan="5"><?php echo $paginator->render() ?></td></tr>
	</tfoot>
</table>
<span class="ac-search-result"><?php echo __('application',
                                             'search-results-' . ($count === 1 ? 's' : 'p'),
                                             number_format($count)) ?></span>
