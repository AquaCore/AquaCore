<?php
/**
 * @var $count      int
 * @var $homunculus \Aqua\Ragnarok\Homunculus[]
 * @var $page       \Page\Main\Ragnarok\Server\Ladder
 */
?>
<table class="ac-table ac-ranking ac-fame-ranking">
	<thead>
	<tr class="alt">
		<td><?php echo __('ragnarok', 'rank')?></td>
		<td></td>
		<td><?php echo __('ragnarok', 'name')?></td>
		<td><?php echo __('ragnarok', 'owner')?></td>
		<td><?php echo __('ragnarok', 'class')?></td>
		<td><?php echo __('ragnarok', 'level')?></td>
		<td><?php echo __('ragnarok', 'experience')?></td>
	</tr>
	</thead>
	<tbody>
	<?php for($i = 0; $i < $count; ++$i) : ?>
		<tr>
			<td class="ac-rank-<?php echo $i+1?>"><?php echo $i+1?></td>
			<?php if(isset($homunculus[$i])) : ?>
				<td><img src="<?php echo \Aqua\URL . '/assets/images/homunculus/' . $homunculus[$i]->class . '.gif' ?>"></td>
				<td><?php echo htmlspecialchars($homunculus[$i]->name ?: $homunculus[$i]->className())?></td>
				<td><?php echo htmlspecialchars($homunculus[$i]->ownerName)?></td>
				<td><?php echo $homunculus[$i]->className()?></td>
				<td><?php echo $homunculus[$i]->level?></td>
				<td><?php echo number_format($homunculus[$i]->experience)?></td>
			<?php else : ?>
				<td colspan="6"></td>
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
