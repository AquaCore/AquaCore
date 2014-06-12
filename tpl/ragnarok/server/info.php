<?php
use Aqua\UI\ScriptManager;
/**
 * @var $accounts              int
 * @var $guilds                int
 * @var $parties               int
 * @var $characters            int
 * @var $homunculus            int
 * @var $class_population      array
 * @var $homunculus_population array
 * @var $online                int
 * @var $page               \Page\Main\Ragnarok\Server
 */

$class_population = array_replace(array_fill_keys(array_keys(\Aqua\Core\L10n::getNamespace('ragnarok-jobs')), 0), $class_population);
$mkJobList = function($jobs) use ($class_population) {
	$html = '<ul class="ac-job-population">';
	foreach($jobs as $name => $job) {
		$count = 0;
		if(is_array($job)) {
			foreach($job as $id) {
				$count += $class_population[$id];
			}
		} else {
			$count = $class_population[$job];
		}
		$html.= "<li><div class=\"ac-job-population-name\">{$name}</div><div class=\"ac-job-population-count\">{$count}</div><div style=\"clear:both\"></div></li>";
	}
	$html.= '</ul>';
	return $html;
};
$colspan = ($page->charmap->getOption('renewal') ? 3 : 2);
$status = array(
	'login' => $page->charmap->server->login->serverStatus(),
    'char'  => false,
    'map'   => false
);
$page->charmap->serverStatus($status['char'], $status['map']);
?>
<table class="ac-table">
	<?php if($page->charmap->getOption('renewal')) : ?>
	<colgroup>
		<col style="width: 20%;">
		<col style="width: 20%;">
		<col style="width: 20%;">
		<col style="width: 20%;">
		<col style="width: 20%;">
	</colgroup>
	<?php else : ?>
	<colgroup>
		<col style="width: 25%;">
		<col style="width: 25%;">
		<col style="width: 25%;">
		<col style="width: 25%;">
	</colgroup>
	<?php endif; ?>
	<thead>
		<tr>
			<td colspan="<?php echo $colspan + 2 ?>">
				<div class="ac-server-status-wrapper">
					<div class="ac-server-status <?php echo $status['map'] ? 'on' : 'off' ?>"></div>
						<?php echo __('ragnarok', 'map-server') ?>
				</div>
				<div class="ac-server-status-wrapper">
					<div class="ac-server-status <?php echo $status['char'] ? 'on' : 'off' ?>"></div>
					<?php echo __('ragnarok', 'char-server') ?>
				</div>
				<div class="ac-server-status-wrapper">
					<div class="ac-server-status <?php echo $status['login'] ? 'on' : 'off' ?>"></div>
					<?php echo __('ragnarok', 'login-server') ?>
				</div>
			</td>
		</tr>
	</thead>
	<tbody>
	<?php if((int)$page->charmap->getOption('online-stats')) :
		$stats = array();
		$i = -1;
		foreach($page->charmap->onlineStats(
			'peak',
			strtotime('sunday 1 week ago'),
			strtotime('saturday this week 23:59:59'),
			'day',
			300
		) as $timestamp => $count) {
			$week = date('w', $timestamp);
			if((int)$week == 0) {
				++$i;
			}
			$stats[$i][$week] = $count;
		}
		$stats = array_values($stats);
		for($j = 0; $j < 7; ++$j) {
			if(!isset($stats[$i][$j])) {
				$stats[$i][$j] = null;
			}
		}
		$page->theme
			->addWordGroup('week')
			->addSettings('onlineStats', array_reverse($stats))
			->addWordGroup('application', array( 'this-week', 'weeks-ago-s', 'weeks-ago-p' ))
			->addWordGroup('ragnarok', array( 'online-stats' ));
		$page->theme->footer->enqueueScript(ScriptManager::script('highsoft.highchart'));
		$page->theme->footer->enqueueScript(ScriptManager::script('jquery-ui'));
		$page->theme->footer->enqueueScript('tpl.server-info')
			->type('text/javascript')
			->src(ac_build_url(array(
				'base_dir' => \Aqua\DIR . '/tpl/scripts',
			    'script'   => 'server-info.js'
			)));
		?>
		<tr>
			<td colspan="<?php echo $colspan + 2 ?>">
				<div id="online-stats"></div>
			</td>
		</tr>
		<tr class="ac-table-header">
			<td colspan="<?php echo $colspan + 2 ?>" style="text-align: right">
				<?php echo __('ragnarok',
				              'player-peak-all',
				              number_format($page->charmap->onlineStats('peak'))); ?>
				|
				<?php echo __('ragnarok',
				              'player-peak-this-month',
				              number_format($page->charmap->onlineStats('peak', strtotime('first day of this month midnight')))); ?>
			</td>
		</tr>
	<?php endif; ?>
		<tr>
			<td colspan="<?php echo $colspan ?>"><?php echo __('ragnarok', 'accounts-registered')?></td>
			<td colspan="2"><?php echo number_format($accounts)?></td>
		</tr>
		<tr>
			<td colspan="<?php echo $colspan ?>"><?php echo __('ragnarok', 'chars-registered')?></td>
			<td colspan="2"><?php echo number_format($characters)?></td>
		</tr>
		<tr>
			<td colspan="<?php echo $colspan ?>"><?php echo __('ragnarok', 'guilds')?></td>
			<td colspan="2"><?php echo number_format($guilds)?></td>
		</tr>
		<tr>
			<td colspan="<?php echo $colspan ?>"><?php echo __('ragnarok', 'parties')?></td>
			<td colspan="2"><?php echo number_format($parties)?></td>
		</tr>
		<tr class="ac-table-header alt"><td colspan="5"><?php echo __('ragnarok', 'class-population') ?></td></tr>
		<tr class="ac-table-header">
	<?php if($page->charmap->getOption('renewal')) : ?>
			<td><?php echo __('ragnarok', 'class-1st')?></td>
			<td><?php echo __('ragnarok', 'class-2nd')?></td>
			<td><?php echo __('ragnarok', 'class-trans')?></td>
			<td><?php echo __('ragnarok', 'class-3rd')?></td>
			<td><?php echo __('ragnarok', 'class-other')?></td>
	<?php else : ?>
			<td><?php echo __('ragnarok', 'class-1st')?></td>
			<td><?php echo __('ragnarok', 'class-2nd')?></td>
			<td><?php echo __('ragnarok', 'class-trans')?></td>
			<td colspan="2"><?php echo __('ragnarok', 'class-other')?></td>
	<?php endif; ?>
		</tr>
		<tr class="ac-class-population">
			<td>
				<?php echo $mkJobList(array(
					                      __('ragnarok-jobs', 1) => array(1, 4002),
					                      __('ragnarok-jobs', 2) => array(2, 4003),
					                      __('ragnarok-jobs', 3) => array(3, 4004),
					                      __('ragnarok-jobs', 4) => array(4, 4005),
					                      __('ragnarok-jobs', 5) => array(5, 4006),
					                      __('ragnarok-jobs', 6) => array(6, 4007)
				                      ))?>
			</td>
			<td>
				<?php echo $mkJobList(array(
					                      __('ragnarok-jobs', 7) => array(7, 4030),
					                      __('ragnarok-jobs', 8) => array(8, 4031),
					                      __('ragnarok-jobs', 9) => array(9, 4032),
					                      __('ragnarok-jobs', 10) => array(10, 4033),
					                      __('ragnarok-jobs', 11) => array(11, 4034),
					                      __('ragnarok-jobs', 12) => array(12, 4035),
					                      __('ragnarok-jobs', 14) => array(14, 4037),
					                      __('ragnarok-jobs', 15) => array(15, 4038),
					                      __('ragnarok-jobs', 16) => array(16, 4039),
					                      __('ragnarok-jobs', 17) => array(17, 4040),
					                      __('ragnarok-jobs', 18) => array(18, 4041),
					                      __('ragnarok-jobs', 19) . '/' . __('ragnarok-jobs', 20) => array(19, 20, 4042, 4043)
				                      ))?>
			</td>
			<td>
				<?php echo $mkJobList(array(
					                      __('ragnarok-jobs', 4008) => 4008,
					                      __('ragnarok-jobs', 4009) => 4009,
					                      __('ragnarok-jobs', 4010) => 4010,
					                      __('ragnarok-jobs', 4011) => 4011,
					                      __('ragnarok-jobs', 4012) => 4012,
					                      __('ragnarok-jobs', 4013) => 4013,
					                      __('ragnarok-jobs', 4015) => 4015,
					                      __('ragnarok-jobs', 4016) => 4016,
					                      __('ragnarok-jobs', 4017) => 4017,
					                      __('ragnarok-jobs', 4018) => 4018,
					                      __('ragnarok-jobs', 4019) => 4019,
					                      __('ragnarok-jobs', 4020) . '/' . __('ragnarok-jobs', 4021) => array(4020, 4021)
				                      ))?>
			</td>
			<?php if($page->charmap->getOption('renewal')) : ?>
				<td>
					<?php echo $mkJobList(array(
						                      __('ragnarok-jobs', 4054) => array(4054, 4060, 4096),
						                      __('ragnarok-jobs', 4055) => array(4055, 4061, 4097),
						                      __('ragnarok-jobs', 4056) => array(4056, 4062, 4098),
						                      __('ragnarok-jobs', 4057) => array(4057, 4063, 4099),
						                      __('ragnarok-jobs', 4058) => array(4058, 4064, 4100),
						                      __('ragnarok-jobs', 4059) => array(4059, 4065, 4101),
						                      __('ragnarok-jobs', 4066) => array(4066, 4073, 4102),
						                      __('ragnarok-jobs', 4067) => array(4067, 4074, 4103),
						                      __('ragnarok-jobs', 4070) => array(4070, 4077, 4106),
						                      __('ragnarok-jobs', 4071) => array(4071, 4078, 4107),
						                      __('ragnarok-jobs', 4072) => array(4072, 4079, 4108),
						                      __('ragnarok-jobs', 4068) . '/' . __('ragnarok-jobs', 4069) => array(4068, 4069, 4075, 4076, 4104, 4105)

					                      ))?>
				</td>
			<?php endif; ?>
			<td>
				<?php echo $mkJobList(array(
					                      __('ragnarok-jobs', 0) => array(0, 4023),
					                      __('ragnarok-jobs', 23) => array(23, 4045),
					                      __('ragnarok-jobs', 24) => 24,
					                      __('ragnarok-jobs', 25) => 25,
					                      __('ragnarok-jobs', 4046) => 4046,
					                      __('ragnarok-jobs', 4047) => 4047,
					                      __('ragnarok-jobs', 4049) => 4049,
				                      ))?>
			</td>
		</tr>
	</tbody>
	<tfoot><tr><td colspan="<?php echo $colspan + 2 ?>"></td></tr></tfoot>
</table>
