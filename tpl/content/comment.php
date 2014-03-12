<?php
use Aqua\Core\App;

 /**
  * @var $comment \Aqua\Content\Filter\CommentFilter\Comment
  * @var $level   int
  * @var $content \Aqua\Content\ContentData
  * @var $page    \Aqua\Site\Page
  */
$datetimeFormat = \Aqua\Core\App::settings()->get('datetime_format');
?>
<div class="ac-comment <?php if($level) echo 'ac-comment-child' ?>">
	<div class="ac-comment-avatar">
		<img src="<?php echo $comment->authorAvatar() ?>">
	</div>
	<div class="ac-comment-body">
		<div class="ac-comment-info">
			<div class="ac-comment-info-header">
				<div class="ac-comment-author">
					<?php echo $comment->authorDisplay() ?>
				</div>
				<div clas="ac-comment-date">
					<?php echo $comment->publishDate($datetimeFormat) ?>
				</div>
			</div>
			<?php if($content->contentType->filter('CommentFilter')->getOption('rating', false)) : ?>
				<div class="ac-comment-rating">
					<?php if(App::user()->role()->hasPermission('rate')) : ?>
						<a class="ac-comment-upvote <?php if(isset($ratings[$comment->id]) &&
						                                     $ratings[$comment->id] === 1) {
							echo 'active';
						} ?>"></a>
						<a class="ac-comment-downvote <?php if(isset($ratings[$comment->id]) &&
						                                       $ratings[$comment->id] === -1) {
							echo 'active';
						} ?>"></a>
					<?php endif; ?>
					<div class="ac-comment-points"><?php echo number_format($comment->rating) ?></div>
				</div>
			<?php endif; ?>
		</div>
		<div class="ac-comment-content"><?php echo $comment->html ?></div>
		<?php if($comment->editDate && $comment->lastEditorId) : ?>
			<div class="ac-comment-edited">
				<?php echo __('comment',
				              'edited-by',
				              $comment->lastEditorDisplay(),
				              $comment->editDate($datetimeFormat)); ?>
			</div>
		<?php endif; ?>
	</div>
	<div class="ac-comment-actions"></div>
	<?php
	if(!empty($comment->children)) {
		$tpl = new \Aqua\UI\Template;
		foreach($comment->children as $child) {
			$tpl->set('page', $page)
			    ->set('level', $level + 1)
			    ->set('content', $content)
			    ->set('comment', $child);
			echo $tpl->render('content/comment');
		}
	}
	?>
</div>