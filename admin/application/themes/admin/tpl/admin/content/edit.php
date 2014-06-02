<?php
/**
 * @var $content \Aqua\Content\ContentData|null
 * @var $form    \Aqua\UI\Form
 * @var $page    \Page\Admin\Content
 */

use Aqua\UI\ScriptManager;
use Aqua\UI\Sidebar;
registerCKEditorSettings($page->theme);
$page->theme->template = 'sidebar-right';
$page->theme
	->set('wrapper', $form->buildTag())
	->addSettings('contentData', array(
		'title'       => (isset($content) ? $content->title : null),
		'contentType' => $page->contentType->name,
		'key'         => $page->contentType->key
	))
	->addWordGroup('application', array( 'now', 'none' ))
	->addWordGroup('content', array( 'confirm-delete-s' ));
$page->theme->footer->enqueueScript(ScriptManager::script('ckeditor'));
$page->theme->footer->enqueueScript(ScriptManager::script('jquery-ui.timepicker'));
$page->theme->footer->enqueueScript(ScriptManager::script('moment'));
$page->theme->footer->enqueueScript('theme.page')
	->type('text/javascript')
	->src($page->theme->url . '/scripts/content.js');
$sidebar = new Sidebar;
$fieldKeys = array_keys($form->content);
$publishingOptions = '';
foreach(array('status',
              'publish_date',
              'archive_date') as $key) {
	if(!($field = $form->field($key))) {
		continue;
	}
	unset($fieldKeys[array_search($key, $fieldKeys)]);
	ob_start();
?>
	<tr>
		<td colspan="2" class="ac-form-warning"><?php echo $field->getWarning() ?></td>
	</tr>
	<tr>
		<?php if($key === 'status') : ?>
			<td style="width: 80px"><?php echo $field->getLabel() ?></td>
			<td><?php echo $field->render() ?></td>
		<?php else : ?>
			<td><?php echo $field->getLabel() ?></td>
			<td>
				<div class="ac-schedule-date ac-script" style="font-weight: bold"></div>
				<?php echo $field->attr('class', 'ac-post-schedule')->render() ?>
			</td>
		<?php endif; ?>
	</tr>
<?php
	$publishingOptions.= ob_get_contents();
	ob_end_clean();
}
unset($fieldKeys[array_search('title', $fieldKeys)]);
unset($fieldKeys[array_search('content', $fieldKeys)]);
foreach($fieldKeys as $key) {
	ob_start();
	$field = $form->field($key);
	if($field instanceof \Aqua\UI\Form\Checkbox && !$field->multiple) {
		$id = 'opt-' . $field->name;
?>
	<tr>
		<td colspan="2" class="ac-form-warning"><?php echo $field->getWarning() ?></td>
	</tr>
	<tr>
		<td><label for="<?php echo $id ?>"><?php echo $field->getLabel() ?></label></td>
		<td>
			<?php
			echo $field->option('1')->attr('id', $id)->render();
			if($desc = $field->getDescription()) {
				echo '<br/><small>', $desc, '</small>';
			}
			?>
		</td>
	</tr>
<?php
		$publishingOptions.= ob_get_contents();
	} else if($field instanceof \Aqua\UI\Form\FieldInterface) {
?>
	<div class="ac-form-warning"><?php echo $field->getWarning() ?></div>
	<?php
	echo $field->render();
	if($desc = $field->getDescription()) {
		echo '<br/><small>', $desc, '</small>';
	}
	?>
<?php
		$sidebar->append($key, array(array(
			'title' => $field->getLabel(),
		    'content' => ob_get_contents()
		)));
	}
	ob_end_clean();
}
$previewUrl = $page->contentType->url(array( 'action' => 'preview' ), false);
$previewTitle = __('content', 'preview');
$submit = __('application', 'submit');
if(isset($content)) {
	$delete = '<input class="ac-post-delete red" name="x-delete" type="submit" value="' . __('application', 'delete') . '">';
} else {
	$delete = '';
}
$sidebar->append('publish', array(
	'class' => 'ac-sidebar-action',
    array(
	    'title' => __('content', 'publishing-options'),
        'content' => "
<div class=\"ac-publishing-options\">
	$delete
	<input class=\"ac-post-preview\"
	       type=\"submit\"
	       value=\"$previewTitle\"
	       formaction=\"$previewUrl\"
	       formtarget=\"_blank\"
	       formmethod=\"POST\">
	<table cellspacing=\"1\" cellpadding=\"1\">
		$publishingOptions
	</table>
</div>
<input class=\"ac-sidebar-submit ac-post-submit\"
       type=\"submit\"
       value=\"$submit\"
       ac-default-submit=\"1\">
"
    )));
$page->theme->set('sidebar', $sidebar);
echo $form->field('title')->placeholder($form->field('title')->getLabel())->attr('class', 'ac-content-title')->render();
echo $form->field('content')->attr('id', 'ckeditor')->render();
