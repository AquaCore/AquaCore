<?php
/**
 * @var $comment \Aqua\Content\Filter\CommentFilter\Comment
 * @var $reports \Aqua\Content\Filter\CommentFilter\Report[]
 * @var $form    \Aqua\UI\Form
 * @var $page    \Page\Admin\News\Comments
 */

use Aqua\UI\Sidebar;
use Aqua\UI\ScriptManager;

$page->theme->template = 'sidebar-right';
$page->theme->addSettings('ckeComments',include \Aqua\ROOT . '/settings/ckeditor.php');
$page->theme->footer->enqueueScript(ScriptManager::script('ckeditor'));
$page->theme->footer->enqueueScript('theme.comment')
	->type('text/javascript')
	->append('
(function($) {
	CKEDITOR.replace("ckeditor", $.extend({}, AquaCore.settings.ckeComments, {
		height: 300
	}));
})(jQuery);
');
$sidebar = new Sidebar;
ob_start() ?>
<div class="ac-form-warning"><?php echo $form->field('anonymous')->getWarning() ?></div>
<div style="float: left; width: 50%">
	<input id="anon-comment" type="radio" name="anonymous" value="1" <?php if($comment->anonymous) echo 'checked' ?>>
	<label for="anon-comment"><b><?php echo __('application', 'yes') ?></b></label>
</div>
<div style="float: left; width: 50%;">
	<input id="no-anon-comment" type="radio" name="anonymous" value="" <?php if(!$comment->anonymous) echo 'checked' ?>>
	<label for="no-anon-comment"><b><?php echo __('application', 'no') ?></b></label>
</div>
<div style="clear: both"></div>
<?php
$sidebar->append('anon', array(array(
	'title' => $form->field('anonymous')->getLabel(),
    'content' => ob_get_contents()
	)));
ob_clean(); ?>
<div class="ac-form-warning"><?php echo $form->field('status')->getWarning() ?></div>
<?php
echo $form->field('status')->render();
$sidebar->append('status', array(array(
	'title' => $form->field('status')->getLabel(),
	'content' => ob_get_contents()
	)));
ob_clean();
?>
<input class="ac-sidebar-submit" type="submit" name="submit" value="<?php echo __('application', 'submit') ?>">
<?php
$sidebar->append('submit', array( 'class' => 'ac-sidebar-action', array(
	'content' => ob_get_contents()
	)));
ob_end_clean();
$page->theme
	->set('sidebar', $sidebar)
	->set('wrapper', $form->buildTag());
?>
<?php echo $form->field('content')->attr('id', 'ckeditor')->render() ?>
<table class="ac-table" style="margin-top: 15px">
	<thead>
		<tr>
			<td colspan="4"><?php echo __('comment', 'reports') ?></td>
		</tr>
		<tr class="alt">
			<td><?php echo __('content', 'user') ?></td>
			<td><?php echo __('content', 'ip-address') ?></td>
			<td><?php echo __('content', 'date') ?></td>
			<td><?php echo __('content', 'message') ?></td>
		</tr>
	</thead>
	<tbody>
	<?php if(empty($reports)) : ?>
		<td class="ac-table-no-result" colspan="4"><?php echo __('application', 'no-search-results') ?></td>
	<?php else : foreach($reports as $report) : ?>
		<tr>
			<td><?php echo $report->user()->display() ?></td>
			<td><?php echo $report->ipAddress ?></td>
			<td><?php echo $report->date ?></td>
			<td style="text-align: justify"><?php echo htmlspecialchars($report->report) ?></td>
		</tr>
		<?php if($report->report) : ?>
		<?php endif; ?>
	<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
		<tr><td colspan="4"></td></tr>
	</tfoot>
</table>
