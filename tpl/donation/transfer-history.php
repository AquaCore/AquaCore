<?php
use Aqua\Core\App;
/**
 * @var $transfers      \Aqua\Log\TransferLog[]
 * @var $transfer_count int
 * @var $paginator      \Aqua\UI\Pagination
 * @var $page           \Page\Main\Donation
 */
$datetime_format = App::settings()->get('datetime_format');
?>
<table class="ac-table">
	<thead>
		<tr class="alt">
			<td><?php echo __('donation', 'sender') ?></td>
			<td><?php echo __('donation', 'receiver') ?></td>
			<td><?php echo __('donation', 'credits') ?></td>
			<td><?php echo __('donation', 'xfer-date') ?></td>
		</tr>
	</thead>
	<tbody>
	<?php if($transfer_count < 1) : ?>
		<tr><td colspan="4" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td></tr>
	<?php else : foreach($transfers as $t) : ?>
		<tr>
			<td><?php echo $t->sender()->display() ?></td>
			<td><?php echo $t->receiver()->display() ?></td>
			<td><?php echo __('donation', 'credit-points', number_format($t->amount)) ?></td>
			<td><?php echo $t->date($datetime_format) ?></td>
		</tr>
	<?php endforeach; endif; ?>

	</tbody>
	<tfoot>
		<tr><td colspan="4"><?php echo $paginator->render() ?></td></tr>
	</tfoot>
</table>
<span class="ac-search-result"><?php echo __('application', 'search-results-' . ($transfer_count === 1 ? 's' : 'p'), number_format($transfer_count)) ?></span>
