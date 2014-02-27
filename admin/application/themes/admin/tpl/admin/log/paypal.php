<?php
use Aqua\Core\App;
use Aqua\User\Role;

/**
 * @var \Aqua\Log\PayPalLog[] $txn
 * @var int                   $txn_count
 * @var \Page\Admin\Log       $page
 * @var \Aqua\UI\Pagination   $paginator
 */

$date_format = App::settings()->get('datetime_format', '');
$guest = Role::get(Role::ROLE_GUEST)->display(null, 'ac-guest');
$base_acc_url = ac_build_url(array(
		'path' => array( 'user' ),
		'action' => 'view',
		'arguments' => array( '' )
	));
$base_txn_url = ac_build_url(array(
		'path' => array( 'log' ),
		'action' => 'viewpaypal',
		'arguments' => array( '' )
	));
?>
<table class="ac-table">
	<thead>
		<tr class="alt">
			<td><?php echo __('donation', 'id')?></td>
			<td><?php echo __('donation', 'user')?></td>
			<td><?php echo __('donation', 'deposited')?></td>
			<td><?php echo __('donation', 'gross')?></td>
			<td><?php echo __('donation', 'credits')?></td>
			<td><?php echo __('donation', 'txn-type')?></td>
			<td><?php echo __('donation', 'payer-email')?></td>
			<td><?php echo __('donation', 'process-date')?></td>
			<td><?php echo __('donation', 'payment-date')?></td>
		</tr>
	</thead>
	<tbody>
	<?php if(empty($txn)) : ?>
		<tr><td colspan="9" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td></tr>
	<?php else : foreach($txn as $t) : ?>
	<tr>
		<td><a href="<?php echo $base_txn_url . $t->id ?>"><?php echo $t->id ?></a></td>
		<?php if($t->userId) : ?>
		<td><a href="<?php echo $base_acc_url . $t->userId ?>"><?php echo $t->account()->display() ?></a></td>
		<?php else : ?>
		<td><?php echo $guest ?></td>
		<?php endif; ?>
		<td><?php echo number_format($t->deposited, 2) ?> <small><?php echo $t->currency ?></small></td>
		<td><?php echo number_format($t->gross, 2) ?> <small><?php echo $t->currency ?></small></td>
		<td><?php echo __('donation', 'credit-points', number_format($t->credits)) ?></td>
		<td><?php echo $t->transactionType() ?></td>
		<td><?php echo htmlspecialchars($t->payerEmail) ?></td>
		<td><?php echo $t->processDate($date_format) ?></td>
		<td><?php echo $t->paymentDate($date_format) ?></td>
	</tr>
	<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
		<tr><td colspan="9"><?php echo $paginator->render() ?></td></tr>
	</tfoot>
</table>
<span class="ac-search-result"><?php echo __('application', 'search-results-' . ($txn_count === 1 ? 's' : 'p'), number_format($txn_count)) ?></span>
