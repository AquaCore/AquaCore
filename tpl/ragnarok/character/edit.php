<?php
use Aqua\Ragnarok\Character;
/**
 * @var $page    \Page\Main\Ragnarok\Server\Char
 */
?>
<form method="POST">
	<table class="ac-table">
		<thead>
		<tr>
			<td colspan="4"><?php echo __('ragnarok', 'account-info', htmlspecialchars($page->char->name))?></td>
		</tr>
		</thead>
		<tbody>
			<tr>
				<td><label for="reset_look"><?php echo __('ragnarok', 'reset-look')?></label>:</td>
				<td><input type="checkbox" name="reset_look" value="1" id="reset_look"></td>
				<td><label for="reset_position"><?php echo __('ragnarok', 'reset-position')?></label>:</td>
				<td><input type="checkbox" name="reset_position" value="1" id="reset_position"></td>
			</tr>
			<tr>
				<td style="width: 25%"><label for="hide_online"><?php echo __('ragnarok', 'hide-whos-online')?></label>:</td>
				<td style="width: 25%"><input type="checkbox" name="hide_online" value="1" id="hide_online" <?php echo $page->char->options & Character::OPT_DISABLE_WHO_IS_ONLINE ? 'checked="checked"' : ''?>></td>
				<td style="width: 25%"><label for="hide_map"><?php echo __('ragnarok', 'hide-map-whos-online')?></label>:</td>
				<td style="width: 25%"><input type="checkbox" name="hide_map" value="1" id="hide_map" <?php echo $page->char->options & Character::OPT_DISABLE_MAP_WHO_IS_ONLINE ? 'checked="checked"' : ''?>></td>
			</tr>
			<tr>
				<td><label for="hide_zeny"><?php echo __('ragnarok', 'hide-zeny')?></label>:</td>
				<td><input type="checkbox" name="hide_zeny" value="1" id="hide_zeny" <?php echo $page->char->options & Character::OPT_DISABLE_ZENY_LADDER ? 'checked="checked"' : ''?>></td>
				<td></td>
				<td></td>
			</tr>
		</tbody>
		<tfoot>
		<tr>
			<td colspan="4">
				<input type="hidden" name="edit_char" value="1">
				<input type="submit" value="<?php echo __('application', 'submit')?>" class="ac-button">
			</td>
		</tr>
		</tfoot>
	</table>
</form>
