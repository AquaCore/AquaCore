<?php
/**
 * @var $comments     \Aqua\Content\Filter\CommentFilter\Comment[]
 * @var $commentCount int
 * @var $content      \Aqua\Content\ContentData
 * @var $paginator    \Aqua\UI\Pagination
 * @var $search       \Aqua\UI\Search
 * @var $page         \Page\Admin\Content\Comments
 */

use Aqua\Core\App;
use Aqua\UI\ScriptManager;
use Aqua\UI\StyleManager;
use Aqua\UI\Sidebar;

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
$page->theme->template = 'sidebar-right';
$page->theme->footer->enqueueScript(ScriptManager::script('aquacore.build-url'));
$sidebar = new Sidebar;
foreach($search->content as $key => $field) {
	$str = $field->render();
	if($desc = $field->getDescription()) {
		$str.= "<br/><small>$desc</small>";
	}
	$sidebar->append($key, array(array(
		'title' => $field->getLabel(),
		'content' => $str
	)));
}
$sidebar->append('submit', array('class' => 'ac-sidebar-action', array(
	'content' => '<input class="ac-sidebar-submit" type="submit" value="' . __('application', 'search') . '">'
)));
$sidebar->wrapper($search->buildTag());
$page->theme->set('sidebar', $sidebar);
?>
<table class="ac-table">
	<colgroup>
		<col style="width: 80px">
		<col style="width: 16%">
	</colgroup>
	<thead>
		<tr class="alt">
			<?php echo $search->renderHeader(array(
				'id' => __('content', 'id'),
				'content' => __('content', 'content'),
				'status' => __('content', 'status'),
				'author' => __('content', 'author'),
				'editor' => __('content', 'editor'),
				'pdate' => __('content', 'publish-date'),
				'edate' => __('content', 'edit-date'),
				'anon' => __('comment', 'anonymous'),
				'rating' => __('content', 'rating'),
				'reports' => __('comment', 'reports'),
			)) ?>
		</tr>
	</thead>
	<tbody>
	<?php if(empty($comments)) : ?>
		<tr>
			<td colspan="10" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td>
		</tr>
	<?php else : foreach($comments as $comment) : ?>
		<tr>
			<td>
				<a href="<?php echo ac_build_url(array(
					'path' => array( 'comments' ),
			        'action' => 'edit',
			        'arguments' => array( $comment->id )
				)) ?>"><?php echo $comment->id ?>
				</a>
			</td>
			<td>
				<a href="<?php echo $comment->contentType->url(array(
					'path' => array( $comment->content()->slug ),
					'query' => array( 'root' => $comment->id ),
				    'hash' => 'comments'
				), false) ?>">
					<?php echo htmlspecialchars($comment->content()->title) ?>
				</a>
			</td>
			<td><?php echo $comment->status() ?></td>
			<td>
				<a href="<?php echo ac_build_url(array(
						'path' => array( 'user' ),
			            'action' => 'view',
			            'arguments' => array( $comment->authorId )
					)) ?>">
					<?php echo $comment->author()->display() ?>
				</a>
			</td>
			<?php if($comment->lastEditorId) : ?>
			<td>
				<a href="<?php echo ac_build_url(array(
						'path' => array( 'user' ),
			            'action' => 'view',
			            'arguments' => array( $comment->lastEditorId )
					)) ?>">
					<?php echo $comment->lastEditor()->display() ?>
				</a>
			</td>
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
			<td colspan="10">
				<a class="ac-comment-toggle" href="#ac-comment-<?php echo $comment->id ?>">
					<?php echo __('comment', 'view-comment') ?>
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
			<td colspan="10"><?php echo $paginator->render() ?></td>
		</tr>
	</tfoot>
</table>
<span class="ac-search-result"><?php echo __('application',
                                             'search-results-' . ($commentCount === 1 ? 's' : 'p'),
                                             number_format($commentCount)) ?></span>
