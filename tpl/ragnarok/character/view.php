<?php
/**
 * @var $char    \Aqua\Ragnarok\Character
 * @var $page    \Page\Main\Ragnarok\Server\Char
 */
$name = htmlspecialchars($char->name);
?>
<table class="ac-table ac-char-table">
	<thead>
	<tr>
		<td colspan="6">
			<?php echo __('ragnarok', 'account-info', htmlspecialchars($char->name))?>
		</td>
	</tr>
	</thead>
	<tbody>
		<tr>
			<td rowspan="5" class="ac-char-body">
				<img src="<?php echo ac_char_body($char)?>">
				<?php if($char->guildId) : ?>
					<div class="ac-char-guild">
						<img src="<?php echo ac_guild_emblem($char->server, $char->charmap, $char->guildId)?>">
						<span><?php echo $char->guildId ? htmlspecialchars($char->guildName) : __('ragnarok', 'no-guild')?></span>
					</div>
				<?php endif; ?>
			</td>
			<td><?php echo __('ragnarok', 'name')?></td>
			<td><?php echo $name?></td>
			<td><?php echo __('ragnarok', 'class')?></td>
			<td><?php echo $char->job()?></td>
		</tr>
		<tr>
			<td><?php echo __('ragnarok', 'base-level')?></td>
			<td><?php echo $char->baseLevel?></td>
			<td><?php echo __('ragnarok', 'job-level')?></td>
			<td><?php echo $char->jobLevel?></td>
		</tr>
		<tr>
			<td><?php echo __('ragnarok', 'base-exp')?></td>
			<td><?php echo number_format($char->baseExp)?></td>
			<td><?php echo __('ragnarok', 'job-exp')?></td>
			<td><?php echo number_format($char->jobExp)?></td>
		</tr>
		<tr>
			<td><?php echo __('ragnarok', 'karma')?></td>
			<td><?php echo number_format($char->karma)?></td>
			<td><?php echo __('ragnarok', 'manner')?></td>
			<td><?php echo number_format($char->manner)?></td>
		</tr>
		<tr>
			<td><?php echo __('ragnarok', 'zeny')?></td>
			<td><?php echo number_format($char->zeny)?><small>z</small></td>
			<td><?php echo __('ragnarok', 'fame')?></td>
			<td><?php echo number_format($char->fame)?></td>
		</tr>
	</tbody>
	<tfoot>
	<tr>
		<td colspan="6"></td>
	</tr>
	</tfoot>
</table>
