<?php
use Aqua\Core\App;
use Aqua\UI\Sidebar;
use Aqua\UI\Tag;
use Aqua\User\Account;
use Aqua\User\Role;
/**
 * @var $users     \Aqua\User\Account[]
 * @var $userCount int
 * @var $paginator \Aqua\UI\Pagination
 * @var $search    \Aqua\UI\Search
 * @var $page      \Page\Main\Ragnarok\Server\Item
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
?>
<table class="ac-table">
	<thead>
	<tr class="alt">
		<?php echo $search->renderHeader(array(
			'id'      => __('profile', 'id'),
			'uname'   => __('profile', 'username'),
			'display' => __('profile', 'display-name'),
			'email'   => __('profile', 'email'),
			'role'    => __('profile', 'role'),
			'status'  => __('profile', 'status'),
			'regdate' => __('profile', 'registration-date'),
		)) ?>
	</tr>
	</thead>
	<tbody>
	<?php if($userCount < 1) : ?>
		<tr><td colspan="7" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td></tr>
	<?php else : foreach($users as $user) : ?>
		<tr>
			<td><?php echo $user->id ?></td>
			<td><a href="<?php echo ac_build_url(array(
						'path' => array( 'user' ),
						'action' => 'view',
						'arguments' => array( $user->id )
					)) ?>"><?php echo htmlspecialchars($user->username)?></a></td>
			<td><?php echo $user->display()->render()?></td>
			<td><?php echo htmlspecialchars($user->email)?></td>
			<td><?php echo $user->role()->display($user->role()->name, 'ac-username') ?></td>
			<td><?php echo $user->status()?></td>
			<td><?php echo $user->registrationDate($datetimeFormat)?></td>
		</tr>
	<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
	<tr>
		<td colspan="7">
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
<span class="ac-search-result"><?php echo __('application',
                                             'search-results-' . ($userCount === 1 ? 's' : 'p'),
                                             number_format($userCount)) ?></span>
