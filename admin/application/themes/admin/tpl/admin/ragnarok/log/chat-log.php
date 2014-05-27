<?php
/**
 * @var $logs      \Aqua\Ragnarok\Server\Logs\ChatLog[]
 * @var $logCount  int
 * @var $paginator \Aqua\UI\Pagination
 * @var $search    \Aqua\UI\Search
 * @var $page      \Page\Admin\Ragnarok\Server
 */

use Aqua\Core\App;
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
				'id'   => __('ragnarok', 'id'),
				'date' => __('ragnarok', 'date'),
				'type' => __('ragnarok', 'type'),
				'tid'  => __('ragnarok', 'type-id'),
				'acc'  => __('ragnarok', 'account'),
				'char' => __('ragnarok', 'character'),
				'dst'  => __('ragnarok', 'receiver'),
				'map'  => __('ragnarok', 'map'),
				'x'    => __('ragnarok', 'x-coord'),
				'y'    => __('ragnarok', 'y-coord'),
			)) ?>
	</tr>
	</thead>
	<tbody>
	<?php if(empty($logs)) : ?>
		<tr><td colspan="10" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td></tr>
	<?php else : foreach($logs as $chat) : ?>
		<tr>
			<td><?php echo $chat->id ?></td>
			<td><?php echo $chat->date($datetimeFormat) ?></td>
			<td><?php echo $chat->type() ?></td>
			<td><?php echo $chat->typeId ?: '--' ?></td>
			<td><a href="<?php echo $chat->charmap->server->url(array(
			        'action' => 'viewaccount',
			        'arguments' => array( $chat->srcAccountId )
				)) ?>"><?php echo htmlspecialchars($chat->account()->username) ?></a></td>
			<?php if($char = $chat->source()) : ?>
				<td><a href="<?php echo $char->charmap->url(array(
					'action' => 'viewchar',
				    'arguments' => array( $char->id )
				)) ?>"><?php echo htmlspecialchars($char->name) ?></a></td>
			<?php else : ?>
				<td><?php echo __('ragnarok', 'deleted', $chat->srcCharId) ?></td>
			<?php endif ?>
			<td><?php echo htmlspecialchars($chat->dstName) ?: '--' ?></td>
			<td><?php echo htmlspecialchars($chat->map) ?: '--' ?></td>
			<td><?php echo $chat->x ?: '--' ?></td>
			<td><?php echo $chat->y ?: '--' ?></td>
		</tr>
		<tr>
			<td colspan="10"><?php echo htmlspecialchars($chat->message) ?></td>
		</tr>
	<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
	<tr>
		<td colspan="10">
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
<span class="ac-search-result"><?php echo __('application',
                                             'search-results-' . ($logCount === 1 ? 's' : 'p'),
                                             number_format($logCount)) ?></span>
