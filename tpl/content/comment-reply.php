<?php
use Aqua\UI\ScriptManager;
/**
 * @var $form    \Aqua\UI\Form
 * @var $content \Aqua\Content\ContentData
 * @var $comment null|\Aqua\Content\Filter\CommentFilter\Comment
 * @var $page    \Page\Main\Comment
 */

use Aqua\UI\StyleManager;

$page->theme->head->enqueueLink(StyleManager::style('bbcode'));
$page->theme->footer->enqueueScript(ScriptManager::script('ckeditor'));
$page->theme->footer->enqueueScript(ScriptManager::script('aquacore.build-url'));
$page->theme->footer->enqueueScript(ScriptManager::script('number-format'));
$page->theme->footer->enqueueScript('tpl.comments')
	->type('text/javascript')
	->src(ac_build_url(array(
		'base_dir' => \Aqua\DIR . '/tpl/scripts',
		'script' => 'comments.js'
	)));
$page->theme->addSettings('ckeComments',include \Aqua\ROOT . '/settings/ckeditor.php');
?>
<div class="ac-comments ac-comments-action">
<?php
if(!empty($comment)) {
	$tpl = new \Aqua\UI\Template;
	$tpl->set('content', $content)
		->set('comment', $comment)
		->set('level', 0)
		->set('actions', false)
		->set('page', $page);
	echo $tpl->render('content/comment');
}
?>
	<div class="ac-comment-submit">
		<form method="POST">
			<textarea name="content" id="cke-comment"></textarea>
			<?php if($content->meta->get('comment-anonymously')) : ?>
				<input type="checkbox" name="anonymous" value="1" id="anon-comment">
				<label for="anon-comment"><?php echo __('comment', 'comment-anonymously') ?></label>
			<?php endif; ?>
			<input type="submit" value="<?php echo __('application', 'submit') ?>" class="ac-button">
			<div style="clear: both"></div>
		</form>
	</div>
</div>
