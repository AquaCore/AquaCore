<?php
/**
 * @var $items     \Aqua\Ragnarok\Item[]
 * @var $itemCount int
 * @var $paginator \Aqua\UI\Pagination
 * @var $search    \Aqua\UI\Search
 * @var $page      \Page\Admin\Ragnarok|\Page\Admin\Ragnarok\Server
 */

use Aqua\Core\App;
use Aqua\UI\ScriptManager;

$dateTimeFormat = App::settings()->get('datetime_format');
$page->theme->footer->enqueueScript(ScriptManager::script('aquacore.build-url'));
?>
<table class="ac-table">
	<colgroup>
		<col style="width: 60px">
	</colgroup>
	<thead>
		<tr>
			<td colspan="11">
				<form method="GET">
					<div style="float: left">
						<?php
						echo $search->limit()->attr('class', 'ac-search-limit')->render();
						if($page instanceof \Page\Admin\Ragnarok) {
							echo '<select class="ac-select-server" name="1">';
							foreach($page->server->charmap as $charmap) {
								echo '<option value="',
								     htmlspecialchars($charmap->key), '"' ,
								     ($page->request->uri->arg(1) === $charmap->key ? ' selected' : ''), '>',
								     htmlspecialchars($charmap->name), '</option>';
							}
							echo '</select>';
						}
						?>
					</div>
				</form>
				<form method="GET">
					<?php echo ac_form_path() ?>
					<div style="float: right">
						<?php echo $search->field('type')->render(), ' ',
						           $search->field('name')->placeholder($search->field('name')->getLabel())->render() ?>
						<input type="submit" value="<?php echo __('application', 'submit') ?>">
					</div>
				</form>
			</td>
		</tr>
		<tr class="alt">
			<td></td>
			<td><?php echo __('ragnarok', 'name')?></td>
			<td><?php echo __('ragnarok', 'amount')?></td>
			<td><?php echo __('ragnarok', 'identified')?></td>
			<td><?php echo __('ragnarok', 'expire-time')?></td>
			<td><?php echo __('ragnarok', 'bound')?></td>
			<td><?php echo __('ragnarok', 'unique-id')?></td>
			<td colspan="4"><?php echo __('ragnarok', 'cards')?></td>
		</tr>
	</thead>
	<tbody>
	<?php if(empty($items)) : ?>
		<tr><td class="ac-table-no-result" colspan="11"><?php echo __('application', 'no-search-results')?></td></tr>
	<?php else : foreach($items as $item) : ?>
		<tr>
			<td class="ac-item-icon"><img src="<?php echo ac_item_icon($item->itemId)?>"></td>
			<td class="ac-item-name"><a href="<?php echo $item->charmap->url(array(
					'path'      => array( 'item' ),
					'action'    => 'view',
					'arguments' => array( $item->itemId )
				), false) ?>"><?php echo $item->name(false)?></a></td>
			<td class="ac-item-amount"><?php echo number_format($item->amount)?></td>
			<td><?php echo __('application', $item->identified ? 'yes' : 'no') ?></td>
			<td><?php echo ($item->expire ? $item->expireTime($dateTimeFormat) : '--') ?></td>
			<td><?php echo ($item->bound ? $item->bindType() : __('application', 'no')) ?></td>
			<td><?php echo $item->uniqueId ?: '--' ?></td>
			<?php
			for($i = 0; $i < 4; ++$i) {
				$item->card($i, $cardId, $enchanted);
				if($enchanted) { ?>
					<td>
						<a href="<?php echo $item->charmap->url(array(
							'path'      => array( 'item' ),
							'action'    => 'view',
							'arguments' => array( $cardId )
						), false) ?>"><img src="<?php echo ac_item_icon($cardId)?>"></a>
					</td>
				<?php } else if($item->slots < ($i + 1)) { ?>
					<td class="ac-card-slot ac-slot-disabled"></td>
				<?php } else if($cardId) { ?>
					<td class="ac-card-slot" ac-ro-card="<?php echo ac_item_cardbmp($cardId)?>">
						<a href="<?php echo $item->charmap->url(array(
							'path'      => array( 'item' ),
							'action'    => 'view',
							'arguments' => array( $cardId )
						), false) ?>"></a>
					</td>
				<?php } else { ?>
					<td class="ac-card-slot ac-slot-empty"></td>
				<?php }} ?>
		</tr>
	<?php endforeach; endif ?>
	</tbody>
	<tfoot>
		<tr><td colspan="11"><?php echo $paginator->render() ?></td></tr>
	</tfoot>
</table>
<span class="ac-search-result"><?php echo __('application', 'search-results-' . ($itemCount === 1 ? 's' : 'p'), number_format($itemCount)) ?></span>
