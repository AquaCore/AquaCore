<?php
use Aqua\Ragnarok\Character;
use Aqua\UI\ScriptManager;
use Aqua\Ragnarok\MapMarker;
/**
 * @var $characters   \Aqua\Ragnarok\Character[]
 * @var $online_chars int
 * @var $paginator    \Aqua\UI\Pagination
 * @var $page         \Page\Main\Ragnarok\Server
 */
$page->theme->footer->enqueueScript('whos-online-map')
	->type('text/javascript')
	->append('
$(".ac-whos-online-map").hover(function() {
	$(this).find(".ac-map").stop(false, true).show("drop", {direction: "down", distance: "30px"}, 300);
}, function() {
	$(this).find(".ac-map").stop(false, true).hide("drop", {direction: "up", distance: "30px"}, 200);
});
');
?>
<table class="ac-table" id="ac-whos-online">
	<thead>
		<tr>
			<td colspan="6">
				<?php echo __('ragnarok', 'x-online', number_format($page->charmap->status('characters_online')))?>
				<form method="GET" style="float: right">
				<?php echo ac_form_path()?>
				<input
					type="text"
					name="c"
					placeholder="<?php echo __('ragnarok', 'name')?>"
					value="<?php echo $page->request->uri->getString('c')?>">
				<input
					type="text"
					name="m"
					placeholder="<?php echo __('ragnarok', 'map-eg')?>"
					value="<?php echo $page->request->uri->getString('m')?>">
				<input type="submit" value="<?php echo __('application', 'search')?>">
				</form>
			</td>
		</tr>
		<tr class="alt">
			<td></td>
			<td><?php echo __('ragnarok', 'name')?></td>
			<td><?php echo __('ragnarok', 'last-map')?></td>
			<td><?php echo __('ragnarok', 'x-coord')?></td>
			<td><?php echo __('ragnarok', 'y-coord')?></td>
			<td style="width: 40px"></td>
		</tr>
	</thead>
	<tbody>
<?php if(empty($characters)) : ?>
	<tr>
		<td colspan="6" class="ac-table-no-result"><?php echo __('ragnarok', '0-chars-found')?></td>
	</tr>
<?php else: foreach($characters as $char) : ?>
	<?php
	$hide_pos = $char->options & Character::OPT_DISABLE_MAP_WHO_IS_ONLINE;
	?>
	<tr class="ac-whois-online-char <?php echo $hide_pos ? 'ac-whois-online-position-hidden' : ''?>">
		<td style="vertical-align: middle; text-align: center; width: 70px;"><img src="<?php echo ac_char_head($char)?>"></td>
		<td style="vertical-align: middle"><?php echo htmlspecialchars($char->name)?></td>
		<td style="vertical-align: middle">
			<?php
			if($hide_pos) {
				echo __('ragnarok', 'Hidden');
			} else if($map_name = __('ragnarok-map_name', preg_replace('/^[0-9]+@/', '@', $char->lastMap))) {
				echo $map_name, '<br><small><i>(', htmlspecialchars($char->lastMap), ')</i></small>';
			} else {
				echo htmlspecialchars($char->lastMap);
			}
			?>
		</td>
		<td style="vertical-align: middle">
			<?php echo $hide_pos ? __('ragnarok', 'hidden') : $char->lastX?>
		</td>
		<td style="vertical-align: middle">
			<?php echo $hide_pos ? __('ragnarok', 'hidden') : $char->lastY?>
		</td>
		<td>
			<?php if(!$hide_pos && MapMarker::hasMiniMap($char->lastMap)) : ?>
			<div class="ac-script ac-whos-online-map">
			<?php
				$marker = new MapMarker($char->lastMap);
				$marker->mark($char->lastX, $char->lastY);
				echo $marker->render();
			?>
			</div>
			<?php endif; ?>
		</td>
	</tr>
<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="6" style="text-align: center">
				<?php echo $paginator->render()?>
			</td>
		</tr>
	</tfoot>
</table>
<span class="ac-search-result"><?php echo __('ragnarok', 'x-chars-found', number_format($online_chars))?></span>
