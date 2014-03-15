<?php
/**
 * @var $form \Aqua\UI\Form
 * @var $page \Page\Admin\News
 */

use Aqua\Core\App;
use Aqua\Core\L10n;
use Aqua\UI\ScriptManager;
use Aqua\UI\Sidebar;
use Aqua\UI\Tag;

registerCKEditorSettings($page->theme);
$page->theme->template = 'sidebar-right';
$page->theme
	->set('wrapper', $form->buildTag())
	->addSettings('newsTags', $page->contentType->tags())
	->addWordGroup('application', array( 'now', 'none' ));
$page->theme->footer->enqueueScript(ScriptManager::script('ckeditor'));
$page->theme->footer->enqueueScript(ScriptManager::script('jquery-ui.timepicker'));
$page->theme->footer->enqueueScript(ScriptManager::script('moment'));
$page->theme->footer->enqueueScript('theme.news')
	->type('text/javascript')
	->src($page->theme->url . '/scripts/news.js');
$sidebar = new Sidebar;
ob_start();
?>
	<div class="ac-form-warning"><?php echo $form->field('tags')->getWarning() ?></div>
<?php echo $form->field('tags')->attr('class', 'ac-post-tags')->render() ?>
	<small><?php echo $form->field('tags')->getDescription() ?></small>
<?php
$sidebar->append('tags', array(array(
		'title'   => $form->field('tags')->getLabel(),
		'content' => ob_get_contents()
	)));
ob_clean();
if(!empty($form->field('category')->values)) :
?>
	<div class="ac-form-warning"><?php echo $form->field('category')->getWarning() ?></div>
<?php echo $form->field('category')->render() ?>
	<small><?php echo $form->field('category')->getDescription() ?></small>
<?php
$sidebar->append('category', array(array(
		'title'   => $form->field('category')->getLabel(),
		'content' => ob_get_contents()
	)));
ob_clean();
endif;
?>
	<div class="ac-publishing-options">
		<input class="ac-post-preview" name="x-preview" type="submit" value="<?php echo __('content', 'preview') ?>"
		       formaction="<?php echo ac_build_url(array(
			                                           'path'     => array( 'news' ),
			                                           'action'   => 'preview',
			                                           'base_dir' => App::settings()->get('base_dir')
		                                           )) ?>" formtarget="_blank" formmethod="POST">
		<table cellspacing="1" cellpadding="1">
			<tr>
				<td colspan="2" class="ac-form-warning"><?php echo $form->field('status')->getWarning() ?></td>
			</tr>
			<tr>
				<td style="width: 80px"><?php echo $form->field('status')->getLabel() ?></td>
				<td><?php echo $form->field('status')->render() ?></td>
			</tr>
			<tr>
				<td colspan="2" class="ac-form-warning"><?php echo $form->field('publish_date')->getWarning() ?></td>
			</tr>
			<tr>
				<td><?php echo $form->field('publish_date')->getLabel() ?></td>
				<td>
					<div class="ac-schedule-date ac-script" style="font-weight: bold"></div>
					<?php echo $form->field('publish_date')->attr('class', 'ac-post-schedule')->render() ?>
				</td>
			</tr>
			<tr>
				<td colspan="2" class="ac-form-warning"><?php echo $form->field('archive_date')->getWarning() ?></td>
			</tr>
			<tr>
				<td><?php echo $form->field('archive_date')->getLabel() ?></td>
				<td>
					<div class="ac-schedule-date ac-script" style="font-weight: bold"></div>
					<?php echo $form->field('archive_date')->attr('class', 'ac-archive-schedule')->render() ?>
				</td>
			</tr>
			<tr>
				<td colspan="2" class="ac-form-warning"><?php echo $form->field('comments')->getWarning() ?></td>
			</tr>
			<tr>
				<td>
					<label for="opt-comments">
						<?php echo $form->field('comments')->getLabel() ?>
					</label>
				</td>
				<td style="vertical-align: middle">
					<?php echo $form->field('comments')->option('1')->attr('name', 'comments')
						->attr('id', 'opt-comments')->render() ?>
				</td>
			</tr>
			<tr>
				<td colspan="2" class="ac-form-warning"><?php echo $form->field('archiving')->getWarning() ?></td>
			</tr>
			<tr>
				<td>
					<label for="opt-archiving">
						<?php echo $form->field('archiving')->getLabel() ?>
					</label>
				</td>
				<td style="vertical-align: middle">
					<?php echo $form->field('archiving')->option('1')->attr('name', 'archiving')
						->attr('id', 'opt-archiving')->render() ?>
				</td>
			</tr>
			<tr>
				<td colspan="2" class="ac-form-warning"><?php echo $form->field('anonymous')->getWarning() ?></td>
			</tr>
			<tr>
				<td>
					<label for="opt-anonymous">
						<?php echo $form->field('anonymous')->getLabel() ?>
					</label>
				</td>
				<td style="vertical-align: middle">
					<?php echo $form->field('anonymous')->option('1')->attr('id', 'opt-anonymous')->render() ?>
				</td>
			</tr>
			<tr>
				<td colspan="2" class="ac-form-warning"><?php echo $form->field('rating')->getWarning() ?></td>
			</tr>
			<tr>
				<td>
					<label for="opt-rating">
						<?php echo $form->field('rating')->getLabel() ?>
					</label>
				</td>
				<td style="vertical-align: middle">
					<?php echo $form->field('rating')->option('1')->attr('id', 'opt-rating')->render() ?>
				</td>
			</tr>
			<tr>
				<td colspan="2" class="ac-form-warning"><?php echo $form->field('featured')->getWarning() ?></td>
			</tr>
			<tr>
				<td>
					<label for="opt-featured">
						<?php echo $form->field('featured')->getLabel() ?>
					</label>
				</td>
				<td style="vertical-align: middle">
					<?php echo $form->field('featured')->option('1')->attr('id', 'opt-featured')->render() ?>
				</td>
			</tr>
			<tr>
				<td colspan="2" class="ac-form-description">
					<small><?php echo $form->field('featured')->getDescription() ?></small>
				</td>
			</tr>
		</table>
	</div>
	<input class="ac-sidebar-submit ac-post-submit" type="submit" value="<?php echo __('application', 'submit') ?>">
<?php
$sidebar->append('publish', array(
		'class' => 'ac-sidebar-action',
		array(
			'title'   => __('content', 'publishing-options'),
			'content' => ob_get_contents()
		)
	));
ob_end_clean();
$page->theme->set('sidebar', $sidebar);
echo $form->field('title')->placeholder($form->field('title')->getLabel())->attr('class', 'ac-content-title')->render();
echo $form->field('content')->attr('id', 'ckeditor')->render();
