<?php
/**
 * @var $log       \Aqua\Ragnarok\Server\Logs\ZenyLog[]
 * @var $count     int
 * @var $paginator \Aqua\UI\Pagination
 * @var $search    \Aqua\UI\Search
 * @var $page      \Page\Admin\Ragnarok\Server
 */

use Aqua\Core\App;
use Aqua\UI\Sidebar;
use Aqua\Ragnarok\Server\Logs\ZenyLog;

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
				'map'  => __('ragnarok', 'map'),
				'type' => __('ragnarok', 'type'),
				'tgt'  => __('ragnarok', 'target'),
				'src'  => __('ragnarok', 'source'),
				'zeny' => __('ragnarok', 'amount'),
			)) ?>
	</tr>
	</thead>
	<tbody>
	<?php if(empty($log)) : ?>
		<tr><td colspan="7" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td></tr>
	<?php else : foreach($log as $zeny) : ?>
		<tr>
			<td><?php echo $zeny->id ?></td>
			<td><?php echo $zeny->date($datetimeFormat) ?></td>
			<td><?php echo htmlspecialchars($zeny->map) ?: '--' ?></td>
			<td><?php echo $zeny->type() ?></td>
			<?php if($char = $zeny->target()) : ?>
				<td><?php echo htmlspecialchars($char->name) ?></td>
			<?php else : ?>
				<td><?php echo ($zeny->charId ? __('ragnarok', 'deleted', $zeny->charId) : '--') ?></td>
			<?php endif; switch($zeny->sourceType()) {
			case ZenyLog::SOURCE_PC: if($src = $zeny->source()) : ?>
				<td><a href="<?php echo $src->charmap->url(array(
				            'action' => 'viewchar',
				            'arguments' => array( $src->id )
						)) ?>"><?php echo htmlspecialchars($src->name) ?></a></td>
			<?php else : ?>
				<td><?php echo ($zeny->srcId ? __('ragnarok', 'deleted', $zeny->srcId) : '--') ?></td>
			<?php endif; break;
			case  ZenyLog::SOURCE_MOB: if($src = $zeny->source()) : ?>
				<td><a href="<?php echo $src->charmap->url(array(
							'path' => array( 'mob' ),
				            'action' => 'view',
				            'arguments' => array( $src->id )
						), false) ?>"><?php echo htmlspecialchars($src->iName) ?></a></td>
			<?php else : ?>
				<td><?php echo ($zeny->srcId ? __('ragnarok', 'deleted', $zeny->srcId) : '--') ?></td>
			<?php endif; break;
			case  ZenyLog::SOURCE_ITEM: if($src = $zeny->source()) : ?>
				<td><a href="<?php echo $src->charmap->url(array(
							'path' => array( 'item' ),
				            'action' => 'view',
				            'arguments' => array( $src->id )
						), false) ?>"><?php echo htmlspecialchars($src->jpName) ?></a></td>
			<?php else : ?>
				<td><?php echo ($zeny->srcId ? __('ragnarok', 'deleted', $zeny->srcId) : '--') ?></td>
			<?php endif; break;
			default: ?>
				<td><?php echo $zeny->srcId ?: '--' ?></td>
			<?php break;
			} ?>
			<td><?php echo number_format($zeny->amount) ?></td>
		</tr>
	<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
	<tr><td colspan="7"><?php echo $paginator->render() ?></td></tr>
	</tfoot>
</table>
<span class="ac-search-result"><?php echo __('application',
                                             'search-results-' . ($count === 1 ? 's' : 'p'),
                                             number_format($count)) ?></span>
