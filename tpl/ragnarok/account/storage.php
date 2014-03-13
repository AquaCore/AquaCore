<?php
use Aqua\Core\App;
/**
 * @var $storage_size int
 * @var $storage      \Aqua\Ragnarok\Item[]
 * @var $server       \Aqua\Ragnarok\Server
 * @var $charmap      \Aqua\Ragnarok\Server\CharMap
 * @var $paginator    \Aqua\UI\Pagination
 * @var $page         \Page\Main\Ragnarok\Account
 */

$page->theme->footer->enqueueScript('cardbmp')
	->type('text/javascript')
	->src(ac_build_url(array(
		'base_dir' => \Aqua\DIR . '/tpl/scripts',
		'script'   => 'cardbmp.js'
	)));

$search_type = $page->request->uri->getString('t');
$base_url = $charmap->url(array(
	'path' => array( 'item' ),
	'action' => 'view',
	'arguments' => array( '' )
));
?>
<table class="ac-table">
	<thead>
		<tr>
			<td colspan="7">
				<form method="GET">
				<?php echo ac_form_path()?>
				<div class="ac-storage-types" style="float: left; line-height: 30px">
					<input id="ac_storage-use" type="radio" name="t" value="use" <?php echo ($search_type == 'use' ? 'checked' : '')?>>
					<label for="ac_storage-use"><?php echo __('ragnarok-item-type', '2')?></label>
					<input id="ac_storage-misc" type="radio" name="t" value="misc" <?php echo ($search_type == 'misc' ? 'checked' : '')?>>
					<label for="ac_storage-misc"><?php echo __('ragnarok-item-type', '3')?></label>
					<input id="ac_storage-weapon" type="radio" name="t" value="weapon" <?php echo ($search_type == 'weapon' ? 'checked' : '')?>>
					<label for="ac_storage-weapon"><?php echo __('ragnarok-item-type', '4')?></label>
					<input id="ac_storage-armor" type="radio" name="t" value="armor" <?php echo ($search_type == 'armor' ? 'checked' : '')?>>
					<label for="ac_storage-armor"><?php echo __('ragnarok-item-type', '5')?></label>
					<input id="ac_storage-egg" type="radio" name="t" value="egg" <?php echo ($search_type == 'egg' ? 'checked' : '')?>>
					<label for="ac_storage-egg"><?php echo __('ragnarok-item-type', '7')?></label>
					<input id="ac_storage-card" type="radio" name="t" value="card" <?php echo ($search_type == 'card' ? 'checked' : '')?>>
					<label for="ac_storage-card"><?php echo __('ragnarok-item-type', '6')?></label>
					<input id="ac_storage-ammo" type="radio" name="t" value="ammo" <?php echo ($search_type == 'ammo' ? 'checked' : '')?>>
					<label for="ac_storage-ammo"><?php echo __('ragnarok-item-type', '10')?></label>
					<input id="ac_storage-all" type="radio" name="t" value="" <?php echo (empty($search_type) ? 'checked' : '')?>>
					<label for="ac_storage-all"><?php echo __('application', 'all')?></label>
				</div>
				<div style="float:right">
					<?php if($server->charmapCount > 1) : ?>
						<select onchange="document.location.href = this.options[this.selectedIndex].value;" class="ac-script">
							<?php $x_base_url = App::request()->uri->url(array( 'arguments' => array( '' ) )); ?>
							<?php foreach($server->charmap as &$cm) : ?>
								<option value="<?php echo $x_base_url . $cm->key()?>" <?php echo ($charmap->key() === $cm->key() ? 'selected' : '')?>><?php echo $cm->name?></option>
							<?php endforeach; ?>
						</select>
					<?php endif; ?>
					<input type="text" name="s" value="<?php echo htmlspecialchars($page->request->uri->getString('s'))?>">
					<input type="submit" value="<?php echo __('application', 'search')?>">
				</div>
				</form>
			</td>
		</tr>
		<tr class="alt">
			<td></td>
			<td><?php echo __('ragnarok', 'name')?></td>
			<td><?php echo __('ragnarok', 'amount')?></td>
			<td colspan="4"><?php echo __('ragnarok', 'cards')?></td>
		</tr>
	</thead>
	<tbody>
<?php if(empty($storage)) : ?>
	<tr>
		<td colspan="7" style="text-align: center; font-style: italic;"><?php echo __('application', 'no-search-results')?></td>
	</tr>
<?php else : foreach($storage as $item) : ?>
	<tr>
<?php if($item->identified) : ?>
		<td class="ac-item-icon"><img src="<?php echo ac_item_icon($item->itemId)?>"></td>
		<td class="ac-item-name"><a href="<?php echo $base_url . $item->itemId?>"><?php echo $item->name(false)?></a></td>
		<td class="ac-item-amount"><?php echo number_format($item->amount)?></td>
		<?php
		for($i = 0; $i < 4; ++$i) {
			$item->card($i, $card_id, $enchanted);
			if($enchanted) { ?>
		<td class="ac-card-slot ac-slot-enchanted">
			<a href="<?php echo $base_url . $card_id ?>"><img src="<?php echo ac_item_icon($card_id)?>"></a>
		</td>
		<?php } else if($item->slots < ($i + 1)) { ?>
		<td class="ac-card-slot ac-slot-disabled"></td>
		<?php } else if($card_id) { ?>
		<td class="ac-card-slot" ac-ro-card="<?php echo ac_item_cardbmp($card_id)?>" title="">
			<a href="<?php echo $base_url . $card_id?>"></a>
		</td>
		<?php } else { ?>
		<td class="ac-card-slot ac-slot-empty"></td>
		<?php }} ?>
<?php else : ?>
		<td class="ac-item-icon ac-item-unidentified">
			<img src="<?php echo \Aqua\URL?>/assets/images/icons/unidentified.png">
		</td>
		<td class="ac-item-name"><i><?php echo __('ragnarok', 'unidentified')?></i></td>
		<td class="ac-item-amount"><?php echo number_format($item->amount)?></td>
		<td class="ac-card-slot ac-slot-disabled"></td>
		<td class="ac-card-slot ac-slot-disabled"></td>
		<td class="ac-card-slot ac-slot-disabled"></td>
		<td class="ac-card-slot ac-slot-disabled"></td>
<?php endif; ?>
	</tr>
<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="7" style="text-align: center">
				<?php echo $paginator->render()?>
			</td>
		</tr>
	</tfoot>
</table>
<span class="ac-search-result"><?php echo __('application', 'search-results-' . ($storage_size === 1 ? 's' : 'p'), number_format($storage_size))?></span>
