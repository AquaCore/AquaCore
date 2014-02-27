<?php
use Aqua\UI\Form;
use Aqua\UI\Tag;
use Aqua\UI\ScriptManager;
use Aqua\UI\Sidebar;
use Aqua\User\Role;
/**
 * @var $roles      \Aqua\User\Role[]
 * @var $role_count int
 * @var $paginator  \Aqua\UI\Pagination
 * @var $form       \Aqua\UI\Form
 * @var $token      string
 * @var $page       \Page\Admin\Role
 */
$form->field('permission')->setClass('ac-permission-list');
$page->theme->template = 'sidebar-right';
$page->theme
	->addWordGroup('role', array( 'confirm-delete-s', 'confirm-delete-p', 'edit-role' ))
	->addWordGroup('application', array( 'none' ));
$page->theme->footer->enqueueScript(ScriptManager::script('aquacore.ajax-form'));
$page->theme->footer->enqueueScript('theme.form-functions')
	->type('text/javascript')
	->src($page->theme->url . '/scripts/ajax-form-functions.js');
$page->theme->footer->enqueueScript('theme.roles')
	->src($page->theme->url . '/scripts/roles.js');
$sidebar = new Sidebar;
$sidebar->wrapper($form->buildTag());
ob_start();
?>
<div class="ac-form-warning"><?php echo $form->field('name')->getWarning() ?></div>
<?php echo $form->field('name')->render() ?>
<?php
$sidebar->append('name', array(array(
		'title' => $form->field('name')->getLabel(),
		'content' => ob_get_contents()
	)));
ob_clean();
?>
<div class="ac-form-warning"><?php echo $form->field('color')->getWarning() ?></div>
<?php echo $form->field('color')->type('text')->placeholder('#FFFFFF')->render() ?>
<small><?php echo $form->field('color')->getDescription() ?></small>
<?php
$sidebar->append('color', array(array(
		'title' => $form->field('color')->getLabel(),
		'content' => ob_get_contents()
	)));
ob_clean();
?>
<div class="ac-form-warning"><?php echo $form->field('background')->getWarning() ?></div>
<?php echo $form->field('background')->type('text')->placeholder('#FFFFFF')->render() ?>
<small><?php echo $form->field('background')->getDescription() ?></small>
<?php
$sidebar->append('background', array(array(
		'title' => $form->field('background')->getLabel(),
		'content' => ob_get_contents()
	)));
ob_clean();
?>
<div class="ac-form-warning"><?php echo $form->field('permission')->getWarning() ?></div>
<?php echo $form->field('permission')->render() ?>
<div class="ac-permission-description"></div>
<?php
$sidebar->append('permission', array(array(
		'title' => $form->field('permission')->getLabel(),
		'content' => ob_get_contents()
	)));
ob_clean();
?>
<div class="ac-form-warning"><?php echo $form->field('description')->getWarning() ?></div>
<?php echo $form->field('description')->render() ?>
<small><?php echo $form->field('description')->getDescription() ?></small>
<?php
$sidebar->append('description', array(array(
		'title' => $form->field('description')->getLabel(),
		'content' => ob_get_contents()
	)))->append('submit', array('class' => 'ac-sidebar-action', array(
		'content' => '<input class="ac-sidebar-submit" type="submit" value="' . __('role', 'new-role') .'">'
	)));
ob_end_clean();
$page->theme->set('sidebar', $sidebar);
$base_edit_url = ac_build_url(array(
		'path' => array( 'role' ),
		'action' => 'edit',
		'arguments' => array( '' )
	));
