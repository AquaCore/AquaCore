<?php
/**
 * @var $log \Aqua\Log\TaskLog
 * @var $page \Page\Admin\Task
 */

use Aqua\Core\App;

$dateTimeFormat = App::settings()->get('datetime_format', '');
?>
<table class="ac-table" style="table-layout: fixed">
	<thead>
		<tr><td colspan="4"></td></tr>
	</thead>
	<tbody>
		<tr>
			<td><b><?php echo __('task', 'id') ?></b></td>
			<td><?php echo $log->id ?></td>
			<td><b><?php echo __('task', 'task') ?></b></td>
			<td><?php echo ($log->task() ? htmlspecialchars($log->task()->title) : '--') ?></td>
		</tr>
		<tr>
			<td><b><?php echo __('task', 'start-time') ?></b></td>
			<td><?php echo $log->startDate($dateTimeFormat) ?></td>
			<td><b><?php echo __('task', 'end-time') ?></b></td>
			<td><?php echo $log->endDate($dateTimeFormat) ?></td>
		</tr>
		<tr>
			<td><b><?php echo __('task', 'ip-address') ?></b></td>
			<td><?php echo htmlspecialchars($log->ipAddress) ?></td>
			<td><b><?php echo __('task', 'duration') ?></b></td>
			<td>
				<?php
				$runTime = explode('.', (string)$log->runTime, 2);
				if(count($runTime) !== 2) {
					$runTime = array( $log->runTime, 0 );
				}
				echo date('H:i:s', strtotime('1999-01-01') + intval($runTime[0])),
				'<small>.', str_pad(floor(intval($runTime[1]) * 1000000), 6, '0'), '</small>';
				?>
			</td>
		</tr>
		<?php if($log->outputShort || $log->outputFull) : ?>
		<tr class="ac-table-header"><td colspan="4"><?php echo __('task', 'output') ?></td></tr>
		<tr>
			<td colspan="4">
				<div class="task-output">
				<?php if($log->outputShort) : ?><pre><?php echo $log->outputShort ?></pre><?php endif;?>
				<?php if($log->outputFull && $log->outputShort) : ?><hr/><?php endif;?>
				<?php if($log->outputFull) : ?><pre><?php echo $log->outputFull ?></pre><?php endif;?>
				</div>
			</td>
		</tr>
		<?php endif; ?>
	</tbody>
	<tfoot><tr><td colspan="4"></td></tr></tfoot>
</table>