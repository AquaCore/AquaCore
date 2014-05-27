<?php
/**
 * @var $guild \Aqua\Ragnarok\Guild
 * @var $page  \Page\Admin\Ragnarok\Server
 * @var $skills array
 * @var $alliances array
 */
?>
<table class="ac-table" style="table-layout: fixed">
	<colgroup>
		<col>
		<col style="width: 45px;">
		<col>
		<col>
		<col>
		<col>
		<col>
	</colgroup>
	<thead>
	<tr>
		<td colspan="7">
			<img style="height: 20px;width: 20px;margin-right: 10px" src="<?php echo $guild->emblem()?>">
			<span style="line-height: 20px;"><?php echo htmlspecialchars($guild->name) ?></span>
		</td>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td colspan="2"><b><?php echo __('ragnarok', 'id') ?></b></td>
		<td><?php echo $guild->id ?></td>
		<td><b><?php echo __('ragnarok', 'guild-name') ?></b></td>
		<td><?php echo htmlspecialchars($guild->name) ?></td>
		<td><b><?php echo __('ragnarok', 'leader') ?></b></td>
		<td><?php echo htmlspecialchars($guild->leaderName) ?></td>
	</tr>
	<tr>
		<td colspan="2"><b><?php echo __('ragnarok', 'online') ?></b></td>
		<td><?php echo number_format($guild->online) ?></td>
		<td><b><?php echo __('ragnarok', 'members') ?></b></td>
		<td><?php echo number_format($guild->memberCount) ?></td>
		<td><b><?php echo __('ragnarok', 'max-members') ?></b></td>
		<td><?php echo number_format($guild->memberLimit) ?></td>
	</tr>
	<tr>
		<td colspan="2"><b><?php echo __('ragnarok', 'level') ?></b></td>
		<td><?php echo number_format($guild->level) ?></td>
		<td><b><?php echo __('ragnarok', 'experience') ?></b></td>
		<td><?php echo number_format($guild->experience) ?></td>
		<td><b><?php echo __('ragnarok', 'next-exp') ?></b></td>
		<td><?php echo number_format($guild->nextExperience) ?></td>
	</tr>
	<tr>
		<td colspan="2"><b><?php echo __('ragnarok', 'avg-level') ?></b></td>
		<td><?php echo number_format($guild->averageLevel) ?></td>
		<td><b><?php echo __('ragnarok', 'skill-points') ?></b></td>
		<td><?php echo number_format($guild->skillPoints) ?></td>
		<td><b><?php echo __('ragnarok', 'castles') ?></b></td>
		<td><?php echo number_format($guild->castleCount) ?></td>
	</tr>
	<tr class="ac-table-header alt"><td colspan="7"><?php echo __('ragnarok', 'skills') ?></td></tr>
	<tr class="ac-table-header">
		<td><?php echo __('ragnarok', 'id') ?></td>
		<td></td>
		<td colspan="3"><?php echo __('ragnarok', 'name') ?></td>
		<td colspan="2"><?php echo __('ragnarok', 'level') ?></td>
	</tr>
	<?php if(empty($skills)) : ?>
		<tr><td colspan="7" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td></tr>
	<?php else : foreach($skills as $skillId => $skillLv) : ?>
	<tr>
		<td>#<?php echo $skillId ?></td>
		<td><img src="<?php echo ac_skill_icon($skillId) ?>"></td>
		<td colspan="3"><?php echo __('ragnarok-skill-name', $skillId) ?></td>
		<td colspan="2"><?php echo $skillLv ?></td>
	</tr>
	<?php endforeach; endif; ?>
	<tr class="ac-table-header alt"><td colspan="7"><?php echo __('ragnarok', 'alliances') ?></td></tr>
	<tr class="ac-table-header">
		<td><?php echo __('ragnarok', 'type') ?></td>
		<td></td>
		<td colspan="5"><?php echo __('ragnarok', 'name') ?></td>
	</tr>
	<?php if(empty($alliances)) : ?>
		<tr><td colspan="7" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td></tr>
	<?php else : foreach($alliances as $guildId => $alliance) : ?>
		<tr>
			<td><?php echo __('ragnarok-opposition', $alliance['opposition']) ?></td>
			<td><img src="<?php echo ac_guild_emblem($guild->charmap->server->key,
			                                         $guild->charmap->key,
			                                         $guildId) ?>"></td>
			<td colspan="5"><?php echo htmlspecialchars($alliance['name']) ?></td>
		</tr>
	<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
	<tr>
		<td colspan="7">
			<a href="<?php echo $guild->charmap->url(array(
				'action' => 'gstorage',
			    'arguments' => array( $guild->id )
			))?>"><button class="ac-button"><?php echo __('ragnarok', 'storage') ?></button></a>
			<a href="<?php echo $guild->charmap->url(array(
				'action' => 'gmembers',
			    'arguments' => array( $guild->id )
			))?>"><button class="ac-button"><?php echo __('ragnarok', 'members') ?></button></a>
		</td>
	</tr>
	</tfoot>
</table>
