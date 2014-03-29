<?php
/**
 * @var $form \Aqua\UI\Form
 * @var $items \Aqua\Ragnarok\ItemData[]
 * @var $page \Page\Admin\Ragnarok\Server
 */

use Aqua\UI\Sidebar;
use Aqua\UI\ScriptManager;

$page->theme->template = 'sidebar-right';

$page->theme->addWordGroup('ragnarok', array( 'confirm-delete-item-s', 'confirm-delete-item-p' ));
$page->theme->footer->enqueueScript(ScriptManager::script('jquery-ui'));
$page->theme->footer->enqueueScript(ScriptManager::script('aquacore.build-url'));
$page->theme->footer->enqueueScript('theme.server-shop')
	->type('text/javascript')
	->src($page->theme->url . '/scripts/server-shop.js');

$sidebar = new Sidebar;
ob_start();
?>
<div class="ac-form-warning"><?php echo $form->field('item')->getWarning() ?></div>
<?php
echo $form->field('item')->attr('class', 'ac-item-name')->render();
$sidebar->append('item', array(array(
	'title' => $form->field('item')->getLabel(),
	'content' => ob_get_contents()
	)));
ob_clean();
?>
<div class="ac-form-warning"><?php echo $form->field('category')->getWarning() ?></div>
<?php
echo $form->field('category')->render();
$sidebar->append('category', array(array(
	'title' => $form->field('category')->getLabel(),
	'content' => ob_get_contents()
	)));
ob_clean();
?>
<div class="ac-form-warning"><?php echo $form->field('price')->getWarning() ?></div>
<?php
echo $form->field('price')->render();
$sidebar->append('price', array(array(
	'title' => $form->field('price')->getLabel(),
	'content' => ob_get_contents()
	)))->append('submit', array('class' => 'ac-sidebar-action', array(
		'content' => '<input class="ac-sidebar-submit" type="submit" value="' . __('ragnarok', 'new-item') .'">'
	)));
ob_end_clean();
$sidebar->wrapper($form->buildTag());
$page->theme->set('sidebar', $sidebar);
?>
<form method="POST">
<table class="ac-table" id="shop-items">
	<colgroup>
		<col style="width: 40px">
		<col style="width: 40px">
		<col>
		<col>
		<col>
		<col>
		<col>
	</colgroup>
	<thead>
		<tr>
			<td colspan="7" style="text-align: right">
				<select name="action">
					<option value="order"><?php echo __('application', 'save-order') ?></option>
					<option value="delete"><?php echo __('application', 'delete') ?></option>
				</select>
				<input type="submit" name="x-bulk" value="<?php echo __('application', 'apply') ?>">
			</td>
		</tr>
		<tr class="alt">
			<td><input type="checkbox" ac-checkbox-toggle="items[]"></td>
			<td></td>
			<td><?php echo __('ragnarok', 'item-id') ?></td>
			<td><?php echo __('ragnarok', 'item') ?></td>
			<td><?php echo __('ragnarok', 'shop-category') ?></td>
			<td><?php echo __('ragnarok', 'price') ?></td>
			<td><?php echo __('ragnarok', 'sold') ?></td>
		</tr>
	</thead>
	<tbody>
	<?php if(empty($items)) : ?>
		<tr><td class="ac-table-no-result" colspan="7"><?php echo __('application', 'no-search-results') ?></td></tr>
	<?php else : foreach($items as $item) : ?>
		<tr>
			<td>
				<input type="checkbox" name="items[]" value="<?php echo $item->id ?>">
				<input type="hidden" name="order" value="<?php echo $item->id ?>">
			</td>
			<td><img src="<?php echo ac_item_icon($item->id) ?>"></td>
			<td><?php echo $item->id ?></td>
			<td><?php echo htmlspecialchars($item->jpName) ?></td>
			<td><?php echo $item->shopCategoryId ? htmlspecialchars($page->charmap->shopCategory($item->shopCategoryId)->name) : __('application', 'none') ?></td>
			<td><?php echo __('donation', 'credit-points', number_format($item->shopPrice)) ?></td>
			<td><?php echo number_format($item->shopSold) ?></td>
		</tr>
	<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
		<tr><td colspan="7"></td></tr>
	</tfoot>
</table>
</form>