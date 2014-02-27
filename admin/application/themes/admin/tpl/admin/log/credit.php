<?php
use Aqua\Core\App;

/**
 * @var \Aqua\Log\TransferLog[] $xfer
 * @var int                     $xfer_count
 * @var \Page\Admin\Log         $page
 * @var \Aqua\UI\Pagination     $paginator
 */

$date_format = App::settings()->get('datetime_format', '');
$base_acc_url = ac_build_url(array(
		'path' => array( 'user' ),
		'action' => 'view',
		'arguments' => array( '' )
	));
?>
<table class="ac-table">
	<thead>
	<tr class="alt">
		<td><?php echo __('donation', 'id')?></td>
		<td><?php echo __('donation', 'sender') ?></td>
		<td><?php echo __('donation', 'receiver') ?></td>
		<td><?php echo __('donation', 'credits') ?></td>
		<td><?php echo __('donation', 'xfer-date') ?></td>
	</tr>
	</thead>
	<tbody>
	<?php if(empty($xfer)) : ?>
		<tr><td colspan="5" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td></tr>
	<?php else : foreach($xfer as $x) : ?>
		<tr>
			<td><?php echo $x->id ?></td>
			<td><a href="<?php echo $base_acc_url . $x->senderId ?>"><?php echo $x->sender()->display() ?></a></td>
			<td><a href="<?php echo $base_acc_url . $x->receiverId ?>"><?php echo $x->receiver()->display() ?></a></td>
			<td><?php echo __('donation', 'credit-points', number_format($x->amount)) ?></td>
			<td><?php echo $x->date($date_format) ?></td>
		</tr>
	<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
	<tr><td colspan="5"><?php echo $paginator->render() ?></td></tr>
	</tfoot>
</table>
<span class="ac-search-result"><?php echo __('application', 'search-results-' . ($xfer_count === 1 ? 's' : 'p'), number_format($xfer_count)) ?></span>
