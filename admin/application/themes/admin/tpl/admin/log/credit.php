<?php
/**
 * @var \Aqua\Log\TransferLog[] $xfer
 * @var int                     $xferCount
 * @var \Page\Admin\Log         $page
 * @var \Aqua\UI\Search         $search
 * @var \Aqua\UI\Pagination     $paginator
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
			'id'       => __('donation', 'id'),
		    'sender'   => __('donation', 'sender'),
		    'receiver' => __('donation', 'receiver'),
		    'amount'   => __('donation', 'credits'),
		    'date'     => __('donation', 'xfer-date'),
		)) ?>
	</tr>
	</thead>
	<tbody>
	<?php if(empty($xfer)) : ?>
		<tr><td colspan="5" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td></tr>
	<?php else : foreach($xfer as $x) : ?>
		<tr>
			<td><?php echo $x->id ?></td>
			<td><a href="<?php echo ac_build_url(array(
						'path' => array( 'user' ),
						'action' => 'view',
						'arguments' => array( $x->senderId )
					)) ?>"><?php echo $x->sender()->display() ?></a></td>
			<td><a href="<?php echo ac_build_url(array(
						'path' => array( 'user' ),
						'action' => 'view',
						'arguments' => array( $x->receiverId )
					)) ?>"><?php echo $x->receiver()->display() ?></a></td>
			<td><?php echo __('donation', 'credit-points', number_format($x->amount)) ?></td>
			<td><?php echo $x->date($datetimeFormat) ?></td>
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
<span class="ac-search-result"><?php echo __('application', 'search-results-' . ($xferCount === 1 ? 's' : 'p'), number_format($xferCount)) ?></span>
