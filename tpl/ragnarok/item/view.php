<?php
use Aqua\Core\App;
/**
 * @var $item \Aqua\Ragnarok\ItemData
 * @var $who_drops array
 * @var $page \Page\Main\Ragnarok\Server\Item
 */
if(!$item) {
	echo '<div class="text-align:center">', __('ragnarok', 'item-not-found'), '</div>';
	return;
}
$item_name = htmlspecialchars($item->jpName);
$is_equip  = $item->isEquipment();
$rowspan = 5;
if($is_equip) $rowspan += 3;
if($item->type === 4) ++$rowspan;
?>
<div style="display:table-cell; vertical-align: top; width: 100%; padding-right: 15px">
	<table class="ac-table">
		<thead>
		<tr>
			<td colspan="5">
				<img src="<?php echo ac_item_icon($item->id)?>" style="float: left">
				<span style="padding-left: 10px; line-height: 24px"><?php echo $item_name?> (#<?php echo $item->id?>)</span>
				<?php if($item->custom) : ?>
					<div style="float:right; font-weight: bold"><? echo __('ragnarok', 'custom-item')?></div>
				<?php endif; ?>
			</td>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td rowspan="<?php echo ($rowspan)?>" class="ac-item-collection"><img src="<?php echo ac_item_collection($item->id)?>"></td>
			<td style="width: 20%"><?php echo __('ragnarok', 'item-id')?></td>
			<td style="width: 30%">#<?php echo $item->id?></td>
			<td style="width: 20%"><?php echo __('ragnarok', 'type')?></td>
			<td style="width: 30%"><?php echo $item->type()?></td>
		</tr>
		<tr>
			<td><?php echo __('ragnarok', 'identifier')?></td>
			<td><?php echo htmlspecialchars($item->enName)?></td>
			<td><?php echo __('ragnarok', 'name')?></td>
			<td><?php echo $item_name?></td>
		</tr>
		<tr>
			<td><?php echo __('ragnarok', 'buy-price')?></td>
			<td><?php echo number_format($item->buyingPrice)?><small>z</small></td>
			<td><?php echo __('ragnarok', 'sell-price')?></td>
			<td><?php echo number_format($item->sellingPrice)?><small>z</small></td>
		</tr>
		<tr>
			<td><?php echo __('ragnarok', 'weight')?></td>
			<td><?php echo $item->weight?></td>
			<td><?php echo __('ragnarok', 'sex')?></td>
			<td><?php echo __('ragnarok-equip-gender', $item->equipGender)?></td>
		</tr>
		<tr>
			<td><?php echo __('ragnarok', 'min-lvl')?></td>
			<td><?php echo number_format($item->equipLevelMin)?></td>
			<td><?php echo __('ragnarok', 'max-lvl')?></td>
			<td><?php echo number_format($item->equipLevelMax)?></td>
		</tr>
		<?php if($is_equip) : ?>
			<tr>
				<td><?php echo __('ragnarok', 'slots')?></td>
				<td><?php echo $item->slots?></td>
				<td><?php echo __('ragnarok', 'refinable')?></td>
				<td><?php echo __('application', ($item->refineable ? 'Yes' : 'No'))?></td>
			</tr>
			<tr>
				<td><?php echo __('ragnarok', 'range')?></td>
				<td><?php echo $item->range?></td>
				<td><?php echo __('ragnarok', 'defence')?></td>
				<td><?php echo number_format($item->defence)?></td>
			</tr>
			<tr>
				<td><?php echo __('ragnarok', 'attack')?></td>
				<td><?php echo number_format($item->attack)?></td>
				<td><?php echo __('ragnarok', 'magic-attack')?></td>
				<td><?php echo number_format($item->mattack)?></td>
			</tr>
			<?php if($item->type === 4) : ?>
				<tr>
					<td><?php echo __('ragnarok', 'weapon-type')?></td>
					<td><?php echo __('ragnarok-weapon-type', $item->look)?></td>
					<td><?php echo __('ragnarok', 'weapon-lvl')?></td>
					<td><?php echo $item->weaponLevel?></td>
				</tr>
			<?php endif; ?>
			<tr>
				<td><?php echo __('ragnarok', 'equip-locations')?></td>
				<td style="text-align: justify" colspan="4"><?php echo $item->location(', ')?></td>
			</tr>
		<?php endif; ?>
		<?php if($item->type !== 3 && $item->type !== 6 && $item->type !== 7 && $item->type !== 8) : ?>
			<tr>
				<td><?php echo __('ragnarok', 'applicable-jobs')?></td>
				<td style="text-align: left" colspan="3"><?php echo $item->jobs(', ')?></td>
				<td style="text-align: justify" >
					<ul class="ac-equip-upper">
						<li class="<?php echo (($item->equipUpper & 0x01) ? 'ac-upper-applicable' : '')?>">
							<?php echo __('ragnarok-equip-upper', 0x01)?>
						</li>
						<li class="<?php echo (($item->equipUpper & 0x02) ? 'ac-upper-applicable' : '')?>">
							<?php echo __('ragnarok-equip-upper', 0x02)?>
						</li>
						<li class="<?php echo (($item->equipUpper & 0x04) ? 'ac-upper-applicable' : '')?>">
							<?php echo __('ragnarok-equip-upper', 0x04)?>
						</li>
						<?php if($page->charmap->getOption('renewal')) : ?>
						<li class="<?php echo (($item->equipUpper & 0x08) ? 'ac-upper-applicable' : '')?>">
							<?php echo __('ragnarok-equip-upper', 0x08)?>
						</li>
						<?php endif; ?>
					</ul>
				</td>
			</tr>
		<?php endif; ?>
		<tr>
			<td colspan="5">
				<?php echo ($item->description ? $item->description : __('ragnarok', 'no-desc'))?>
			</td>
		</tr>
		<?php if(App::settings()->get('ragnarok')->get('display_item_script', false)) : ?>
			<tr>
				<td><?php echo __('ragnarok', 'use-script')?></td>
				<td style="text-align: justify" colspan="4" class="ac-script-code"><pre><code><?php echo $item->scriptUse?></code></pre></td>
			</tr>
			<tr>
				<td><?php echo __('ragnarok', 'equip-script')?></td>
				<td style="text-align: justify" colspan="4" class="ac-script-code"><pre><code><?php echo $item->scriptEquip?></code></pre></td>
			</tr>
			<tr>
				<td><?php echo __('ragnarok', 'unequip-script')?></td>
				<td style="text-align: justify" colspan="4" class="ac-script-code"><pre><code><?php echo $item->scriptUnequip?></code></pre></td>
			</tr>
		<?php endif; ?>
		</tbody>
		<tfoot>
		<tr>
			<td colspan="5">
				<?php if($item->inCashShop): ?>
					<?php if(App::user()->loggedIn()) : ?>
						<a href="<?php echo $page->charmap->url(array(
							'path' => array( 'item' ),
							'action' => 'cart',
							'query' => array(
								'id' => $item->id,
								'x'  => 'add',
								'a'  => 1,
								'r'  => base64_encode(App::request()->uri->url())
							)
						))?>">
							<button class="ac-button"><?php echo __('ragnarok', 'add-to-cart')?> <small>(<?php echo __('donation', 'credit-points', number_format($item->shopPrice))?>)</small></button>
						</a>
					<?php else : ?>
						<button class="ac-button" disabled><?php echo __('ragnarok', 'add-to-cart')?> <small>(<?php echo __('donation', 'credit-points', number_format($item->shopPrice))?>)</small></button>
					<?php endif; ?>
				<?php endif; ?>
			</td>
		</tr>
		</tfoot>
	</table>
