<?php
use Aqua\UI\Sidebar;
/**
 * @var $categories     \Aqua\Ragnarok\ShopCategory[]
 * @var $category_count int
 * @var $form           \Aqua\UI\Form
 * @var $paginator      \Aqua\UI\Pagination
 * @var $page           \Page\Admin\Ragnarok\Server
 */
$page->theme->template = 'sidebar-right';
if(!empty($categories)) {
	$page->theme->addWordGroup('ragnarok-shop', array( 'confirm-delete-s', 'confirm-delete-p' ));
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
		'content' => '<input class="ac-sidebar-submit" type="submit" value="' . __('ragnarok-shop', 'new-category') .'">'
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
	</colgroup>
	<thead>
		<tr>
			<td colspan="5" style="text-align: right">
				<select name="action">
					<option value="order"><?php echo __('ragnarok-shop', 'save-order') ?></option>
					<option value="delete"><?php echo __('ragnarok-shop', 'delete') ?></option>
				</select>
				<input type="submit" name="x-bulk" value="<?php echo __('application', 'apply') ?>">
			</td>
		</tr>
		<tr class="alt">
			<td><input type="checkbox" ac-checkbox-toggle="categories[]"></td>
			<td><?php echo __('content', 'id') ?></td>
			<td><?php echo __('content', 'name') ?></td>
			<td><?php echo __('content', 'slug') ?></td>
			<td><?php echo __('content', 'description') ?></td>
		</tr>
	</thead>
	<tbody>
	<?php if(empty($categories)) : ?>
		<tr><td colspan="5" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td></tr>
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
		</tr>
	<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="5">
				<?php echo $paginator->render() ?>
			</td>
		</tr>
	</tfoot>
</table>
</form>
<span class="ac-search-result"><?php echo __('application',
                                             'search-results-' . ($category_count === 1 ? 's' : 'p'),
                                             number_format($category_count))?></span>
