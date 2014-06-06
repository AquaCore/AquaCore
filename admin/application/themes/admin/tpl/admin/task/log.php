<?php
/**
 * @var $tasks \Aqua\Log\TaskLog[]
 * @var $taskCount int
 * @var $search \Aqua\UI\Search
 * @var $paginator \Aqua\UI\Pagination
 * @var $page \Page\Admin\Task
 */

use Aqua\Core\App;
use Aqua\UI\ScriptManager;
use Aqua\UI\Sidebar;

$page->theme->template = 'sidebar-right';
$page->theme->footer->enqueueScript(ScriptManager::script('aquacore.build-url'));
$datetimeFormat = App::settings()->get('datetime_format');
$sidebar = new Sidebar;
foreach($search->content as $key => $field) {
	$content = $field->render();
	if($desc = $field->getDescription()) {
		$content.= "<br/><small>$desc</small>";
	}
	$sidebar->append($key, array(array(
		'title' => $field->getLabel(),
		'content' => $content
	)));
}
$sidebar->append('submit', array('class' => 'ac-sidebar-action', array(
	'content' => '<input class="ac-sidebar-submit" type="submit" value="' . __('application', 'search') . '">'
)));
$sidebar->wrapper($search->buildTag());
$page->theme->set('sidebar', $sidebar);
$datetimeFormat = App::settings()->get('datetime_format', '');
?>
<table class="ac-table">
	<thead>
		<tr class="alt">
		<?php echo $search->renderHeader(array(
			'id'    => __('task', 'id'),
			'task'  => __('task', 'task'),
			'ip'    => __('task', 'ip-address'),
			'start' => __('task', 'start-time'),
			'run'   => __('task', 'duration')
		)) ?>
		</tr>
	</thead>
	<tbody>
	<?php if(empty($tasks)) : ?>
		<tr><td colspan="5" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td></tr>
	<?php else : foreach($tasks as $task) : ?>
		<tr>
			<td><a href="<?php echo ac_build_url(array(
					'path' => array( 'task' ),
			        'action' => 'viewlog',
			        'arguments' => array( $task->id )
				)) ?>"><?php echo $task->id ?></a></td>
			<td><?php echo ($task->task() ? htmlspecialchars($task->task()->title) : '--') ?></td>
			<td><?php echo htmlspecialchars($task->ipAddress) ?></td>
			<td><?php echo $task->startDate($datetimeFormat) ?></td>
			<td>
				<?php
				$runTime = explode('.', (string)$task->runTime, 2);
				if(count($runTime) !== 2) {
					$runTime = array( $task->runTime, 0 );
				}
				echo date('H:i:s', strtotime('1999-01-01') + intval($runTime[0])),
					 '<small>.', str_pad(floor(intval($runTime[1]) * 1000000), 6, '0'), '</small>';
				?>
			</td>
		</tr>
	<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
	<tr>
		<td colspan="5">
			<div style="position: relative">
				<div style="position: absolute; right: 0;">
					<?php echo $search->limit()->attr('class', 'ac-search-limit')->render() ?>
				</div>
				<?php echo $paginator->render() ?>
			</div>
		</td>
	</tr>
	</tfoot>
</table>
<span class="ac-search-result"><?php echo __('application', 'search-results-' . ($taskCount === 1 ? 's' : 'p'), number_format($taskCount)) ?></span>
