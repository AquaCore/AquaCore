<?php
/**
 * @var $log       \Aqua\Ragnarok\Server\Logs\ChatLog[]
 * @var $count     int
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
	<?php if(empty($log)) : ?>
		<tr><td colspan="10" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td></tr>
	<?php else : foreach($log as $chat) : ?>
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
	<tr><td colspan="10"><?php echo $paginator->render() ?></td></tr>
	</tfoot>
</table>
<span class="ac-search-result"><?php echo __('application',
                                             'search-results-' . ($count === 1 ? 's' : 'p'),
                                             number_format($count)) ?></span>
