<?php
use Aqua\Core\App;
/**
 * @var $logs      \Aqua\Ragnarok\Server\Logs\LoginLog[]
 * @var $logCount int
 * @var $paginator \Aqua\UI\Pagination
 * @var $search    \Aqua\UI\Search
 * @var $page      \Page\Admin\Ragnarok
 */

use Aqua\UI\Sidebar;
use Aqua\UI\ScriptManager;

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
?>
<table class="ac-table">
	<thead>
		<tr class="alt">
			<?php echo $search->renderHeader(array(
				'usr'  => __('ragnarok-login-log', 'username'),
				'ip'   => __('ragnarok-login-log', 'ip-address'),
				'code' => __('ragnarok-login-log', 'response'),
				'status' => __('ragnarok-login-log', 'status'),
				'msg'  => __('ragnarok-login-log', 'message'),
				'date' => __('ragnarok-login-log', 'date'),
			)) ?>
		</tr>
	</thead>
	<tbody>
	<?php if(empty($logs)) : ?>
		<tr><td colspan="6" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td></tr>
	<?php else : foreach($logs as $log) : ?>
	<tr>
		<td><?php echo htmlspecialchars($log->username) ?></td>
		<td><?php echo htmlspecialchars($log->ipAddress) ?></td>
		<td><?php echo htmlspecialchars($log->code) ?></td>
		<td class="ac-login-status ac-login-<?php echo ($log->code === 100 ? 'ok' : 'fail') ?>"><?php echo $log->response() ?></td>
		<td><?php echo htmlspecialchars($log->message) ?></td>
		<td><?php echo $log->date($datetimeFormat) ?></td>
	</tr>
	<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="6">
				<div style="position: relative">
					<div style="position: absolute; right: 0;">
						<form method="GET">
						<?php echo $search->limit()->attr('class', 'ac-search-limit')->render() ?>
						</form>
					</div>
					<?php echo $paginator->render() ?>
				</div>
			</td>
		</tr>
	</tfoot>
</table>
<span class="ac-search-result"><?php echo __('application', 'search-results-' . ($logCount === 1 ? 's' : 'p'), number_format($logCount)) ?></span>
