<?php
use Aqua\UI\ScriptManager;
use Aqua\Core\App;
/**
 * @var $characters   \Aqua\Ragnarok\Character[]
 * @var $server       \Aqua\Ragnarok\Server
 * @var $charmap      \Aqua\Ragnarok\Server\CharMap
 * @var $page         \Page\Main\Ragnarok\Account
 */
assert('isset($characters) && isset($page) && isset($charmap) && isset($server)');
$multi_srv = ($server->charmapCount > 1 ? 1 : 0);
$page->theme->head->enqueueScript(ScriptManager::script('jquery-ui'));
?>
<form method="POST">
	<input type="hidden" name="selected-server" value="<?php echo $charmap->key?>">
	<table class="ac-table" id="ac-sort-characters">
		<thead>
		<?php if($server->charmapCount > 1) : ?>
			<tr class="ac-script">
				<td colspan="9" style="text-align: right">
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
			<td class="ac-char-slot"><noscript><?php echo __('ragnarok', 'slot')?></noscript></td>
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
				<td colspan="9" style="text-align: center; font-style: italic;"><?php echo __('application', 'no-search-results')?></td>
			</tr>
		<?php else : foreach($characters as $char) : ?>
			<?php $name = htmlspecialchars($char->name); ?>
			<tr class="ac-character">
				<td class="ac-char-status ac-char-<?php echo ($char->online ? 'online' : 'offline')?>"></td>
				<td class="ac-char-slot">
					<input type="hidden" name="<?php echo $char->id?>-slot" value="<?php echo $char->slot?>">
					<noscript>
						<input type="number" name="<?php echo $char->id?>-slot" value="<?php echo $char->slot?>">
					</noscript>
				</td>
				<td class="ac-char-head"><img src="<?php echo ac_char_head($char, true)?>"></td>
				<td class="ac-char-name"><a href="<?php echo $char->url()?>"><?php echo htmlspecialchars($name)?></a></td>
				<td class="ac-char-class"><?php echo $char->job()?></td>
				<td class="ac-char-blvl"><?php echo $char->baseLevel?></td>
				<td class="ac-char-jlvl"><?php echo $char->jobLevel?></td>
				<td class="ac-char-guild-emblem">
					<?php if($char->guildId) : ?>
						<img src="<?php echo ac_guild_emblem(
							$char->charmap->server->key,
							$char->charmap->key,
							$char->guildId
						)?>">
					<?php endif; ?>
				</td>
				<td class="ac-char-guild-name"><?php echo htmlspecialchars($char->guildName)?></td>
				<?php if($multi_srv) : ?>
					<td class="ac-char-server"><?php echo htmlspecialchars($char->charmap->name)?></td>
				<?php endif; ?>
			</tr>
		<?php endforeach; endif; ?>
		</tbody>
		<tfoot>
		<tr>
			<td colspan="9">
				<div class="ac-script" style="float: left; line-height: 2em; font-size: 0.9em">
					<?php echo __('ragnarok', 'drag-drop-sort')?>
				</div>
				<input class="ac-button" type="submit" name="x-change-slot" value="<?php echo __('ragnarok', 'change-slot')?>">
			</td>
		</tr>
		</tfoot>
	</table>
</form>
<?php if (!empty($characters)) : ?>
<script>
	var table = $("#ac-sort-characters");
	$("tbody", table).sortable({
		helper: function(e, ui) {
			$("td", table).each(function() {
				$(this).width($(this).width());
			});
			ui.children().each(function() {
				$(this).width($(this).width());
			});
			return ui;
		},
		start: function() {
			$(this).css("position", "relative");
		},
		stop: function() {
			$(this).css("position", "");
		},
		update: function(event, ui) {
			$("tr.ac-character", this).each(function(i) {
				$(this).find('.ac-char-slot input').attr('value', i + 1);
			});
		},
		snap: true,
		cursor: "move"
	});
</script>
<?php endif; ?>
<span class="ac-search-result"><?php echo __('application', 'search-results-' . (count($characters) === 1 ? 's' : 'p'), number_format(count($characters)))?></span>