</div>
<div style="display: table-cell; vertical-align: top;">
	<table class="ac-table" id="ac-item-whodrops" style="width: 280px">
		<colgroup>
			<col>
			<col style="width: 90px">
			<col style="width: 80px">
		</colgroup>
		<thead>
		<tr>
			<td colspan="3"><?php echo __('ragnarok', 'who-drops', $item_name)?></td>
		</tr>
		<tr class="alt">
			<td><?php echo __('ragnarok', 'monster')?></td>
			<td><?php echo __('ragnarok', 'rate')?></td>
			<td><?php echo __('ragnarok', 'amount')?></td>
		</tr>
		</thead>
		<tbody>
		<?php if(empty($who_drops)) : ?>
			<tr>
				<td colspan="3"  class="ac-table-no-result" style="font-size: .9em">
					<?php echo __('ragnarok', 'not-dropped')?>
				</td>
			</tr>
		<?php else : foreach($who_drops as $drop) : ?>
			<tr>
				<td><a href="<?php echo $drop['mob_url']?>"><?php echo $drop['name']?></a></td>
				<td><?php echo $drop['max_rate']?>%</td>
				<td><?php echo $drop['amount']?></td>
			</tr>
		<?php endforeach; endif; ?>
		</tbody>
		<tfoot>
		<tr>
			<td colspan="3" style="text-align: right"><?php echo __('ragnarok', 'x-monsters-found', count($who_drops))?></td>
		</tr>
		</tfoot>
	</table>
</div>
