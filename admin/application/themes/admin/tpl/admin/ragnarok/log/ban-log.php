<?php
use Aqua\Core\App;
use Aqua\Ragnarok\Server\Logs\BanLog;
/**
 * @var $logs      \Aqua\Ragnarok\Server\Logs\BanLog[]
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
?><table class="ac-table">
	<thead>
		<tr class="alt">
			<?php echo $search->renderHeader(array(
					'id'    => __('ragnarok-ban-log', 'id'),
					'acc'   => __('ragnarok-ban-log', 'account'),
					'ban'   => __('ragnarok-ban-log', 'banned-by'),
					'type'  => __('ragnarok-ban-log', 'type'),
					'date'  => __('ragnarok-ban-log', 'date'),
					'unban' => __('ragnarok-ban-log', 'unban-date'),
				)) ?>
		</tr>
	</thead>
	<tbody>
	<?php if(empty($logs)) : ?>
		<tr><td colspan="6" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td></tr>
	<?php else : foreach($logs as $log) : ?>
	<tr>
		<td><?php echo $log->id ?></td>
		<td><?php echo htmlspecialchars($log->banned()->username) ?></td>
		<td><?php echo $log->account()->display() ?></td>
		<td class="ac-ban-type <?php if($log->type === BanLog::TYPE_UNBAN) echo 'ac-unban' ?>"><?php echo $log->type() ?></td>
		<td><?php echo $log->banDate($datetimeFormat) ?></td>
		<td><?php echo $log->unbanDate($datetimeFormat) ?></td>
	</tr>
	<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="6">
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
<span class="ac-search-result"><?php echo __('application', 'search-results-' . ($logCount === 1 ? 's' : 'p'), number_format($logCount)) ?></span>
