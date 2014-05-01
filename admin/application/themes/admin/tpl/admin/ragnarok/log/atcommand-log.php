<?php
/**
 * @var $logs      \Aqua\Ragnarok\Server\Logs\AtcommandLog[]
 * @var $logCount  int
 * @var $paginator \Aqua\UI\Pagination
 * @var $search    \Aqua\UI\Search
 * @var $page      \Page\Admin\Ragnarok\Server
 */

use Aqua\Core\App;
use Aqua\UI\Sidebar;

$page->theme->template = 'sidebar-right';
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
				'id'    => __('ragnarok', 'id'),
				'date'  => __('ragnarok', 'date'),
				'map'   => __('ragnarok', 'map'),
				'acc'   => __('ragnarok', 'account'),
				'char'  => __('ragnarok', 'character-id'),
				'name'  => __('ragnarok', 'character'),
				'cmd'   => __('ragnarok', 'command'),
				'param' => __('ragnarok', 'parameters'),
			)) ?>
	</tr>
	</thead>
	<tbody>
	<?php if(empty($logs)) : ?>
		<tr><td colspan="8" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td></tr>
	<?php else : foreach($logs as $atcmd) : ?>
		<tr>
			<td><?php echo $atcmd->id ?></td>
			<td><?php echo $atcmd->date($datetimeFormat) ?></td>
			<td><?php echo htmlspecialchars($atcmd->map) ?: '--' ?></td>
			<td><?php echo htmlspecialchars($atcmd->account()->username) ?></td>
			<td><?php echo $atcmd->charId ?: '--' ?></td>
			<td><?php echo htmlspecialchars($atcmd->charName) ?: '--' ?></td>
			<td><?php echo htmlspecialchars($atcmd->command()) ?></td>
			<td><?php echo htmlspecialchars($atcmd->parameters()) ?: '--' ?></td>
		</tr>
	<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
	<tr><td colspan="8"><?php echo $paginator->render() ?></td></tr>
	</tfoot>
</table>
<span class="ac-search-result"><?php echo __('application',
                                             'search-results-' . ($logCount === 1 ? 's' : 'p'),
                                             number_format($logCount)) ?></span>
