<?php
/**
 * @var $template array
 * @var $form \Aqua\UI\Form
 * @var $page \Page\Admin\Mail
 */
use Aqua\UI\ScriptManager;
use Aqua\UI\Sidebar;
registerCKEditorEmailSettings($page->theme);
$page->theme->template = 'sidebar-right';
$page->theme->set('wrapper', $form->buildTag());
$page->theme->footer->enqueueScript(ScriptManager::script('ckeditor'));
$page->theme->footer->enqueueScript('theme.page')
	->type('text/javascript')
	->src($page->theme->url . '/scripts/email.js');
$sidebar = new Sidebar;
ob_start(); ?>
<ul>
	<?php foreach($template['placeholders'] as $key => $description) : ?>
		<li>
			<b>#<?php echo htmlspecialchars($key) ?>#</b><br/>
			<div class="description"><?php echo htmlspecialchars($description) ?></div>
		</li>
	<?php endforeach; ?>
</ul>
<?php
$sidebar->append('placeholders', array( 'class' => 'email-placeholders', array(
	'title' => __('email', 'placeholders'),
    'content' => ob_get_contents()
)));
ob_clean() ?>
<input class="ac-sidebar-submit"
       type="submit"
       name="submit"
       value="<?php echo __('application', 'submit') ?>"
       ac-default-submit="1">
<?php
$sidebar->append('submit', array( 'class' => 'ac-sidebar-action', array(
	'content' => ob_get_contents()
)));
ob_end_clean();
$page->theme->set('sidebar', $sidebar);
echo $form->field('subject')->placeholder($form->field('subject')->getLabel())->attr('class', 'ac-content-title')->render();
?>
<button type="button" class="tab gray ac-script body-tab" title="<?php echo __('email', 'body-desc') ?>" disabled><?php echo __('email', 'body')?></button>
<button type="button" class="tab gray ac-script altbody-tab" title="<?php echo __('email', 'alt-body-desc') ?>"><?php echo __('email', 'alt-body')?></button>
<div class="email-body"><?php echo $form->field('body')->attr('id', 'ckeditor')->render(); ?></div>
<div class="email-altbody"><?php echo $form->field('altbody')->attr('id', 'email-alt-body')->render(); ?></div>
