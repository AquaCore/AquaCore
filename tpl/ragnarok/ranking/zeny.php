<?php
/**
 * @var $count      int
 * @var $characters \Aqua\Ragnarok\Character[]
 * @var $page       \Page\Main\Ragnarok\Server\Ladder
 */
?>
<table class="ac-table ac-ranking ac-fame-ranking">
	<colgroup>
		<col>
		<col>
		<col>
		<col>
		<col>
		<col style="text-align: center; width: 30px">
		<col>
	</colgroup>
	<thead>
	<tr class="alt">
		<td><?php echo __('ragnarok', 'rank')?></td>
		<td></td>
		<td><?php echo __('ragnarok', 'name')?></td>
		<td><?php echo __('ragnarok', 'class')?></td>
		<td><?php echo __('ragnarok', 'zeny')?></td>
		<td colspan="2"><?php echo __('ragnarok', 'guild')?></td>
	</tr>
	</thead>
	<tbody>
	<?php for($i = 0; $i < $count; ++$i) : ?>
		<tr>
			<td class="ac-rank-<?php echo $i+1?>"><?php echo $i+1?></td>
			<?php if(isset($characters[$i])) : ?>
				<td><img src="<?php echo ac_char_head($characters[$i])?>"></td>
				<td><?php echo htmlspecialchars($characters[$i]->name)?></td>
				<td><?php echo $characters[$i]->job()?></td>
				<td><?php echo number_format($characters[$i]->zeny)?></td>
				<?php if($characters[$i]->guildId) : ?>
				<td>
					<img src="<?php echo ac_guild_emblem(
						$characters[$i]->charmap->server->key,
						$characters[$i]->charmap->key,
						$characters[$i]->guildId
					)?>">
				</td>
				<td><?php echo htmlspecialchars($characters[$i]->guildName)?></td>
				<?php else : ?>
				<td colspan="2"></td>
				<?php endif; ?>
			<?php else : ?>
				<td colspan="8"></td>
			<?php endif ?>
		</tr>
	<?php endfor; ?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="7"></td>
		</tr>
	</tfoot>
</table>
