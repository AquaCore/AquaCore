<?php
use Aqua\UI\ScriptManager;
/**
 * @var $mobs       \Aqua\Ragnarok\Mob[]
 * @var $mob_count  int
 * @var $paginator  \Aqua\UI\Pagination
 * @var $page       \Page\Main\Ragnarok\Server\Item
 */
$page->theme->footer->enqueueScript(ScriptManager::script('cardbmp'));
$base_card_url = $page->server->charMapUri($page->charmap->key())->url(array(
	'path' => array( 'item' ),
	'action' => 'view',
	'arguments' => array( '' )
));
$base_mob_url = $page->server->charMapUri($page->charmap->key())->url(array(
	'path' => array( 'mob' ),
	'action' => 'view',
	'arguments' => array( '' )
));
?>
<table class="ac-table ac-mob-database">
	<thead>
		<tr>
			<td colspan="8">
				<form method="GET" style="float: right">
					<?php echo ac_form_path()?>
					<input type="text" name="id" value="<?php echo $page->request->uri->getString('id')?>" placeholder="<?php echo __('ragnarok', 'mob-id') ?>" size="6">
					<input type="text" name="n" value="<?php echo $page->request->uri->getString('n')?>" placeholder="<?php echo __('ragnarok', 'name') ?>">
					<input type="submit" value="<?php echo __('application', 'search')?>">
				</form>
			</td>
		</tr>
		<tr class="alt">
			<td style="width: 50px;"><?php echo __('ragnarok', 'mob-id')?></td>
			<td><?php echo __('ragnarok', 'name')?></td>
			<td><?php echo __('ragnarok', 'level')?></td>
			<td><?php echo __('ragnarok', 'size')?></td>
			<td><?php echo __('ragnarok', 'race')?></td>
			<td><?php echo __('ragnarok', 'element')?></td>
			<td><?php echo __('ragnarok', 'card')?></td>
			<td style="width: 60px;"><?php echo __('ragnarok', 'custom-mob')?></td>
		</tr>
	</thead>
	<tbody>
<?php if(!$mob_count) : ?>
		<tr>
			<td colspan="8" class="ac-table-no-result"><?php echo __('ragnarok', '0-mobs')?></td>
		</tr>
<?php else : foreach($mobs as $mob) : ?>
		<tr>
			<td><?php echo $mob->id?></td>
			<td><a href="<?php echo $base_mob_url . $mob->id?>"><?php echo htmlspecialchars($mob->iName)?></a></td>
			<td><?php echo $mob->level?></td>
			<td><?php echo $mob->size()?></td>
			<td><?php echo $mob->race()?></td>
			<td><?php echo $mob->element() . ' ' . $mob->elementLevel()?></td>
			<?php if($mob->cardId) { ?>
				<td class="ac-card-slot" ac-ro-card="<?php echo ac_item_cardbmp($mob->cardId)?>">
					<a href="<?php echo $base_card_url . $mob->cardId?>"></a>
				</td>
			<?php } else { ?>
				<td class="ac-card-slot ac-slot-empty"></td>
			<?php } ?>
			<td><?php echo __('application', ($mob->custom ? 'yes' : 'no'))?></td>
		</tr>
<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="8" style="text-align: center">
				<?php echo $paginator->render()?>
			</td>
		</tr>
	</tfoot>
</table>
<span class="ac-search-result"><?php echo __('ragnarok', 'x-monsters-found', number_format($mob_count))?></span>
