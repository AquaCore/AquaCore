<?php
use Aqua\Core\App;
use Aqua\UI\Tag;
use Aqua\UI\Sidebar;
/**
 * @var $accounts     \Aqua\Ragnarok\Account[]
 * @var $accountCount int
 * @var $paginator    \Aqua\UI\Pagination
 * @var $search       \Aqua\UI\Search
 * @var $page         \Page\Admin\Ragnarok
 */

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
$currentOrder   = $search->getOrder(true);
$currentSorting = $search->getSorting();
?>
<table class="ac-table">
	<thead>
		<tr class="alt">
			<?php echo $search->renderHeader(array(
					'id'     => __('ragnarok', 'account-id'),
					'name'   => __('ragnarok', 'username'),
					'email'  => __('ragnarok', 'email'),
					'user'   => __('ragnarok', 'owner'),
					'sex'    => __('ragnarok', 'sex'),
					'group'  => __('ragnarok', 'group'),
					'state'  => __('ragnarok', 'state'),
					'ip'     => __('ragnarok', 'last-ip'),
					'login'  => __('ragnarok', 'last-login'),
				)) ?>
		</tr>
	</thead>
	<tbody>
	<?php if(!count($accounts)) : ?>
		<tr><td colspan="9" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td></tr>
	<?php else : foreach($accounts as $acc) : ?>
		<tr>
			<td><?php echo $acc->id ?></td>
			<td><a href="<?php echo $acc->server->url(array(
					'action' => 'viewaccount',
			        'arguments' => array( $acc->id )
				)) ?>"><?php echo htmlspecialchars($acc->username) ?></a></td>
			<td><?php echo htmlspecialchars($acc->email) ?></td>
			<?php if($acc->owner) : ?>
				<td><a href="<?php echo ac_build_query(array(
						'path' => array( 'user' ),
				        'action' => 'view',
				        'arguments' => array( $acc->owner )
					)) ?>"><?php echo $acc->user()->display() ?></a></td>
			<?php else : ?>
				<td>--</td>
			<?php endif; ?>
			<td><?php echo $acc->gender() ?></td>
			<td><?php echo $acc->groupName() ?> <small>(<?php echo $acc->groupId ?>)</small></td>
			<td><?php echo $acc->state() ?></td>
			<td><?php echo ($acc->lastIp ? htmlspecialchars($acc->lastIp) : '--') ?></td>
			<td><?php echo $acc->lastLogin($datetimeFormat) ?></td>
		</tr>
	<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="9">
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
<span class="ac-search-result"><?php echo __('application', 'search-results-' . ($accountCount === 1 ? 's' : 'p'), number_format($accountCount)) ?></span>
