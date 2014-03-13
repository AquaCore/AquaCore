<?php
/**
 * @var $form    \Aqua\UI\Form
 * @var $comment null|\Aqua\Content\Filter\CommentFilter\Comment
 * @var $page    \Page\Main\Comment
 */
?>
<div class="ac-comments ac-comments-action">
	<?php
	if(!empty($comment)) {
		$tpl = new \Aqua\UI\Template;
		$tpl->set('content', $comment->content())
		    ->set('comment', $comment)
		    ->set('level', 0)
		    ->set('actions', false)
		    ->set('page', $page);
		echo $tpl->render('content/comment');
	}
	?>
	<div class="ac-comment-submit">
		<form method="POST">
			<textarea name="report" class="ac-report-field" placeholder="<?php echo __('comment', 'report-placeholder') ?>'"></textarea>
			<input type="submit" value="<?php echo __('application', 'submit') ?>" class="ac-button">
			<div style="clear: both"></div>
		</form>
	</div>
</div>
