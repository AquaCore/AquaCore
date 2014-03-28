<?php
/**
 * @var $castles array
 * @var $guilds  \Aqua\Ragnarok\Guild[]
 * @var $page    \Page\Main\Ragnarok\Server
 */
?>
<table class="ac-table">
	<colgroup>
		<col style="width: 80px">
		<col>
		<col style="width: 40px">
		<col>
	</colgroup>
	<thead>
		<tr class="alt">
			<td><?php echo __('ragnarok', 'castle-id') ?></td>
			<td><?php echo __('ragnarok', 'castle-name') ?></td>
			<td colspan="2"><?php echo __('ragnarok', 'guild') ?></td>
		</tr>
	</thead>
	<tbody>
	<?php foreach($castles as $id => $name) : ?>
		<tr>
			<td><?php echo $id ?></td>
			<td><?php echo htmlspecialchars($name) ?></td>
			<?php if(isset($guilds[$id])) : ?>
				<td><img src="<?php echo $guilds[$id]->emblem() ?>"></td>
				<td><?php echo htmlspecialchars($guilds[$id]->name) ?></td>
			<?php else : ?>
				<td colspan="2"></td>
			<?php endif; ?>
		</tr>
	<?php endforeach; ?>
	</tbody>
	<tfoot>
		<tr><td colspan="4"></td></tr>
	</tfoot>
</table>
