<?php
use Aqua\Core\App;
use Aqua\User\Role;

/**
 * @var \Aqua\Log\PaypalLog $txn
 * @var \Page\Admin\Log     $page
 */

$datetime_format = App::settings()->get('datetime_format');
?>
<table class="ac-table paypal-table">
	<thead>
		<tr class="alt">
			<td colspan="4"></td>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td class="ac-table-label"><?php echo __('donation', 'id') ?></td>
			<td><?php echo $txn->id ?></td>
			<td class="ac-table-label"><?php echo __('donation', 'user') ?></td>
			<td>
				<?php
				if($txn->userId) echo '<a href="', ac_build_url(array(
						'path' => array( 'user' ),
						'action' => 'view',
						'arguments' => array( $txn->userId )
					)), '">', $txn->account()->display(), '</a>';
				else echo Role::get(Role::ROLE_GUEST)->display(null, 'ac-guest');
				?>
			</td>
		</tr>
		<tr>
			<td class="ac-table-label"><?php echo __('donation', 'sandbox') ?></td>
			<td><?php echo __('application', $txn->sandbox ? 'yes' : 'no') ?></td>
			<td class="ac-table-label"><?php echo __('donation', 'ip-address') ?></td>
			<td><?php echo htmlspecialchars($txn->ipAddress) ?></td>
		</tr>
		<tr>
			<td class="ac-table-label"><?php echo __('donation', 'payer-id') ?></td>
			<td><?php echo htmlspecialchars($txn->payerId) ?></td>
			<td class="ac-table-label"><?php echo __('donation', 'payer-status') ?></td>
			<td><?php echo htmlspecialchars($txn->payerStatus) ?></td>
		</tr>
		<tr>
			<td class="ac-table-label"><?php echo __('donation', 'payer-name') ?></td>
			<td><?php echo htmlspecialchars($txn->firstName), ' ', htmlspecialchars($txn->lastName) ?></td>
			<td class="ac-table-label"><?php echo __('donation', 'payer-email') ?></td>
			<td><?php echo htmlspecialchars($txn->payerEmail) ?></td>
		</tr>
		<tr>
			<td class="ac-table-label"><?php echo __('donation', 'receiver-id') ?></td>
			<td><?php echo htmlspecialchars($txn->receiverId) ?></td>
			<td class="ac-table-label"><?php echo __('donation', 'receiver-email') ?></td>
			<td><?php echo htmlspecialchars($txn->receiverEmail) ?></td>
		</tr>
		<tr>
			<td class="ac-table-label"><?php echo __('donation', 'item-name') ?></td>
			<td><?php echo htmlspecialchars($txn->itemName) ?></td>
			<td class="ac-table-label"><?php echo __('donation', 'quantity') ?></td>
			<td><?php echo number_format($txn->quantity) ?></td>
		</tr>
		<tr>
			<td class="ac-table-label"><?php echo __('donation', 'credits') ?></td>
			<td><?php echo __('donation', 'credit-points', number_format($txn->credits)) ?></td>
			<td class="ac-table-label"><?php echo __('donation', 'credit-exchange-rate') ?></td>
			<td><?php echo number_format($txn->creditExchangeRate, 2) ?></td>
		</tr>
		<tr>
			<td class="ac-table-label"><?php echo __('donation', 'deposited') ?></td>
			<td><?php echo number_format($txn->deposited, 2) ?></td>
			<td class="ac-table-label"><?php echo __('donation', 'currency') ?></td>
			<td><?php echo htmlspecialchars($txn->currency) ?></td>
		</tr>
		<tr>
			<td class="ac-table-label"><?php echo __('donation', 'gross') ?></td>
			<td><?php echo number_format($txn->gross, 2) ?></td>
			<td class="ac-table-label"><?php echo __('donation', 'fee') ?></td>
			<td><?php echo number_format($txn->fee, 2) ?></td>
		</tr>
		<tr>
			<td class="ac-table-label"><?php echo __('donation', 'payment-date') ?></td>
			<td><?php echo $txn->paymentDate($datetime_format) ?></td>
			<td class="ac-table-label"><?php echo __('donation', 'process-date') ?></td>
			<td><?php echo $txn->processDate($datetime_format) ?></td>
		</tr>
		<tr>
			<td class="ac-table-label"><?php echo __('donation', 'txn-id') ?></td>
			<td><?php echo htmlspecialchars($txn->transactionId) ?></td>
			<td class="ac-table-label"><?php echo __('donation', 'parent-txn-id') ?></td>
			<td>
				<?php
				if(!$txn->parentTransactionId) echo '--';
				else if(!($parent = $txn->parentTransaction())) echo htmlspecialchars($txn->parentTransactionId);
				else echo '<a href="', ac_build_url(array(
						'path' => 'log',
						'action' => 'view_paypal',
						'arguments' => array( $parent->id )
					)), '">', htmlspecialchars($txn->parentTransactionId), '</a>';
				?>
			</td>
		</tr>
		<tr>
			<td class="ac-table-label"><?php echo __('donation', 'txn-type') ?></td>
			<td><?php echo $txn->transactionType() ?></td>
			<td class="ac-table-label"><?php echo __('donation', 'payment-type') ?></td>
			<td><?php echo $txn->paymentType() ?></td>
		</tr>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="4"></td>
		</tr>
	</tfoot>
</table>