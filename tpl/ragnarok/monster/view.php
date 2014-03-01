<?php
use Aqua\UI\ScriptManager;
/**
 * @var $mob    \Aqua\Ragnarok\Mob
 * @var $drops  array
 * @var $page   \Page\Main\Ragnarok\Server\Mob
 */
$mob_name = htmlspecialchars($mob->iName);
$all_drops = array();
if(isset($drops['card'])) {
	$all_drops = array_merge($all_drops, array($drops['card']));
}
if(isset($drops['normal'])) {
	$all_drops = array_merge($all_drops, $drops['normal']);
}
if(isset($drops['mvp'])) {
	$all_drops = array_merge($all_drops, $drops['mvp']);
}
?>
<div style="display:table-cell; vertical-align: top; width: 100%; padding-right: 15px">
	<table class="ac-table">
		<thead>
		<tr>
			<td colspan="5">
				<?php echo $mob_name?> (#<?php echo $mob->id?>)
			</td>
		</tr>
		</thead>
		<tbody>
			<tr>
				<td rowspan="10" class="ac-mob-sprite">
					<img src="<?php echo ac_mob_sprite($mob->id)?>">
				</td>
				<td><?php echo __('ragnarok', 'mob-id')?></td>
				<td><?php echo $mob->id?></td>
				<td><?php echo __('ragnarok', 'custom-mob')?></td>
				<td><?php echo __('application', ($mob->custom ? 'Yes' : 'No'))?></td>
			</tr>
			<tr>
				<td><?php echo __('ragnarok', 'kro-name')?></td>
				<td><?php echo htmlspecialchars($mob->kName)?></td>
				<td><?php echo __('ragnarok', 'iro-name')?></td>
				<td><?php echo $mob_name?></td>
			</tr>
			<tr>
				<td><?php echo __('ragnarok', 'hp')?></td>
				<td><?php echo number_format($mob->hp)?></td>
				<td><?php echo __('ragnarok', 'sp')?></td>
				<td><?php echo number_format($mob->sp)?></td>
			</tr>
			<tr>
				<td><?php echo __('ragnarok', 'level')?></td>
				<td><?php echo $mob->level?></td>
				<td><?php echo __('ragnarok', 'size')?></td>
				<td><?php echo $mob->size()?></td>
			</tr>
			<tr>
				<td><?php echo __('ragnarok', 'race')?></td>
				<td><?php echo $mob->race()?></td>
				<td><?php echo __('ragnarok', 'element')?></td>
				<td><?php echo $mob->element() . ' ' . $mob->elementLevel()?></td>
			</tr>
			<tr>
				<td><?php echo __('ragnarok', 'attack-range')?></td>
				<td><?php echo __('ragnarok', 'cells', $mob->attackRange)?></td>
				<td><?php echo __('ragnarok', 'spell-range')?></td>
				<td><?php echo __('ragnarok', 'cells', $mob->skillRange)?></td>
			</tr>
			<tr>
				<td><?php echo __('ragnarok', 'sight-range')?></td>
				<td><?php echo __('ragnarok', 'cells', $mob->sight)?></td>
				<td><?php echo __('ragnarok', 'attack-delay')?></td>
				<td><?php echo number_format($mob->aDelay)?> <small>ms</small></td>
			</tr>
			<tr>
				<td><?php echo __('ragnarok', 'attack-motion')?></td>
				<td><?php echo number_format($mob->aMotion)?> <small>ms</small></td>
				<td><?php echo __('ragnarok', 'delay-motion')?></td>
				<td><?php echo number_format($mob->dMotion)?> <small>ms</small></td>
			</tr>
			<tr>
				<td><?php echo __('ragnarok', 'min-attack')?></td>
				<td><?php echo number_format($mob->minAttack)?></td>
				<td><?php echo __('ragnarok', 'max-attack')?></td>
				<td><?php echo number_format($mob->maxAttack)?></td>
			</tr>
			<tr>
				<td><?php echo __('ragnarok', 'defence')?></td>
				<td><?php echo number_format($mob->defence)?></td>
				<td><?php echo __('ragnarok', 'magic-defence')?></td>
				<td><?php echo number_format($mob->mDefence)?></td>
			</tr>
			<tr>
				<td rowspan="<?php echo 6 + ($page->charmap->getOption('renewal') ? 1 : 0) + ($mob->mvpExp ? 1 : 0)?>" style="text-align: justify">
					<ul class="ac-mob-mode">
						<li class="<?php echo (($mob->mode & 0x0001) ? 'ac-mode-applicable' : '')?>">
							<?php echo __('ragnarok-mob-mode', 0x0001)?>
						</li>
						<li class="<?php echo (($mob->mode & 0x0002) ? 'ac-mode-applicable' : '')?>">
							<?php echo __('ragnarok-mob-mode', 0x0002)?>
						</li>
						<li class="<?php echo (($mob->mode & 0x0004) ? 'ac-mode-applicable' : '')?>">
							<?php echo __('ragnarok-mob-mode', 0x0004)?>
						</li>
						<li class="<?php echo (($mob->mode & 0x0008) ? 'ac-mode-applicable' : '')?>">
							<?php echo __('ragnarok-mob-mode', 0x0008)?>
						</li>
						<li class="<?php echo (($mob->mode & 0x0010) ? 'ac-mode-applicable' : '')?>">
							<?php echo __('ragnarok-mob-mode', 0x0010)?>
						</li>
						<li class="<?php echo (($mob->mode & 0x0020) ? 'ac-mode-applicable' : '')?>">
							<?php echo __('ragnarok-mob-mode', 0x0020)?>
						</li>
						<li class="<?php echo (($mob->mode & 0x0040) ? 'ac-mode-applicable' : '')?>">
							<?php echo __('ragnarok-mob-mode', 0x0040)?>
						</li>
					</ul>
					<ul class="ac-mob-mode">
						<li class="<?php echo (($mob->mode & 0x0080) ? 'ac-mode-applicable' : '')?>">
							<?php echo __('ragnarok-mob-mode', 0x0080)?>
						</li>
						<li class="<?php echo (($mob->mode & 0x0100) ? 'ac-mode-applicable' : '')?>">
							<?php echo __('ragnarok-mob-mode', 0x0100)?>
						</li>
						<li class="<?php echo (($mob->mode & 0x0200) ? 'ac-mode-applicable' : '')?>">
							<?php echo __('ragnarok-mob-mode', 0x0200)?>
						</li>
						<li class="<?php echo (($mob->mode & 0x0400) ? 'ac-mode-applicable' : '')?>">
							<?php echo __('ragnarok-mob-mode', 0x0400)?>
						</li>
						<li class="<?php echo (($mob->mode & 0x1000) ? 'ac-mode-applicable' : '')?>">
							<?php echo __('ragnarok-mob-mode', 0x1000)?>
						</li>
						<li class="<?php echo (($mob->mode & 0x2000) ? 'ac-mode-applicable' : '')?>">
							<?php echo __('ragnarok-mob-mode', 0x2000)?>
						</li>
						<li class="<?php echo (($mob->mode & 0x4000) ? 'ac-mode-applicable' : '')?>">
							<?php echo __('ragnarok-mob-mode', 0x4000)?>
						</li>
					</ul>
				</td>
				<td><?php echo __('ragnarok', 'strength')?></td>
				<td><?php echo number_format($mob->strength)?></td>
				<td><?php echo __('ragnarok', 'agility')?></td>
				<td><?php echo number_format($mob->agility)?></td>
			</tr>
			<tr>
				<td><?php echo __('ragnarok', 'vitality')?></td>
				<td><?php echo number_format($mob->vitality)?></td>
				<td><?php echo __('ragnarok', 'intelligence')?></td>
				<td><?php echo number_format($mob->intelligence)?></td>
			</tr>
			<tr>
				<td><?php echo __('ragnarok', 'dexterity')?></td>
				<td><?php echo number_format($mob->dexterity)?></td>
				<td><?php echo __('ragnarok', 'luck')?></td>
				<td><?php echo number_format($mob->luck)?></td>
			</tr>
			<tr>
				<td colspan="2"><?php echo __('ragnarok', 'base-exp')?></td>
				<td colspan="2" ac-original-exp="<?php echo $mob->baseExp?>"><?php echo number_format($mob->baseExp)?></td>
			</tr>
			<tr>
				<td colspan="2"><?php echo __('ragnarok', 'job-exp')?></td>
				<td colspan="2" ac-original-exp="<?php echo $mob->jobExp?>"><?php echo number_format($mob->jobExp)?></td>
			</tr>
			<?php if($mob->mvpExp) : ?>
			<tr>
				<td colspan="2"><?php echo __('ragnarok', 'mvp-exp')?></td>
				<td colspan="2"><?php echo number_format($mob->mvpExp)?></td>
			</tr>
			<?php endif; ?>
			<?php if($page->charmap->getOption('renewal')) : ?>
			<tr>
				<?php
				$page->theme->footer->enqueueScript(ScriptManager::script('aquacore.experience-slider'));
				$page->theme->footer->enqueueScript('renewal-exp-slider.init')
					->append("
new AquaCore.ExperienceSlider(jQuery(\".ac-renewal-exp-wrapper\").get(0), {
	level: {$mob->level},
	experience: {$mob->baseExp}
});
");
				?>
				<td colspan="4">
					<div class="ac-renewal-exp-wrapper"></div>
				</td>
			</tr>
			<?php endif; ?>
		</tbody>
		<tfoot>
		<tr>
			<td colspan="5"></td>
		</tr>
		</tfoot>
	</table>
</div>
<div style="display: table-cell; vertical-align: top;">
	<table class="ac-table" id="ac-item-whodrops" style="width: 250px;">
		<colgroup>
			<col style="width: 45px">
			<col>
			<col style="width: 70px">
		</colgroup>
		<thead>
		<tr>
			<td colspan="3"><?php echo __('ragnarok', 'x-drops', $mob_name)?></td>
		</tr>
		<tr class="alt">
			<td></td>
			<td><?php echo __('ragnarok', 'item')?></td>
			<td><?php echo __('ragnarok', 'rate')?></td>
		</tr>
		</thead>
		<tbody>
		<?php if(empty($all_drops)) : ?>
			<tr>
				<td colspan="3"  class="ac-table-no-result">
					<?php echo __('ragnarok', 'no-drops')?>
				</td>
			</tr>
		<?php else : foreach($all_drops as $drop) : ?>
			<tr>
				<?php if($drop['type'] === 6) : ?>
				<td ac-ro-card="<?php echo ac_item_cardbmp($drop['id'])?>"><img src="<?php echo ac_item_icon($drop['id'])?>"></td>
				<?php else : ?>
				<td><img src="<?php echo ac_item_icon($drop['id'])?>"></td>
				<?php endif; ?>
				<td><a href="<?php echo $drop['url']?>"><?php echo htmlspecialchars($drop['name'])?></a></td>
				<td><?php echo $drop['rate']?>%</td>
			</tr>
		<?php endforeach; endif; ?>
		</tbody>
		<tfoot>
		<tr>
			<td style="text-align: right" colspan="3"><?php echo __('ragnarok', 'x-items', count($all_drops))?></td>
		</tr>
		</tfoot>
	</table>
</div>
