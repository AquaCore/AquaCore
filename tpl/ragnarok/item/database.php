<?php
/**
 * @var $items      \Aqua\Ragnarok\ItemData[]
 * @var $item_count int
 * @var $paginator  \Aqua\UI\Pagination
 * @var $page       \Page\Main\Ragnarok\Server\Item
 */
$base_url = $page->server->charMapUri($page->charmap->key())->url(array(
	'path' => array( 'item' ),
	'action' => 'view',
	'arguments' => array( '' )
));
?>
<table class="ac-table">
	<thead>
		<tr>
			<td colspan="8">
				<form method="GET" style="float: right">
					<?php echo ac_form_path()?>
					<input type="text" name="id" value="<?php echo $page->request->uri->getString('id')?>" placeholder="<?php echo __('ragnarok', 'item-id') ?>" size="4">
					<input type="text" name="n" value="<?php echo $page->request->uri->getString('n')?>" placeholder="<?php echo __('ragnarok', 'name') ?>">
					<input type="submit" value="<?php echo __('application', 'search')?>">
				</form>
			</td>
		</tr>
		<tr class="alt">
			<td><?php echo __('ragnarok', 'item-id')?></td>
			<td></td>
			<td><?php echo __('ragnarok', 'name')?></td>
			<td><?php echo __('ragnarok', 'type')?></td>
			<td><?php echo __('ragnarok', 'weight')?></td>
			<td><?php echo __('ragnarok', 'buy-price')?></td>
			<td><?php echo __('ragnarok', 'sell-price')?></td>
			<td><?php echo __('ragnarok', 'custom-item')?></td>
		</tr>
	</thead>
	<tbody>
<?php if(empty($items)) : ?>
		<tr>
			<td colspan="8" class="ac-table-no-result"><?php echo __('ragnarok', '0-items')?></td>
		</tr>
<?php else : foreach($items as $item) : ?>
		<tr>
			<td style="width: 60px;"><?php echo $item->id?></td>
			<td style="width: 40px;"><img src="<?php echo ac_item_icon($item->id)?>"></td>
			<td><a href="<?php echo $base_url . $item->id?>"><?php echo htmlspecialchars($item->jpName)?></a></td>
			<td><?php echo $item->type()?></td>
			<td><?php echo $item->weight?></td>
			<td><?php echo number_format($item->buyingPrice)?><small>z</small></td>
			<td><?php echo number_format($item->sellingPrice)?><small>z</small></td>
			<td><?php echo __('application', ($item->custom ? 'yes' : 'no'))?></td>
		</tr>
<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="8" style="text-align: center"><?php echo $paginator->render()?></td>
		</tr>
	</tfoot>
</table>
<span class="ac-search-result"><?php echo __('ragnarok', 'x-items', number_format($item_count))?></span>
