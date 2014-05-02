<?php
use Aqua\Core\App;
/**
 * @var $transactions      \Aqua\Log\PayPalLog[]
 * @var $transaction_count int
 * @var $paginator         \Aqua\UI\Pagination
 * @var $page              \Page\Main\Donate
 */
$datetime_format = App::settings()->get('datetime_format');
?>
<table class="ac-table">
	<thead>
	<tr class="alt">
		<td><?php echo __('donation', 'payer-email') ?></td>
		<td><?php echo __('donation', 'gross') ?></td>
		<td><?php echo __('donation', 'credits') ?></td>
		<td><?php echo __('donation', 'process-date') ?></td>
		<td><?php echo __('donation', 'payment-date') ?></td>
	</tr>
	</thead>
	<tbody>
	<?php if(empty($transactions)) : ?>
		<tr><td colspan="5" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td></tr>
	<?php else : foreach($transactions as $t) : ?>
		<tr>
			<td><?php echo htmlspecialchars($t->payerEmail) ?></td>
			<td><?php echo number_format($t->gross, 2) ?> <small><?php echo $t->currency ?></small></td>
			<td><?php echo __('donation', 'credit-points', number_format($t->credits)) ?></td>
			<td><?php echo $t->processDate($datetime_format) ?></td>
			<td><?php echo $t->paymentDate($datetime_format) ?></td>
		</tr>
	<?php endforeach; endif; ?>

	</tbody>
	<tfoot>
	<tr><td colspan="5"><?php echo $paginator->render() ?></td></tr>
	</tfoot>
</table>
<span class="ac-search-result"><?php echo __('application', 'search-results-' . ($transaction_count === 1 ? 's' : 'p'), number_format($transaction_count)) ?></span>
