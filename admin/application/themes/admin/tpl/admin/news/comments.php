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
use Aqua\UI\StyleManager;

$baseCommentUrl = ac_build_url(array(
		'path' => array( 'news', 'comments' ),
        'action' => 'edit',
        'arguments' => array( '' )
	));
$baseUserUrl = ac_build_url(array(
		'path' => array( 'user' ),
        'action' => 'view',
        'arguments' => array( '' )
	));
$basePostUrl = ac_build_url(array(
		'path' => array( 'news' ),
        'action' => 'view',
        'arguments' => array( '' )
	));
$datetimeFormat = App::settings()->get('datetime_format', '');
$page->theme->head->enqueueLink(StyleManager::style('bbcode'));
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
		<td><?php echo __('content', 'parent') ?></td>
		<td><?php echo __('news', 'post') ?></td>
		<td><?php echo __('content', 'status') ?></td>
		<td><?php echo __('content', 'author') ?></td>
		<td><?php echo __('content', 'editor') ?></td>
		<td><?php echo __('content', 'publish-date') ?></td>
		<td><?php echo __('content', 'edit-date') ?></td>
		<td><?php echo __('comment', 'anonymous') ?></td>
		<td><?php echo __('content', 'rating') ?></td>
		<td><?php echo __('content', 'reports') ?></td>
	</tr>
	</thead>
	<tbody>
	<?php if(empty($comments)) : ?>
		<tr>
			<td colspan="11" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td>
		</tr>
	<?php else : foreach($comments as $comment) : ?>
		<tr>
			<td><a href="<?php echo $baseCommentUrl . $comment->id ?>"><?php echo $comment->id ?></a></td>
			<?php if($comment->parentId) : ?>
				<td><a href="<?php echo $baseCommentUrl . $comment->parentId ?>"><?php echo $comment->parentId ?></a></td>
			<?php else : ?>
				<td>--</td>
			<?php endif; ?>
			<td><?php echo htmlspecialchars($comment->content()->title) ?></td>
			<td><?php echo $comment->status() ?></td>
			<td><a href="<?php echo $baseUserUrl . $comment->authorId ?>"><?php echo $comment->author()->display() ?></a></td>
			<?php if($comment->lastEditorId) : ?>
				<td><a href="<?php echo $baseUserUrl . $comment->lastEditorId ?>"><?php echo $comment->lastEditor()->display() ?></a></td>
			<?php else : ?>
				<td>--</td>
			<?php endif; ?>
			<td><?php echo $comment->publishDate($datetimeFormat) ?></td>
			<td><?php echo ($comment->editDate ? $comment->editDate($datetimeFormat) : '--') ?></td>
			<td><?php echo __('application', $comment->anonymous ? 'yes' : 'no') ?></td>
			<td><?php echo number_format($comment->rating) ?></td>
			<?php if($comment->reportCount > 0) : ?>
				<td style="font-weight: bold; color: #ec4b69"><?php echo number_format($comment->reportCount) ?></td>
			<?php else : ?>
				<td><?php echo number_format($comment->reportCount) ?></td>
			<?php endif; ?>
		</tr>
		<tr>
			<td colspan="11">
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
		<td colspan="11"><?php echo $paginator->render() ?></td>
	</tr>
	</tfoot>
</table>
<span class="ac-search-result"><?php echo __('application',
                                             'search-results-' . ($comment_count === 1 ? 's' : 'p'),
                                             number_format($comment_count)) ?></span>
