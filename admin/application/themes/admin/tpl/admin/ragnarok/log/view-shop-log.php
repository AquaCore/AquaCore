<?php
/**
 * @var $log  \Aqua\Ragnarok\Server\Logs\CashShopLog
 * @var $page \Page\Admin\Ragnarok\Server
 */

use Aqua\Core\App;
?>
<table class="ac-table ac-table-fixed">
	<thead><tr><td colspan="6"></td></tr></thead>
	<tbody>
	<tr>
		<td><b><?php echo __('ragnarok', 'id') ?></b></td>
		<td><?php echo $log->id ?></td>
		<td><b><?php echo __('ragnarok', 'date') ?></b></td>
		<td><?php echo $log->date(App::settings()->get('datetime_format')) ?></td>
		<td><b><?php echo __('profile', 'ip-address') ?></b></td>
		<td><?php echo htmlspecialchars($log->ipAddress) ?></td>
	</tr>
	<tr>
		<td><b><?php echo __('ragnarok', 'account') ?></b></td>
		<td><a href="<?php echo $log->charmap->server->url(array(
				'action' => 'viewaccount',
				'arguments' => array( $log->accountId )
			)) ?>"><?php echo htmlspecialchars($log->account()->username) ?></a></td>
		<td><b><?php echo __('ragnarok', 'amount') ?></b></td>
		<td><?php echo number_format($log->amount) ?></td>
		<td><b><?php echo __('profile', 'subtotal') ?></b></td>
		<td><?php echo __('donation', 'credit-points', number_format($log->total)) ?></td>
	</tr>
	<tr class="ac-table-header alt">
		<td></td>
		<td><?php echo __('ragnarok', 'item-id') ?></td>
		<td colspan="2"><?php echo __('ragnarok', 'item') ?></td>
		<td><?php echo __('ragnarok', 'amount') ?></td>
		<td><?php echo __('ragnarok', 'price') ?></td>
	</tr>
	<?php foreach($log->cart() as $id => $data) : ?>
		<tr>
			<td><img src="<?php echo ac_item_icon($id) ?>"></td>
			<td><?php echo $id ?></td>
			<?php if($item = $log->charmap->item($id)) : ?>
				<td colspan="2"><a href="<?php echo $log->charmap->url(array(
						'path' => array( 'item' ),
				        'action' => 'view',
				        'arguments' => array( $item->id )
					), false) ?>"><?php echo htmlspecialchars($item->jpName) ?></a></td>
			<?php else : ?>
				<td colspan="2"><?php echo __('ragnarok', 'deleted', $id) ?></td>
			<?php endif; ?>
			<td><?php echo number_format($data['amount']) ?></td>
			<td><?php echo __('donation', 'credit-points', number_format($data['price'])) ?></td>
		</tr>
	<?php endforeach ?>
	</tbody>
	<tfoot><tr><td colspan="6"></td></tr></tfoot>
</table>
