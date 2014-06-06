<?php
/**
 * @var $char \Aqua\Ragnarok\Character
 * @var $page \Page\Admin\Ragnarok\Server
 */

use Aqua\UI\ScriptManager;

$name = htmlspecialchars($char->name);
$page->theme->addSettings('statsSize', 320);
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
					<td colspan="6"></td>
				</tr>
				</thead>
				<tbody>
				<tr>
					<td rowspan="10" class="ac-char-body">
						<div class="ac-char-hp" title="HP: <?php echo number_format($char->hp),
						                                              '/',
						                                              number_format($char->maxHp) ?>">
							<div class="ac-char-fill"
							     style="width: <?php echo $char->hp / ($char->maxHp / 100) ?>%"></div>
						</div>
						<div class="ac-char-sp" title="SP: <?php echo number_format($char->sp),
						                                              '/',
						                                              number_format($char->maxSp) ?>">
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
					<td><b><?php echo __('ragnarok', 'account')?></b></td>
					<td><a href="<?php echo ac_build_url(array(
								'path' => array( 'r', $char->charmap->server->key ),
					            'action' => 'viewaccount',
					            'arguments' => array( $char->accountId )
							)) ?>"><?php echo htmlspecialchars($char->account()->username) ?></a></td>
					<td><b><?php echo __('ragnarok', 'owner')?></b></td>
					<?php if($char->account()->owner) : ?>
						<td><a href="<?php echo ac_build_url(array(
									'path' => array( 'user' ),
						            'action' => 'view',
						            'arguments' => array( $char->account()->owner )
								)) ?>"><?php echo $char->account()->user()->display() ?></a></td>
					<?php else : ?>
						<td>--</td>
					<?php endif;?>
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
					<td><b><?php echo __('ragnarok', 'save-point')?></b></td>
					<td><?php echo ($char->saveMap ? $char->saveMap . ' ' . $char->saveX . ' ' . $char->saveY : '--') ?></td>
					<td><b><?php echo __('ragnarok', 'last-point')?></b></td>
					<td><?php echo ($char->lastMap ? $char->lastMap . ' ' . $char->lastX . ' ' . $char->lastY : '--') ?></td>
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
				<tr>
					<td><b><?php echo __('ragnarok', 'father')?></b></td>
					<?php if($char->fatherId && ($_char = $char->charmap->character($char->fatherId))) : ?>
						<td><a href="<?php echo ac_build_url(array(
									'path' => array( 'r', $char->charmap->server->key, $char->charmap->key ),
						            'action' => 'viewchar',
						            'arguments' => array( $char->fatherId )
								)) ?>"><?php echo $char->charmap->character($char->fatherId) ?></a></td>
					<?php else : ?>
						<td><?php echo $char->fatherId ? __('ragnarok', 'deleted', $char->fatherId) : '--' ?></td>
					<?php endif; ?>
					<td><b><?php echo __('ragnarok', 'mother')?></b></td>
					<?php if($char->motherId && ($_char = $char->charmap->character($char->motherId))) : ?>
						<td><a href="<?php echo ac_build_url(array(
									'path' => array( 'r', $char->charmap->server->key, $char->charmap->key ),
						            'action' => 'viewchar',
						            'arguments' => array( $char->motherId )
								)) ?>"><?php echo $char->charmap->character($char->motherId) ?></a></td>
					<?php else : ?>
						<td><?php echo $char->motherId ? __('ragnarok', 'deleted', $char->motherId) : '--' ?></td>
					<?php endif; ?>
    				</tr>
				<tr>
					<td><b><?php echo __('ragnarok', 'partner')?></b></td>
					<?php if($char->partnerId && ($_char = $char->charmap->character($char->partnerId))) : ?>
						<td><a href="<?php echo ac_build_url(array(
									'path' => array( 'r', $char->charmap->server->key, $char->charmap->key ),
						            'action' => 'viewchar',
						            'arguments' => array( $char->partnerId )
								)) ?>"><?php echo $char->charmap->character($char->partnerId) ?></a></td>
					<?php else : ?>
						<td><?php echo $char->partnerId ? __('ragnarok', 'deleted', $char->partnerId) : '--' ?></td>
					<?php endif; ?>
					<td><b><?php echo __('ragnarok', 'child')?></b></td>
					<?php if($char->childId && ($_char = $char->charmap->character($char->childId))) : ?>
						<td><a href="<?php echo ac_build_url(array(
									'path' => array( 'r', $char->charmap->server->key, $char->charmap->key ),
						            'action' => 'viewchar',
						            'arguments' => array( $char->childId )
								)) ?>"><?php echo $char->charmap->character($char->childId) ?></a></td>
					<?php else : ?>
						<td><?php echo $char->childId ? __('ragnarok', 'deleted', $char->childId) : '--' ?></td>
					<?php endif; ?>
				</tr>
				</tbody>
				<tfoot>
				<tr>
					<td colspan="6">
						<?php if(\Aqua\Core\App::user()->role()->hasPermission('view-user-items')) : ?>
						<a href="<?php echo ac_build_url(array(
							'path' => array( 'r', $char->charmap->server->key, $char->charmap->key ),
						    'action' => 'inventory',
						    'arguments' => array( $char->id )
						))?>"><button type="button"
						              class="ac-button"
								><?php echo __('ragnarok', 'inventory') ?></button></a>
						<a href="<?php echo ac_build_url(array(
							'path' => array( 'r', $char->charmap->server->key, $char->charmap->key ),
						    'action' => 'cart',
						    'arguments' => array( $char->id )
						))?>"><button type="button"
						              class="ac-button"
						              style="margin-right: 10px"
								><?php echo __('ragnarok', 'cart') ?></button></a>
						<?php endif; ?>
					</td>
				</tr>
				</tfoot>
			</table>
		</div>
		<div class="ac-char-stats ac-script" style="width: 300px; padding-top: 10px;">
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
