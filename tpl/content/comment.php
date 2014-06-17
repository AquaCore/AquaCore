<?php
use Aqua\Core\App;

 /**
  * @var $comment \Aqua\Content\Filter\CommentFilter\Comment
  * @var $ratings array
  * @var $level   int
  * @var $actions bool
  * @var $content \Aqua\Content\ContentData
  * @var $page    \Aqua\Site\Page
  */

$isArchived = ($content->contentType->hasFilter('ArchiveFilter') &&
               $content->isArchived());
?>
<div class="ac-comment <?php
if($level) {
	echo 'ac-comment-child ';
}
if($comment->authorId === $content->authorId && !$comment->anonymous) {
	echo 'ac-comment-op ';
} ?>"
	ac-comment-id="<?php echo $comment->id ?>"
	ac-comment-ctype="<?php echo $comment->contentType->id ?>">
	<div class="ac-comment-avatar">
		<img src="<?php echo $comment->authorAvatar() ?>">
	</div>
	<div class="ac-comment-body">
		<div class="ac-comment-info">
			<div class="ac-comment-info-header">
				<?php echo __('comment', 'info',
				              $comment->authorDisplay(),
				              $comment->timeElapsedPublishDate()) ?>
			</div>
			<?php if($content->contentType->filter('CommentFilter')->getOption('rating', true)) : ?>
				<div class="ac-comment-rating">
					<?php if($actions && !$isArchived &&
					         App::user()->role()->hasPermission('rate')) : ?>
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
				              $comment->timeElapsedEditDate()); ?>
			</div>
		<?php endif; ?>
		<div class="ac-comment-actions">
			<?php if($actions) : ?>
				<a class="ac-comment-permalink" href="<?php echo App::request()->uri->url(array(
					'query' => array( 'root' => $comment->id ),
				    'hash' => 'comments'
				)) ?>"><?php echo __('comment', 'permalink') ?></a>
				<?php if($comment->nestingLevel && $level === 0) : ?>
					<a class="ac-comment-permalink" href="<?php echo App::request()->uri->url(array(
						'query' => array( 'root' => $comment->parentId ),
						'hash' => 'comments'
					)) ?>"><?php echo __('comment', 'parent') ?></a>
				<?php endif; ?>
				<?php if(!$isArchived && App::user()->role()->hasPermission('comment')) :
					if($comment->authorId === App::user()->account->id &&
					   $comment->contentType->filter('CommentFilter')->getOption('editing', true)) :
						$page->theme->jsSettings['commentSource'][$comment->id] = $comment->bbCode;
					?>
						<a class="ac-comment-edit" href="<?php echo ac_build_url(array(
							'path' => array( 'comment' ),
							'action' => 'edit',
							'arguments' => array( $comment->contentType->key, $comment->id ),
							'query' => array( 'return' => $page->theme->jsSettings['base64Url'] )
						)) ?>"><?php echo __('comment', 'edit') ?></a>
					<?php endif; ?>
					<a class="ac-comment-reply" href="<?php echo ac_build_url(array(
						'path' => array( 'comment' ),
					    'action' => 'reply',
					    'arguments' => array( $comment->contentType->key, $content->uid, $comment->id ),
					    'query' => array( 'return' => $page->theme->jsSettings['base64Url'] )
					)) ?>"><?php echo __('comment', 'reply') ?></a>
					<a class="ac-comment-report" href="<?php echo ac_build_url(array(
						'path' => array( 'comment' ),
					    'action' => 'report',
					    'arguments' => array( $comment->contentType->key, $comment->id ),
					    'query' => array( 'return' => $page->theme->jsSettings['base64Url'] )
					)) ?>"><?php echo __('comment', 'report') ?></a>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php if($actions && $comment->childCount !== count($comment->children)) : ?>
		<div class="ac-child-count"><?php echo __('comment', 'child-count', number_format($comment->childCount)) ?></div>
		<?php endif; ?>
		<?php if($actions && !empty($comment->children)) : ?>
			<div class="ac-hide-children ac-script"><?php echo __('comment', 'toggle-children') ?></div>
		<?php endif; ?>
	</div>
	<div class="ac-comment-children">
	<?php
	if(!empty($comment->children)) : ?>
		<?php
		$tpl = new \Aqua\UI\Template;
		foreach($comment->children as $child) {
			$tpl->set('page', $page)
			    ->set('ratings', $ratings)
			    ->set('level', $level + 1)
			    ->set('actions', $actions)
			    ->set('content', $content)
			    ->set('comment', $child);
			echo $tpl->render('content/comment');
		}
		?>
	<?php endif; ?>
	</div>
</div>