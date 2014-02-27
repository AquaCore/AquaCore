<?php
/**
 * @var $guilds \Aqua\Ragnarok\Guild[]
 * @var $page   \Page\Main\Ragnarok\Server\Ranking
 */
?>
<table class="ac-table ac-ranking ac-fame-ranking">
	<thead>
	<tr class="alt">
		<td><?php echo __('ragnarok', 'rank')?></td>
		<td></td>
		<td><?php echo __('ragnarok', 'guild-name')?></td>
		<td><?php echo __('ragnarok', 'leader')?></td>
		<td><?php echo __('ragnarok', 'castles')?></td>
		<td><?php echo __('ragnarok', 'members')?></td>
		<td><?php echo __('ragnarok', 'avg-level')?></td>
		<td><?php echo __('ragnarok', 'level')?></td>
		<td><?php echo __('ragnarok', 'experience')?></td>
	</tr>
	</thead>
	<tbody>
	<?php for($i = 0; $i < 10; ++$i) : ?>
		<tr>
			<td class="ac-rank-<?php echo $i+1?>"><?php echo $i+1?></td>
			<?php if(isset($guilds[$i])) : ?>
				<td><img src="<?php echo $guilds[$i]->emblem()?>"></td>
				<td><?php echo htmlspecialchars($guilds[$i]->name)?></td>
				<td><?php echo htmlspecialchars($guilds[$i]->leaderName)?></td>
				<td><?php echo $guilds[$i]->castleCount?></td>
				<td><?php echo $guilds[$i]->memberCount?></td>
				<td><?php echo $guilds[$i]->averageLevel?></td>
				<td><?php echo $guilds[$i]->level?></td>
				<td><?php echo number_format($guilds[$i]->experience)?></td>
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
