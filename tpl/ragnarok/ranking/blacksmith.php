<?php
/**
 * @var $characters \Aqua\Ragnarok\Character[]
 * @var $page       \Page\Main\Ragnarok\Server\Ranking
 */
?>
<table class="ac-table ac-ranking ac-fame-ranking">
	<thead>
	<tr class="alt">
		<td><?php echo __('ragnarok', 'rank')?></td>
		<td></td>
		<td><?php echo __('ragnarok', 'name')?></td>
		<td><?php echo __('ragnarok', 'class')?></td>
		<td><?php echo __('ragnarok', 'base-level')?></td>
		<td><?php echo __('ragnarok', 'job-level')?></td>
		<td><?php echo __('ragnarok', 'fame')?></td>
		<td colspan="2"><?php echo __('ragnarok', 'guild')?></td>
	</tr>
	</thead>
	<tbody>
	<?php for($i = 0; $i < 10; ++$i) : ?>
		<tr>
			<td class="ac-rank-<?php echo $i+1?>"><?php echo $i+1?></td>
			<?php if(isset($characters[$i])) : ?>
				<td><img src="<?php echo ac_char_head($characters[$i])?>"></td>
				<td><?php echo htmlspecialchars($characters[$i]->name)?></td>
				<td><?php echo $characters[$i]->job()?></td>
				<td><?php echo $characters[$i]->baseLevel?></td>
				<td><?php echo $characters[$i]->jobLevel?></td>
				<td><?php echo number_format($characters[$i]->fame)?></td>
				<td style="text-align: center; width: 30px">
					<img src="<?php echo ac_guild_emblem($characters[$i]->server, $characters[$i]->charmap, $characters[$i]->guildId)?>">
				</td>
				<td><?php echo htmlspecialchars($characters[$i]->guildName)?></td>
			<?php else : ?>
				<td colspan="8"></td>
			<?php endif ?>
		</tr>
	<?php endfor; ?>
	</tbody>
	<tfoot>
	<tr>
		<td colspan="9"></td>
	</tr>
	</tfoot>
</table>
