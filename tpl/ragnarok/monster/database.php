<?php
/**
 * @var $mobs       \Aqua\Ragnarok\Mob[]
 * @var $mob_count  int
 * @var $paginator  \Aqua\UI\Pagination
 * @var $page       \Page\Main\Ragnarok\Server\Item
 */
$page->theme->footer->enqueueScript('cardbmp')
	->type('text/javascript')
	->append('
(function($) {
	$("[ac-ro-card]").tooltip({
		tooltipClass: "ac-card-bmp",
		position: {
			my: "center+5 bottom-7",
			at: "center-5 top"
		},
		hide: null,
		show: null,
		content: function() {
			return $("<span/>")
				.append($("<div/>").addClass("ac-tooltip-top"))
				.append($("<div/>").width(150).addClass("ac-tooltip-content").append($("<img/>").attr("src", $(this).attr("ac-ro-card"))))
				.append($("<div/>").addClass("ac-tooltip-bottom"));
		}
	});
})(jQuery);
');
$base_card_url = $page->charmap->url(array(
	'path' => array( 'item' ),
	'action' => 'view',
	'arguments' => array( '' )
));
$base_mob_url = $page->charmap->url(array(
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
					<input type="text" name="id" value="<?php echo htmlspecialchars($page->request->uri->getString('id'))?>" placeholder="<?php echo __('ragnarok', 'mob-id') ?>" size="8">
					<input type="text" name="n" value="<?php echo htmlspecialchars($page->request->uri->getString('n'))?>" placeholder="<?php echo __('ragnarok', 'name') ?>">
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
<?php if(empty($mobs)) : ?>
		<tr>
			<td colspan="8" class="ac-table-no-result"><?php echo __('application', 'no-search-results')?></td>
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
				<td class="ac-card-slot" ac-ro-card="<?php echo ac_item_cardbmp($mob->cardId)?>" title="">
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
<span class="ac-search-result"><?php echo __('application', 'search-results-' . ($mob_count === 1 ? 's' : 'p'), number_format($mob_count))?></span>
