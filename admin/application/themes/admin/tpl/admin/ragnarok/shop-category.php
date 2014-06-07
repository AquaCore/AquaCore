<?php
/**
 * @var $categories     \Aqua\Ragnarok\ShopCategory[]
 * @var $category_count int
 * @var $form           \Aqua\UI\Form
 * @var $page           \Page\Admin\Ragnarok\Server
 */

use Aqua\UI\Sidebar;

$page->theme->template = 'sidebar-right';
if(!empty($categories)) {
	$page->theme->addWordGroup('ragnarok', array( 'confirm-delete-category-s', 'confirm-delete-category-p' ));
	$page->theme->footer->enqueueScript('theme.shop-category')
		->type('text/javascript')
		->src($page->theme->url . '/scripts/shop-category.js');
}
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
<div class="ac-form-warning"><?php echo $form->field('description')->getWarning() ?></div>
<?php echo $form->field('description')->render() ?>
<?php
$sidebar->append('description', array(array(
		'title' => $form->field('description')->getLabel(),
		'content' => ob_get_contents()
	)))->append('submit', array('class' => 'ac-sidebar-action', array(
		'content' => '<input class="ac-sidebar-submit" type="submit" value="' . __('ragnarok', 'new-category') .'">'
	)));
ob_end_clean();
$page->theme->set('sidebar', $sidebar);
?>
<form method="POST">
<table class="ac-table" id="shop-categories">
	<colgroup>
		<col style="width: 30px">
		<col style="width: 90px">
		<col>
		<col>
		<col>
		<col>
	</colgroup>
	<thead>
		<tr>
			<td colspan="6" style="text-align: right">
				<select name="action">
					<option value="order"><?php echo __('application', 'save-order') ?></option>
					<option value="delete"><?php echo __('application', 'delete') ?></option>
				</select>
				<input type="submit" name="x-bulk" value="<?php echo __('application', 'apply') ?>">
			</td>
		</tr>
		<tr class="alt">
			<td><input type="checkbox" ac-checkbox-toggle="categories[]"></td>
			<td><?php echo __('content', 'id') ?></td>
			<td><?php echo __('content', 'category-name') ?></td>
			<td><?php echo __('content', 'slug') ?></td>
			<td><?php echo __('content', 'category-description') ?></td>
			<td><?php echo __('application', 'action') ?></td>
		</tr>
	</thead>
	<tbody>
	<?php if(empty($categories)) : ?>
		<tr><td colspan="6" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td></tr>
	<?php else : foreach($categories as $category) : ?>
		<tr>
			<td>
				<input type="checkbox" name="categories[]">
				<input type="hidden" value="<?php echo $category->id ?>" name="order[]">
			</td>
			<td><?php echo $category->id ?></td>
			<td><?php echo htmlspecialchars($category->name) ?></td>
			<td>
				<a href="<?php echo $category->url() ?>">
					<?php echo htmlspecialchars($category->slug) ?>
				</a>
			</td>
			<td><?php echo htmlspecialchars($category->description) ?></td>
			<td class="ac-actions">
				<a href="<?php echo $page->charmap->url(array(
					'action' => 'editcategory',
				    'arguments' => array( $category->id )
				)) ?>"><button class="ac-action-edit"
				        type="button">
					<?php echo __('application', 'edit') ?>
				</button></a>
			</td>
		</tr>
	<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
		<tr><td colspan="6"></td></tr>
	</tfoot>
</table>
</form>
<span class="ac-search-result"><?php echo __('application',
                                             'search-results-' . ($category_count === 1 ? 's' : 'p'),
                                             number_format($category_count))?></span>
