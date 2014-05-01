<?php
/**
 * @var $form           \Aqua\UI\Form
 * @var $categories     \Aqua\Content\Filter\CategoryFilter\Category[]
 * @var $categoryCount  int
 * @var $paginator      \Aqua\UI\Pagination
 * @var $page           \Page\Admin\Content\Category
 */

use Aqua\UI\ScriptManager;
use Aqua\UI\Sidebar;
use Aqua\UI\Tag;
use Aqua\Core\App;

$settings = '';
$sidebar = new Sidebar;
$sidebar->wrapper($form->buildTag());
$page->theme->template = 'sidebar-right';
$page->theme->addWordGroup('content', array( 'confirm-delete-category',
                                             'confirm-delete-categories',
                                             'edit-category' ));
$page->theme->footer->enqueueScript(ScriptManager::script('aquacore.build-url'));
$page->theme->footer->enqueueScript(ScriptManager::script('jquery.autosize'));
$page->theme->footer->enqueueScript(ScriptManager::script('aquacore.ajax-form'));
$page->theme->footer->enqueueScript('theme.form-functions')
	->type('text/javascript')
	->src($page->theme->url . '/scripts/ajax-form-functions.js');
$page->theme->footer->enqueueScript('theme.category')
	->type('text/javascript')
	->src($page->theme->url . '/scripts/category.js');
ob_start();
?>
<div class="ac-form-warning"><?php echo $form->field('name')->getWarning() ?></div>
<?php echo $form->field('image')->render() ?>
<?php
$sidebar->append('image', array(array(
		'title' => $form->field('image')->getLabel(),
		'content' => ob_get_contents()
	)));
ob_clean(); ?>
<div class="ac-form-warning"><?php echo $form->field('name')->getWarning() ?></div>
<?php echo $form->field('name')->render() ?>
<?php
$sidebar->append('name', array(array(
		'title' => $form->field('name')->getLabel(),
		'content' => ob_get_contents()
	)));
ob_clean(); ?>
<div class="ac-form-warning"><?php echo $form->field('description')->getWarning() ?></div>
<?php echo $form->field('description')->attr('rows', 6)->render() ?>
<?php
$sidebar->append('description', array(array(
		'title' => $form->field('description')->getLabel(),
		'content' => ob_get_contents()
	)))->append('submit', array('class' => 'ac-sidebar-action', array(
		'content' => '<input class="ac-sidebar-submit" type="submit" value="' . __('content', 'create-category') . '">'
	)));
