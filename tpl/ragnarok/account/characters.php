<?php
use Aqua\UI\ScriptManager;
use Aqua\Core\App;
/**
 * @var $characters   \Aqua\Ragnarok\Character[]
 * @var $server       \Aqua\Ragnarok\Server
 * @var $charmap      \Aqua\Ragnarok\Server\CharMap
 * @var $page         \Page\Main\Ragnarok\Account
 */

$page->theme->head->enqueueScript(ScriptManager::script('jquery-ui'));
?>
<form method="POST">
	<input type="hidden" name="selected-server" value="<?php echo $charmap->key?>">
	<table class="ac-table" id="sort-characters" style="table-layout: fixed">
		<colgroup>
			<col style="width: 25px">
			<col style="width: 75px">
			<col>
			<col>
			<col>
			<col>
			<col style="width: 40px">
			<col>
		</colgroup>
		<thead>
		<?php if($server->charmapCount > 1) : ?>
			<tr class="ac-script">
				<td colspan="8" style="text-align: right">
					<select onchange="document.location.href = this.options[this.selectedIndex].value;" class="ac-script">
						<?php $x_base_url = App::request()->uri->url(array( 'arguments' => array( '' ) )); ?>
						<?php foreach($server->charmap as &$cm) : ?>
							<option value="<?php echo $x_base_url . $cm->key?>" <?php echo ($charmap->key === $cm->key ? 'selected' : '')?>><?php echo $cm->name?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
		<?php endif; ?>
		<tr class="alt">
			<td></td>
			<td></td>
			<td><?php echo __('ragnarok', 'name')?></td>
			<td><?php echo __('ragnarok', 'class')?></td>
			<td><?php echo __('ragnarok', 'base-level')?></td>
			<td><?php echo __('ragnarok', 'job-level')?></td>
			<td colspan="2"><?php echo __('ragnarok', 'guild')?></td>
		</tr>
		</thead>
		<tbody>
		<?php if(empty($characters)) : ?>
			<tr>
				<td colspan="8" class="ac-table-no-result"><?php echo __('application', 'no-search-results')?></td>
			</tr>
		<?php else : foreach($characters as $char) : ?>
			<?php $name = htmlspecialchars($char->name); ?>
			<tr class="ac-character">
				<td class="ac-char-status ac-char-<?php echo ($char->online ? 'online' : 'offline')?>">
					<input type="hidden" class="slot" name="slots[]" value="<?php echo $char->id?>">
				</td>
				<td><img src="<?php echo ac_char_head($char, true)?>"></td>
				<td><a href="<?php echo $char->url()?>"><?php echo htmlspecialchars($name)?></a></td>
				<td><?php echo $char->job()?></td>
				<td><?php echo $char->baseLevel?></td>
				<td><?php echo $char->jobLevel?></td>
				<?php if($char->guildId) : ?>
					<td>
						<img src="<?php echo ac_guild_emblem(
							$char->charmap->server->key,
							$char->charmap->key,
							$char->guildId
						)?>">
					</td>
					<td><?php echo htmlspecialchars($char->guildName)?></td>
				<?php else : ?>
					<td colspan="2"></td>
				<?php endif; ?>
			</tr>
		<?php endforeach; endif; ?>
		</tbody>
		<tfoot>
			<tr>
				<td colspan="8">
					<div class="ac-script" style="float: left; line-height: 2em; font-size: 0.9em">
						<?php echo __('ragnarok', 'drag-drop-sort')?>
					</div>
					<input class="ac-button" type="submit" name="x-change-slot" value="<?php echo __('ragnarok', 'change-slot')?>">
				</td>
			</tr>
		</tfoot>
	</table>
</form>
<span class="ac-search-result"><?php echo __('application',
                                             'search-results-' . (count($characters) === 1 ? 's' : 'p'),
                                             number_format(count($characters)))?></span>
<?php if (!empty($characters)) : ?>
<script>
(function($) {
	$.widget("aquacore.sortableEx", $.ui.sortable, {
		refreshContainment: function() {
			this._setContainment();
		}
	});
	$("#sort-characters tbody").sortableEx({
		helper: function(e, ui) {
			var element = ui.clone(),
				children = ui.children();
			element.children().each(function(i) {
				$(this).width(children.eq(i).width());
			});
			return element;
		},
		start: function(e, ui) {
			ui.placeholder.height(ui.item.height());
			$(this).sortableEx("refreshContainment");
		},
		containment: "parent",
		tolerance: "pointer",
		forceHelperSize: true,
		forcePlaceholderSize: true,
		snap: false,
		axis: "y",
		cursor: "move"
	});
})(jQuery);
</script>
<?php endif; ?>
