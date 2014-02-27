<?php
use Aqua\Core\App;
use Aqua\UI\Sidebar;
use Aqua\UI\Tag;
use Aqua\User\Account;
use Aqua\User\Role;
/**
 * @var $users      \Aqua\User\Account[]
 * @var $user_count int
 * @var $paginator  \Aqua\UI\Pagination
 * @var $page       \Page\Main\Ragnarok\Server\Item
 */
$base_url = ac_build_url(array(
	'path' => array( 'user' ),
	'action' => 'view',
	'arguments' => array( '' )
));
$date_format = App::settings()->get('datetime_format', '');
$sidebar = new Sidebar();
$sidebar->append('username', array(array(
		'title' => __('profile', 'username'),
		'content' => "<input type=\"text\" name=\"u\" value=\"{$page->request->uri->getString('u')}\">"
	)))->append('display_name', array(array(
		'title' => __('profile', 'display-name'),
		'content' => "<input type=\"text\" name=\"d\" value=\"{$page->request->uri->getString('d')}\">"
	)))->append('email', array(array(
			'title' => __('profile', 'email'),
			'content' => "<input type=\"text\" name=\"e\" value=\"{$page->request->uri->getString('e')}\">"
	)));
$html = '<select name="r[]" multiple="1">';
$roles = $page->request->uri->getArray('r');
$status = $page->request->uri->getArray('s');
foreach(Role::$roles as $id => $role) {
	if($id !== Role::ROLE_GUEST) {
		$selected = (in_array($id, $roles) ? ' selected' : '');
		$html.= "<option value=\"$id\"$selected>$role->name</option>";
	}
}
$html.= '</select>';
$sidebar->append('role', array(array(
		'title' => __('profile', 'role'),
		'content' => $html
	)))->append('status', array(array(
		'title' => __('profile', 'status'),
		'content' => '
		<select name="s[]" multiple="1">
			<option value="' . Account::STATUS_NORMAL . '" ' . (in_array(Account::STATUS_NORMAL, $status) ? 'selected' : '') . '>' . __('account-state', Account::STATUS_NORMAL) . '</option>
			<option value="' . Account::STATUS_SUSPENDED . '" ' . (in_array(Account::STATUS_SUSPENDED, $status) ? 'selected' : '') . '>' . __('account-state', Account::STATUS_SUSPENDED) . '</option>
			<option value="' . Account::STATUS_BANNED . '" ' . (in_array(Account::STATUS_BANNED, $status) ? 'selected' : '') . '>' . __('account-state', Account::STATUS_BANNED) . '</option>
			<option value="' . Account::STATUS_AWAITING_VALIDATION . '" ' . (in_array(Account::STATUS_AWAITING_VALIDATION, $status) ? 'selected' : '') . '>' . __('account-state', Account::STATUS_AWAITING_VALIDATION) . '</option>
		</select>
		'
	)))->append('submit', array('class' => 'ac-sidebar-action', array(
		'content' => '<input class="ac-sidebar-submit" type="submit" value="' . __('account', 'search') . '">'
	)));
$wrapper = new Tag('form');
$wrapper->attr('method', 'GET');
$sidebar->wrapper($wrapper);
$page->theme->template = 'sidebar-right';
$page->theme->set('sidebar', $sidebar);
?>
<table class="ac-table">
	<thead>
	<tr class="alt">
		<td><?php echo __('profile', 'id')?></td>
		<td><?php echo __('profile', 'username')?></td>
		<td><?php echo __('profile', 'display-name')?></td>
		<td><?php echo __('profile', 'email')?></td>
		<td><?php echo __('profile', 'role')?></td>
		<td><?php echo __('profile', 'status')?></td>
		<td><?php echo __('profile', 'registration-date')?></td>
	</tr>
	</thead>
	<tbody>
	<?php if($user_count < 1) : ?>
		<tr><td colspan="9" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td></tr>
	<?php else : foreach($users as $user) : ?>
		<tr>
			<td><?php echo $user->id?></td>
			<td><a href="<?php echo $base_url . $user->id?>"><?php echo htmlspecialchars($user->username)?></a></td>
			<td><?php echo $user->display()->render()?></td>
			<td><?php echo htmlspecialchars($user->email)?></td>
			<td><?php echo $user->role()->display($user->role()->name, 'ac-username') ?></td>
			<td><?php echo $user->status()?></td>
			<td><?php echo $user->registrationDate($date_format)?></td>
		</tr>
	<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
	<tr>
		<td colspan="9" style="text-align: center"><?php echo $paginator->render()?></td>
	</tr>
	</tfoot>
</table>
<span class="ac-search-result"><?php echo __('application', 'search-results-' . ($user_count === 1 ? 's' : 'p'), number_format($user_count)) ?></span>
