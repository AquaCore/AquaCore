<?php
use Aqua\UI\ScriptManager;
/**
 * @var $char    \Aqua\Ragnarok\Character
 * @var $page    \Page\Main\Ragnarok\Server\Char
 */
$name = htmlspecialchars($char->name);
$page->theme->addSettings('charStats', array(
	'title' => __('ragnarok', 'stats-info', htmlspecialchars($char->name)),
	'str' => $char->strength,
    'vit' => $char->vitality,
    'dex' => $char->dexterity,
    'agi' => $char->agility,
    'int' => $char->intelligence,
    'luk' => $char->luck,
));
$page->theme->addWordGroup('ragnarok-stats');
$page->theme->footer->enqueueScript(ScriptManager::script('jquery'));
$page->theme->footer->enqueueScript(ScriptManager::script('highsoft.highchart-more'));
$page->theme->footer->enqueueScript('tpl.char-stats')
	->type('text/javascript')
	->src(ac_build_url(array(
		'base_dir' => \Aqua\DIR . '/tpl/scripts',
	    'script' => 'char-stats.js'
	)));
?>
<div class="ac-view-char"><div class="wrapper">
	<div class="ac-char-info" style="width: auto">
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
					<td rowspan="6" class="ac-char-body">
						<div class="ac-char-hp">
							<div class="ac-char-fill"
							     style="width: <?php echo $char->hp / ($char->maxHp / 100) ?>%"></div>
						</div>
						<div class="ac-char-sp">
							<div class="ac-char-fill"
							     style="width: <?php echo $char->sp / ($char->maxSp / 100) ?>%"></div>
						</div>
						<img src="<?php echo ac_char_body($char)?>">
						<?php if($char->guildId) : ?>
							<div class="ac-char-guild">
								<img src="<?php echo ac_guild_emblem(
									$char->charmap->server->key,
									$char->charmap->key,
									$char->guildId
								)?>">
								<span><?php echo htmlspecialchars($char->guildName)?></span>
							</div>
						<?php endif; ?>
					</td>
					<td><b><?php echo __('ragnarok', 'name')?></b></td>
					<td><?php echo $name?></td>
					<td><b><?php echo __('ragnarok', 'class')?></b></td>
					<td><?php echo $char->job()?></td>
				</tr>
				<tr>
					<td><b><?php echo __('ragnarok', 'base-level')?></b></td>
					<td><?php echo $char->baseLevel?></td>
					<td><b><?php echo __('ragnarok', 'job-level')?></b></td>
					<td><?php echo $char->jobLevel?></td>
				</tr>
				<tr>
					<td><b><?php echo __('ragnarok', 'base-exp')?></b></td>
					<td><?php echo number_format($char->baseExp)?></td>
					<td><b><?php echo __('ragnarok', 'job-exp')?></b></td>
					<td><?php echo number_format($char->jobExp)?></td>
				</tr>
				<tr>
					<td><b><?php echo __('ragnarok', 'max-hp')?></b></td>
					<td><?php echo number_format($char->maxHp)?></td>
					<td><b><?php echo __('ragnarok', 'max-sp')?></b></td>
					<td><?php echo number_format($char->maxSp)?></td>
				</tr>
				<tr>
					<td><b><?php echo __('ragnarok', 'karma')?></b></td>
					<td><?php echo number_format($char->karma)?></td>
					<td><b><?php echo __('ragnarok', 'manner')?></b></td>
					<td><?php echo number_format($char->manner)?></td>
				</tr>
				<tr>
					<td><b><?php echo __('ragnarok', 'zeny')?></b></td>
					<td><?php echo number_format($char->zeny)?><small>z</small></td>
					<td><b><?php echo __('ragnarok', 'fame')?></b></td>
					<td><?php echo number_format($char->fame)?></td>
				</tr>
			</tbody>
			<tfoot>
			<tr>
				<td colspan="6"></td>
			</tr>
			</tfoot>
		</table>
	</div>
	<div class="ac-char-stats ac-script" style="width: 250px">
		<div id="char-stats"></div>
	</div>
	<noscript>
	<div class="ac-char-stats">
		<table class="ac-table">
			<thead><tr><td colspan="2"></td></tr></thead>
			<tbody>
				<tr>
					<td><b><?php echo __('ragnarok-stats', 'str') ?></b></td>
					<td><?php echo number_format($char->strength) ?></td>
				</tr>
				<tr>
					<td><b><?php echo __('ragnarok-stats', 'vit') ?></b></td>
					<td><?php echo number_format($char->vitality) ?></td>
				</tr>
				<tr>
					<td><b><?php echo __('ragnarok-stats', 'agi') ?></b></td>
					<td><?php echo number_format($char->agility) ?></td>
				</tr>
				<tr>
					<td><b><?php echo __('ragnarok-stats', 'dex') ?></b></td>
					<td><?php echo number_format($char->dexterity) ?></td>
				</tr>
				<tr>
					<td><b><?php echo __('ragnarok-stats', 'int') ?></b></td>
					<td><?php echo number_format($char->intelligence) ?></td>
				</tr>
				<tr>
					<td><b><?php echo __('ragnarok-stats', 'luk') ?></b></td>
					<td><?php echo number_format($char->luck) ?></td>
				</tr>
			</tbody>
			<tfoot>
				<tr>
					<td colspan="2" style="text-align: right">
						<?php echo __('ragnarok', 'status-points', number_format($char->statusPoints))?>
					</td>
				</tr>
			</tfoot>
		</table>
	</div>
	</noscript>
</div></div>