ob_end_clean();
$page->theme->set('sidebar', $sidebar);
?>
<form method="POST">
	<table class="ac-table ac-categories">
		<colgroup>
			<col style="width: 30px">
			<col style="width: 30px">
			<col>
			<col style="width: 130px">
			<col>
			<col style="width: 90px">
			<col style="width: 30px">
			<col>
		</colgroup>
		<thead>
		<tr>
			<td colspan="8" style="text-align: right">
				<select name="action">
					<option value="1"><?php echo __('application', 'delete') ?></option>
				</select>
				<input type="submit" name="x-bulk-action" value="<?php echo __('application', 'apply') ?>">
			</td>
		</tr>
		<tr class="alt">
			<td><input type="checkbox" ac-checkbox-toggle="categories[]"></td>
			<td><?php echo __('content', 'id') ?></td>
			<td></td>
			<td><?php echo __('content', 'category-name') ?></td>
			<td><?php echo __('content', 'description') ?></td>
			<td><?php echo __('content', 'slug') ?></td>
			<td><?php echo __('content', 'uses') ?></td>
			<td><?php echo __('application', 'action') ?></td>
		</tr>
		</thead>
		<tbody>
		<?php if(!count($categories)) : ?>
			<tr><td colspan="8" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td></tr>
		<?php else: foreach($categories as &$category) : $categoryUrl = $category->contentType()->url(array(
				'path' => array( 'category' ),
				'action' => 'edit',
				'arguments' => array( $category->id )
			)) ?>
			<tr id="category-info-<?php echo $category->id ?>">
				<td><input type="checkbox" name="categories[]" value="<?php echo $category->id ?>"></td>
				<td><?php echo $category->id ?></td>
				<td class="ac-category-image">
					<img class="category-image-<?php echo $category->id ?>" src="<?php echo $category->image() ?>">
				</td>
				<td class="ac-category-name" ac-field="name"><?php echo htmlspecialchars($category->name) ?></td>
				<td class="ac-category-description" ac-field="description"><?php echo htmlspecialchars($category->description) ?></td>
				<td>
					<a href="<?php echo ac_build_url(array(
						                                 'path' => array( 'news', 'category', $category->slug ),
						                                 'base_dir' => App::settings()->get('base_dir')
					                                 )) ?>" ac-field="slug">
						<?php echo htmlspecialchars($category->slug) ?>
					</a>
				</td>
				<td><?php echo number_format($category->count()) ?></td>
				<td class="ac-actions">
					<?php ob_start() ?>
					<div class="ac-settings" id="category-settings-<?php echo $category->id ?>">
						<form method="POST" enctype="multipart/form-data" action="<?php echo $categoryUrl ?>">
							<table>
								<tr>
									<td colspan="2" style="text-align: center">
										<div class="ac-delete-wrapper">
											<img class="category-image-<?php echo $category->id ?>" src="<?php echo $category->image() ?>">
											<input type="submit" class="ac-delete-button" name="x-delete-image" value="" <?php if(!$category->image) echo ' style="display: none"' ?>>
										</div>
									</td>
								</tr>
								<tr class="ac-form-warning"><td colspan="2"><div></div></td></tr>
								<tr class="ac-form-field">
									<td class="ac-form-label"><?php echo __('content', 'category-image') ?></td>
									<td class="ac-form-tag"><input type="file" name="image" accept="image/jpeg, image/png, image/gif"></td>
								</tr>
								<tr class="ac-form-warning"><td colspan="2"><div></div></td></tr>
								<tr class="ac-form-field">
									<td class="ac-form-label"><?php echo __('content', 'category-name') ?></td>
									<td class="ac-form-tag"><input type="text" name="name" maxlength="255" value="<?php echo htmlspecialchars($category->name) ?>"></td>
								</tr>
								<tr class="ac-form-warning"><td colspan="2"><div></div></td></tr>
								<tr class="ac-form-field">
									<td class="ac-form-label"><?php echo __('content', 'category-description') ?></td>
									<td class="ac-form-tag">
										<textarea name="description" rows="6"><?php echo htmlspecialchars
											($category->description)
											?></textarea>
									</td>
								</tr>
								<tr class="ac-form-field">
									<td class="ac-form-tag" colspan="2" style="text-align: right;">
										<div class="ac-form-response"></div>
										<input type="submit" value="<?php echo __('application', 'submit') ?>" ac-default-submit>
									</td>
								</tr>
							</table>
						</form>
					</div>
					<?php $settings.= ob_get_contents(); ob_end_clean(); ?>
							<a href="<?php echo $categoryUrl ?>" class="edit-category">
								<button class="ac-action-edit ac-script"
								        type="button"
								        value="<?php echo $category->id ?>"><?php echo __('content', 'edit') ?></button>
							</a>
					<button class="ac-action-delete"
					        type="submit"
					        name="x-delete"
					        value="<?php echo $category->id ?>"><?php echo __('content', 'delete') ?></button>
				</td>
			</tr>
		<?php endforeach; endif; ?>
		</tbody>
		<tfoot>
		<tr>
			<td colspan="8">
				<?php echo $paginator->render() ?>
			</td>
		</tr>
		</tfoot>
	</table>
</form>
<span class="ac-search-result"><?php echo __('application', 'search-results-' . ($categoryCount === 1 ? 's' : 'p'), number_format($categoryCount)) ?></span>
<?php echo $settings ?>
