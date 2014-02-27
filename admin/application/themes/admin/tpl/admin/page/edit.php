<?php
use Aqua\Core\App;
use Aqua\Core\L10n;
use Aqua\UI\Sidebar;
use Aqua\UI\ScriptManager;
/**
 * @var $content  \Aqua\Content\Page
 * @var $form  \Aqua\UI\Form
 * @var $page \Page\Admin\Page
 */
registerCKEditorSettings($page->theme);
$page->theme->template = 'sidebar-right';
$page->theme
	->set('wrapper', $form->buildTag())
	->addWordGroup('application', array( 'now' ))
	->addWordGroup('page', array( 'confirm-delete-s' ));
$page->theme->footer->enqueueScript(ScriptManager::script('ckeditor'));
$page->theme->footer->enqueueScript(ScriptManager::script('jquery-ui.timepicker'));
$page->theme->footer->enqueueScript('theme.page')
	->type('text/javascript')
	->src($page->theme->url . '/scripts/page.js');
if(L10n::getDefault()->code !== 'en') {
	$page->theme->footer->enqueueScript(ScriptManager::script('jquery-ui.timepicker-i18n', array(
				'language' => L10n::getDefault()->code
			)));
	$page->theme->footer->enqueueScript(ScriptManager::script('jquery-ui-i18n', array(
				'language' => L10n::getDefault()->code
			)));
}
$sidebar = new Sidebar;
ob_start();
if($form->field('parent')) :
?>
	<div class="ac-form-warning"><?php echo $form->field('parent')->getWarning() ?></div>
<?php echo $form->field('parent')->render() ?>
<?php
$sidebar->append('parent', array(array(
		'title' => $form->field('parent')->getLabel(),
		'content' => ob_get_contents()
	)));
ob_clean();
endif;
?>
	<div class="ac-publishing-options">
		<input class="ac-post-delete" name="x-delete" type="submit" value="<?php echo __('page', 'delete') ?>">
		<input class="ac-post-preview" name="x-preview" type="submit" value="<?php echo __('page', 'preview') ?>" formaction="<?php echo ac_build_url(array(
				'path'     => array( 'page' ),
				'action'   => 'preview',
				'base_dir' => \Aqua\DIR
			)) ?>" formtarget="_blank" formmethod="POST">
		<table cellspacing="1" cellpadding="1">
			<tr>
				<td style="width: 80px"><?php echo $form->field('status')->getLabel() ?></td>
				<td><?php echo $form->field('status')->render() ?></td>
			</tr>
			<tr><td colspan="2" class="ac-form-warning"><?php echo $form->field('publish_date')->getWarning() ?></td></tr>
			<tr>
				<td><?php echo $form->field('publish_date')->getLabel() ?></td>
				<td>
					<div class="ac-post-schedule-date ac-script" style="font-weight: bold"></div>
					<?php echo $form->field('publish_date')->attr('class', 'ac-post-schedule')->render() ?>
				</td>
			</tr>
			<tr><td colspan="2" class="ac-form-warning"><?php echo $form->field('rating')->getWarning() ?></td></tr>
			<tr>
				<td><label for="opt-page-rating"><?php echo $form->field('rating')->getLabel() ?></label></td>
				<td>
					<?php echo $form->field('rating')->option('1')->attr('id', 'opt-page-rating')->render() ?>
				</td>
			</tr>
		</table>
	</div>
	<input class="ac-sidebar-submit ac-post-submit" type="submit" value="<?php echo __('application', 'submit') ?>">
<?php
$sidebar->append('publish', array(
		'class' => 'ac-sidebar-action',
		array(
			'title' => __('page', 'publishing-options'),
			'content' => ob_get_contents()
		)));
ob_end_clean();
$page->theme->set('sidebar', $sidebar);
echo $form->field('title')->placeholder($form->field('title')->getLabel())->attr('class', 'ac-content-title')->render();
echo $form->field('content')->attr('id', 'ckeditor')->render();
