<?php
/**
 * @var $tokenKey string
 * @var $tasks \Aqua\Schedule\TaskData[]
 * @var $taskCount int
 * @var $paginator \Aqua\UI\Pagination
 * @var $page \Page\Admin\Task
 */

use Aqua\Core\App;
use Aqua\UI\ScriptManager;

$page->theme->footer->enqueueScript(ScriptManager::script('jquery'));
$page->theme->footer->enqueueScript(ScriptManager::script('aquacore.build-url'));
$page->theme->footer->enqueueScript('theme.task')
	->type('text/javascript')
	->src($page->theme->url . '/scripts/task.js');
$datetimeFormat = App::settings()->get('datetime_format', '');
$cronKey = App::settings()->get('cron_key');
?>
<form method="POST">
<input type="hidden" name="runtaskid" value="<?php echo $tokenKey ?>">
<table class="ac-table">
	<colgroup>
		<col style="width: 45px"/>
		<col style="width: 45px"/>
		<col/>
		<col/>
		<col style="width: 35%"/>
		<col/>
		<col style="width: 200px"/>
			<col/>
	</colgroup>
	<thead>
		<tr>
			<td colspan="8">
				<div style="float: right">
					<select name="action">
						<option value="disable"><?php echo __('task', 'disable') ?></option>
						<option value="enable"><?php echo __('task', 'enable') ?></option>
					</select>
					<input type="submit" value="<?php echo __('application', 'apply') ?>" name="x-bulk"/>
				</div>
			</td>
		</tr>
		<tr class="alt">
			<td></td>
			<td><input type="checkbox" ac-checkbox-toggle="task[]"></td>
			<td class="task-key"><?php echo __('task', 'name') ?></td>
			<td><?php echo __('task', 'title') ?></td>
			<td><?php echo __('task', 'description') ?></td>
			<td colspan="2"></td>
			<td><?php echo __('application', 'action') ?></td>
		</tr>
	</thead>
	<tbody>
	<?php foreach($tasks as $task) : ?>
		<tr>
			<td rowspan="2">
				<?php if($task->errorMessage) : ?>
					<img class="ac-tooltip"
					     src="<?php echo \Aqua\URL ?>/assets/images/icons/f_error.png"
					     title="<?php echo htmlspecialchars($task->errorMessage) ?>"/>
				<?php else : ?>
					<img src="<?php echo \Aqua\URL,
										 '/assets/images/icons/circle_',
										 ($task->isEnabled ? 'green' : 'yellow'),
										 '.png' ?>"/>
				<?php endif; ?>
			</td>
			<td rowspan="2"><input type="checkbox" name="task[]" value="<?php echo $task->id ?>"></td>
			<td rowspan="2"><?php echo htmlspecialchars($task->name) ?></td>
			<td rowspan="2"><?php echo htmlspecialchars($task->title) ?></td>
			<td rowspan="2"><?php echo htmlspecialchars($task->description) ?></td>
			<td><?php echo __('task', 'last-run') ?></td>
			<td><?php echo $task->lastRun ? $task->lastRun($datetimeFormat) : '--' ?></td>
			<td class="ac-actions" rowspan="2">
				<a href="<?php echo ac_build_url(array(
						'path' => array( 'task' ),
				        'action' => 'edit',
				        'arguments' => array( $task->id )
					)) ?>">
				<button class="ac-action-edit"
				       type="button">
					<?php echo __('application', 'edit') ?>
				</button>
				</a>
				<button class="ac-action-refresh run-task"
				       type="submit"
				       name="x-run"
 				       value="<?php echo $task->id ?>">
				<?php echo __('task', 'run') ?>
				</button>
			</td>
		</tr>
		<tr>
			<td><?php echo __('task', 'next-run') ?></td>
			<td><?php echo $task->isEnabled && $task->nextRun ? $task->nextRun($datetimeFormat) : '--' ?></td>
		</tr>
	<?php endforeach; ?>
	</tbody>
	<tfoot>
		<tr><td colspan="8"><?php echo $paginator->render() ?></td></tr>
	</tfoot>
</table>
</form>
<div class="cron-key">
	<div class="title"><?php echo __('task', 'cron-key') ?></div>
	<div class="key"><?php echo $cronKey ?></div>
	<a href="<?php echo ac_build_url(array(
			'path' => array( 'task' ),
		    'action' => 'cron'
		)) ?>"><div class="help"></div></a>
	<div style="clear: both"/>
</div>