$dialog = '';
$form->append('<div class="ac-form-response"></div><input type="submit" value="' . __('application', 'submit') . '" class="ac-button">');
?>
<form method="POST">
	<table class="ac-table">
		<thead>
			<tr>
				<td colspan="7" style="text-align: right">
					<select name="action">
						<option value="delete"><?php echo __('role', 'delete') ?></option>
					</select>
					<input type="submit" name="x-bulk" value="<?php echo __('application', 'apply') ?>">
				</td>
			</tr>
			<tr class="alt">
				<td style="width: 30px;"><input type="checkbox" ac-checkbox-toggle="roles[]"></td>
				<td><?php echo __('role', 'id') ?></td>
				<td><?php echo __('role', 'name') ?></td>
				<td><?php echo __('role', 'color') ?></td>
				<td><?php echo __('role', 'background') ?></td>
				<td><?php echo __('role', 'description') ?></td>
				<td><?php echo __('application', 'action') ?></td>
			</tr>
		</thead>
		<tbody>
		<?php foreach($roles as $role) : ?>
			<tr ac-role-id="<?php echo $role->id ?>">
				<td><input type="checkbox" name="roles[]" value="<?php echo $role->id ?>"></td>
				<td><?php echo $role->id ?></td>
				<td class="ac-role-name"><?php echo $role->display($role->name, 'ac-username') ?></td>
				<?php if($role->color === null) : ?>
					<td class="ac-role-color"><?php echo __('application', 'none') ?></td>
				<?php else : $color = sprintf('#%06x', $role->color); ?>
					<td class="ac-role-color"><span style="color: <?php echo $color ?>; font-weight: bold;"><?php echo $color ?></span></td>
				<?php endif; ?>
				<?php if($role->background === null) : ?>
					<td class="ac-role-background"><?php echo __('application', 'none') ?></td>
				<?php else : $color = sprintf('#%06x', $role->background); ?>
					<td class="ac-role-background"><span style="color: <?php echo $color ?>; font-weight: bold;"><?php echo $color ?></span></td>
				<?php endif; ?>
				<td class="ac-role-description" style="text-align: justify"><?php echo htmlspecialchars($role->description) ?></td>
				<td class="ac-actions">
					<?php if($role->editable) : ?>
						<a ac-role-id="<?php echo $role->id ?>" class="ac-edit-role" href="<?php echo ac_build_url(array(
								'path' => array( 'role' ),
								'action' => 'edit',
								'arguments' => array( $role->id )
								)) ?>"><button class="ac-action-edit" type="button"><?php echo __('role', 'edit') ?></button></a>
					<?php
					$frm = clone $form;
					$frm->action = $base_edit_url . $role->id;
					$frm->field('name')->value(htmlspecialchars($role->name));
					$frm->field('description')->value(htmlspecialchars($role->description));
					foreach($frm->field('permission')->values as $key => $opt) {
						if(isset($role->permission[$key]) && $role->permission[$key] === 2) {
							$opt->bool('disabled', true);
						} else {
							$opt->bool('disabled', false);
						}
					}
					$frm->field('permission')->checked($role->permissions());
					$frm->field('color')->value($role->color === null ? '' : sprintf('#%06x', $role->color));
					$frm->field('background')->value($role->background === null ? '' : sprintf('#%06x', $role->background));
					$frm->field('description')->attr("rows", 6)->content = array( htmlspecialchars($role->description) );
					$dialog.= "<div class=\"ac-settings\" id=\"edit-role{$role->id}\">";
					$dialog.= $frm->render();
					$dialog.= '</div>';
					endif; ?>
					<?php if(!$role->protected) : ?>
						<a class="ac-delete-role" href="<?php echo ac_build_url(array(
								'path' => array( 'role' ),
								'query' => array(
									'x-action' => 'delete',
									'role' => $role->id,
									'token' => $token
								))) ?>"><button class="ac-action-delete" type="button"><?php echo __('role', 'delete') ?></button></a>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
		<tfoot>
			<tr>
				<td colspan="7">
					<?php echo $paginator->render() ?>
				</td>
			</tr>
		</tfoot>
	</table>
</form>
<span class="ac-search-result"><?php echo __('application', 'search-results-' . ($role_count === 1 ? 's' : 'p'), number_format($role_count)) ?></span>
<?php echo $dialog ?>
