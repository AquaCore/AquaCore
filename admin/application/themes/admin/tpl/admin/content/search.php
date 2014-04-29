<?php
/**
 * @var $content      \Aqua\Content\ContentData[]
 * @var $contentCount int
 * @var $paginator    \Aqua\UI\Pagination
 * @var $search       \Aqua\UI\Search
 * @var $page         \Page\Admin\Content
 */

use Aqua\Core\App;
use Aqua\Content\ContentType;
use Aqua\UI\ScriptManager;
use Aqua\UI\Sidebar;

$colspan = 9;
$dateTimeFormat = App::settings()->get('datetime_format', '');
$header = array(
	'bulk'   => '<input type="checkbox" ac-checkbox-toggle="content[]">',
	'id'     => __('content', 'id'),
    'title'  => __('content', 'title'),
    'slug'   => __('content', 'slug'),
);
if($page->contentType->hasFilter('CategoryFilter')) {
	$header['category'] = __('content', 'categories');
	++$colspan;
}
if($page->contentType->hasFilter('RelationshipFilter')) {
	$header['parent'] = __('content', 'parent');
	++$colspan;
}
$header+= array(
    'author' => __('content', 'author'),
    'pdate'  => __('content', 'publish-date'),
    'editor' => __('content', 'editor'),
    'edate'  => __('content', 'edit-date'),
    'status' => __('content', 'status'),
);
if($page->contentType->hasFilter('CommentFilter')) {
	$header['comment'] = __('content', 'comments');
	++$colspan;
}
$page->theme->template = 'sidebar-right';
$page->theme->footer->enqueueScript(ScriptManager::script('aquacore.build-url'));
$page->theme->footer->enqueueScript('theme.content-search')
	->type('text/javascript')
	->src($page->theme->url . '/scripts/content-search.js');
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
<form method="POST" id="content-form">
<table class="ac-table">
	<colgroup>
		<col style="width: 40px">
		<col style="width: 70px">
		<col style="width: 150px">
		<col style="width: 150px">
		<?php if($page->contentType->hasFilter('CategoryFilter')) : ?>
			<col>
		<?php endif; ?>
		<?php if($page->contentType->hasFilter('RelationshipFilter')) : ?>
			<col style="width: 150px">
		<?php endif; ?>
	</colgroup>
	<thead>
		<tr>
			<td colspan="<?php echo $colspan ?>">
				<div style="float: left">
					<?php echo $search->limit()->attr('class', 'ac-search-limit')->render() ?>
				</div>
				<div style="float: right">
					<select name="action">
						<option value="delete"><?php echo __('application', 'delete') ?></option>
					</select>
					<input type="submit" name="x-bulk" value="<?php echo __('application', 'apply') ?>">
				</div>
			</td>
		</tr>
		<tr class="alt">
			<?php echo $search->renderHeader($header) ?>
		</tr>
	</thead>
	<tbody>
	<?php if(empty($content)) : ?>
		<tr><td class="ac-table-no-result" colspan="<?php echo $colspan?>"><?php echo __('application', 'no-search-results') ?></td></tr>
	<?php else : foreach($content as $post) : ?>
		<tr>
			<td><input type="checkbox" name="content[]" value="<?php echo $post->uid ?>"></td>
			<td><?php echo $post->uid ?></td>
			<td><a href="<?php echo $post->contentType->url(array(
					'action' => 'edit',
			        'arguments' => array( $post->uid )
				)) ?>"><?php echo htmlspecialchars($post->title) ?></a></td>
			<td><a href="<?php echo $post->contentType->url(array(
					'path' => array( $post->slug )
				), false) ?>"><?php echo htmlspecialchars($post->slug) ?></a></td>
			<?php if($post->contentType->hasFilter('CategoryFilter')) : ?>
				<td><?php
					$categories = array();
					foreach($post->categories() as $category) {
						$categories[] = '<a href="' . $post->contentType->url(array(
								'path' => array( 'category', $category->slug ),
							), false) . '">' . htmlspecialchars($category->name) . '</a>';
					}
					echo implode(', ', $categories);
					?>
				</td>
			<?php endif; ?>
			<?php if($post->contentType->hasFilter('RelationshipFilter')) : if($parent = $post->parent()) : ?>
				<td><a href="<?php echo $parent->contentType->url(array(
						'action' => 'edit',
				        'arguments' => array( $parent->uid )
					)) ?>"><?php echo htmlspecialchars($parent->title) ?></a></td>
			<?php else : ?>
				<td>--</td>
			<?php endif; endif; ?>
			<td><?php echo $post->author()->display() ?></td>
			<td><?php echo $post->publishDate($dateTimeFormat) ?></td>
			<td><?php echo ($post->lastEditorId ? $post->lastEditor()->display() : '--') ?></td>
			<td><?php echo ($post->editDate ? $post->editDate($dateTimeFormat) : '--') ?></td>
			<td><?php echo $post->status() ?></td>
			<?php if($post->contentType->hasFilter('CommentFilter')) : ?>
				<td>
					<a href="<?php echo $post->contentType->url(array(
						'path' => array( 'comments' ),
					    'arguments' => array( $post->uid )
					)) ?>">
						<div class="ac-comment-count">
							<span><?php echo number_format($post->commentCount()) ?></span>
							<div class="ac-comment-tip"></div>
							<div class="ac-comment-left"></div>
							<div class="ac-comment-right"></div>
						</div>
					</a>
				</td>
			<?php endif; ?>
		</tr>
	<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="<?php echo $colspan ?>">
				<div style="position: relative">
					<?php echo $paginator->render() ?>
					<div style="position: absolute; top: 3px; right: 10px">
						<a href="<?php echo $page->contentType->url(array( 'action' => 'new' )) ?>"><button type="button"><?php
								switch($page->contentType->id) {
									case ContentType::CTYPE_PAGE: echo __('page', 'new-page'); break;
									case ContentType::CTYPE_POST: echo __('news', 'new-post'); break;
									default: echo __('content', 'new', htmlspecialchars($page->contentType->name)); break;
								}
							?></button></a>
					</div>
				</div>
			</td>
		</tr>
	</tfoot>
</table>
</form>
<span class="ac-search-result"><?php echo __('application', 'search-results-' . ($contentCount === 1 ? 's' : 'p'), number_format($contentCount)) ?></span>
