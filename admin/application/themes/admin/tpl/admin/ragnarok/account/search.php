<?php
use Aqua\Core\App;
use Aqua\UI\Tag;
use Aqua\UI\Sidebar;
/**
 * @var $accounts      \Aqua\Ragnarok\Account[]
 * @var $account_count int
 * @var $paginator     \Aqua\UI\Pagination
 * @var $page          \Page\Admin\Ragnarok
 */

$page->theme->template = 'sidebar-right';
$datetime_format = App::settings()->get('datetime_format');
$base_acc_url = ac_build_url(array(
		'path' => array( 'r', $page->server->key ),
		'action' => 'viewaccount',
		'arguments' => array( '' )
	));
$base_user_url = ac_build_url(array(
		'path' => array( 'user' ),
		'action' => 'view',
		'arguments' => array( '' )
	));
$sidebar = new Sidebar;
ob_start();
?>
<input type="text" name="u" value="<?php echo htmlspecialchars($page->request->uri->getString('u'))?>">
<?php
$sidebar->append('username', array(array(
		'title' => __('ragnarok', 'username'),
        'content' => ob_get_contents()
	)));
ob_clean();
?>
<input type="text" name="e" value="<?php echo htmlspecialchars($page->request->uri->getString('e'))?>">
<?php
$sidebar->append('email', array(array(
		'title' => __('ragnarok', 'email'),
        'content' => ob_get_contents()
	)));
ob_clean();
?>
<input type="text" name="ip" value="<?php echo htmlspecialchars($page->request->uri->getString('ip'))?>">
<?php
$sidebar->append('last-ip-address', array(array(
		'title' => __('ragnarok', 'last-ip'),
        'content' => ob_get_contents()
	)));
ob_clean();
?>
<input type="number" name="g" value="<?php echo htmlspecialchars($page->request->uri->getString('g'))?>">
<?php
$sidebar->append('group-id', array(array(
		'title' => __('ragnarok', 'group'),
        'content' => ob_get_contents()
	)));
ob_clean();
$states = $page->request->getArray('s');
?>
<select name="s" multiple>
	<?php foreach(array( 0, 3, 5, 7, 10, 11, 13, 14 ) as $id) : ?>
		<option value="<?php echo $id ?>" <?php if(in_array($id, $states)) echo 'selected' ?>>
			<?php echo __('ragnarok-state', $id) ?>
		</option>
	<?php endforeach; ?>
</select>
<?php
$sidebar->append('state', array(array(
		'title' => __('ragnarok', 'state'),
	    'content' => ob_get_contents()
	)))->append('submit', array('class' => 'ac-sidebar-action', array(
		'content' => '<input class="ac-sidebar-submit" type="submit" value="' . __('application', 'search') . '">'
	)));
ob_end_clean();
$wrapper = new Tag('form');
$wrapper->attr('method', 'GET');
$wrapper->append(ac_form_path());
$sidebar->wrapper($wrapper);
$page->theme->set('sidebar', $sidebar);
?>
<table class="ac-table">
	<thead>
		<tr class="alt">
			<td><?php echo __('ragnarok', 'id') ?></td>
			<td><?php echo __('ragnarok', 'username') ?></td>
			<td><?php echo __('ragnarok', 'email') ?></td>
			<td><?php echo __('ragnarok', 'owner') ?></td>
			<td><?php echo __('ragnarok', 'sex') ?></td>
			<td><?php echo __('ragnarok', 'group') ?></td>
			<td><?php echo __('ragnarok', 'state') ?></td>
			<td><?php echo __('ragnarok', 'last-ip') ?></td>
			<td><?php echo __('ragnarok', 'last-login') ?></td>
		</tr>
	</thead>
	<tbody>
	<?php if($account_count === 0) : ?>
		<tr><td colspan="8" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td></tr>
	<?php else : foreach($accounts as $acc) : ?>
		<tr>
			<td><?php echo $acc->id ?></td>
			<td><a href="<?php echo $base_acc_url . $acc->id ?>"><?php echo htmlspecialchars($acc->username) ?></a></td>
			<td><?php echo htmlspecialchars($acc->email) ?></td>
			<?php if($acc->owner) : ?>
				<td><a href="<?php echo $base_user_url . $acc->owner ?>"><?php echo $acc->user()->display() ?></a></td>
			<?php else : ?>
				<td>--</td>
			<?php endif; ?>
			<td><?php echo $acc->gender() ?></td>
			<td><?php echo $acc->groupName() ?> <small>(<?php echo $acc->groupId ?>)</small></td>
			<td><?php echo $acc->state() ?></td>
			<td><?php echo ($acc->lastIp ? htmlspecialchars($acc->lastIp) : '--') ?></td>
			<td><?php echo $acc->lastLogin($datetime_format) ?></td>
		</tr>
	<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
		<tr><td colspan="9"><?php echo $paginator->render() ?></td></tr>
	</tfoot>
</table>
<span class="ac-search-result"><?php echo __('application', 'search-results-' . ($account_count === 1 ? 's' : 'p'), number_format($account_count)) ?></span>
