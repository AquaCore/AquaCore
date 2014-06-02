<?php
/**
 * @var \Aqua\Log\BanLog[]  $ban
 * @var int                 $banCount
 * @var \Page\Admin\Log     $page
 * @var \Aqua\UI\Search     $search
 * @var \Aqua\UI\Pagination $paginator
 */

use Aqua\Core\App;
use Aqua\Log\BanLog;
use Aqua\UI\Sidebar;
use Aqua\UI\ScriptManager;

$search->field('type')->css('height', '4.5em');

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
			'id'    => __('profile', 'id'),
		    'admin' => __('profile', 'user'),
		    'user'  => __('profile', 'banned-by'),
		    'type'  => __('profile', 'ban-type'),
		    'ban'   => __('profile', 'ban-date'),
		    'unban' => __('profile', 'unban-date'),
		)) ?>
	</tr>
	</thead>
	<tbody>
	<?php if(empty($ban)) : ?>
		<tr><td colspan="6" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td></tr>
	<?php else : foreach($ban as $b) : ?>
		<tr>
			<td style="width: 100px"><?php echo $b->id ?></td>
			<td>
				<a href="<?php echo ac_build_url(array(
						'path'      => array( 'user' ),
						'action'    => 'view',
						'arguments' => array( $b->bannedId )
					)) ?>"><?php echo $b->bannedAccount()->display() ?></a>
			</td>
			<td>
				<a href="<?php echo ac_build_url(array(
						'path'      => array( 'user' ),
						'action'    => 'view',
						'arguments' => array( $b->userId )
					)) ?>"><?php echo $b->account()->display() ?></a>
			</td>
			<td><?php echo $b->type() ?></td>
			<td><?php echo $b->banDate($datetimeFormat) ?></td>
			<td><?php echo ($b->unbanDate ? $b->unbanDate($datetimeFormat) : '--'); ?></td>
		</tr>
		<?php if(!empty($b->reason)) : ?>
		<tr>
			<td style="text-align: justify"><b><?php echo __('profile', ($b->type === BanLog::TYPE_UNBAN ? 'unban-' : 'ban-') . 'reason') ?>:</b></td>
			<td colspan="5" class="ac-ban-reason" style="text-align: justify"><?php echo htmlspecialchars($b->reason) ?></td>
		</tr>
		<?php endif; ?>
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
<span class="ac-search-result"><?php echo __('application', 'search-results-' . ($banCount === 1 ? 's' : 'p'), number_format($banCount)) ?></span>
