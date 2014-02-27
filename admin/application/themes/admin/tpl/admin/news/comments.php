<?php
/**
 * @var $comments      \Aqua\Content\Filter\CommentFilter\Comment[]
 * @var $comment_count int
 * @var $paginator     \Aqua\UI\Pagination
 * @var $post          \Aqua\Content\Adapter\Post
 * @var $page          \Page\Admin\News\Comments
 */

use Aqua\Core\App;
use Aqua\UI\ScriptManager;

$datetime_format = App::settings()->get('datetime_format', '');
$page->theme->footer->enqueueScript(ScriptManager::script('jquery-ui'));
$page->theme->footer->enqueueScript('theme.toggle-comment')
	->type('text/javascript')
	->append('
(function($) {
	$(".ac-comment-toggle").on("click", function() {
		var content = $($(this).attr("href"));
		console.log(content);
		if(content.is(":hidden")) {
			content.show("blind", { easing: "easeInOutCirc" }, 200);
		} else {
			content.hide("blind", { easing: "easeInOutCirc" }, 100);
		}
	});
})(jQuery);
');
?>
<table class="ac-table">
	<thead>
	<tr>
		<td><?php echo __('content', 'id') ?></td>
		<td><?php echo __('news', 'post') ?></td>
		<td><?php echo __('content', 'author') ?></td>
		<td><?php echo __('content', 'editor') ?></td>
		<td><?php echo __('content', 'publish-date') ?></td>
		<td><?php echo __('content', 'edit-date') ?></td>
		<td><?php echo __('comment', 'anonymous') ?></td>
		<td><?php echo __('content', 'rating') ?></td>
	</tr>
	</thead>
	<tbody>
	<?php if(empty($comments)) : ?>
		<tr>
			<td colspan="8" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td>
		</tr>
	<?php else : foreach($comments as $comment) : ?>
		<tr>
			<td><?php echo $comment->id ?></td>
			<td><?php echo htmlspecialchars($comment->content()->title) ?></td>
			<td><?php echo $comment->author()->display() ?></td>
			<td>
				<?php if(!$comment->lastEditorId) : echo '--'; else : ?>
					<a href=""><?php $comment->author()->display() ?></a>
				<?php endif; ?>
			</td>
			<td><?php echo $comment->publishDate($datetime_format) ?></td>
			<td><?php echo ($comment->editDate ? $comment->editDate($datetime_format) : '--') ?></td>
			<td><?php echo __('application', $comment->anonymous ? 'yes' : 'no') ?></td>
			<td><?php echo number_format($comment->rating) ?></td>
		</tr>
		<tr>
			<td colspan="8">
				<a class="ac-comment-toggle" href="#ac-comment-<?php echo $comment->id ?>">
					<?php echo __('content', 'view-comment') ?>
				</a>
				<div class="ac-comment-toggle-content" id="ac-comment-<?php echo $comment->id ?>" style="display: none">
					<?php echo $comment->html ?>
				</div>
			</td>
		</tr>
	<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
	<tr>
		<td colspan="8"><?php echo $paginator->render() ?></td>
	</tr>
	</tfoot>
</table>
<span class="ac-search-result"><?php echo __('application',
                                             'search-results-' . ($comment_count === 1 ? 's' : 'p'),
                                             number_format($comment_count)) ?></span>
