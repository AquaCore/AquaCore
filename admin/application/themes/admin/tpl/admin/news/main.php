<?php
/**
 * @var $posts      \Aqua\Content\Adapter\Post[]
 * @var $post_count int
 * @var $paginator  \Aqua\UI\Pagination
 * @var $page       \Page\Admin\News
 */

use Aqua\Core\App;
use Aqua\UI\Sidebar;
use Aqua\UI\Tag;

$page->theme->template = 'sidebar-right';
$page->theme->addWordGroup('news', array( 'confirm-delete-s', 'confirm-delete-p' ));
$page->theme->footer->enqueueScript('theme.news-search')
	->type('text/javascript')
	->src($page->theme->url . '/scripts/news-search.js');
$base_admin_url = ac_build_url(array(
		'path'      => array( 'news' ),
		'action'    => 'edit',
		'arguments' => array( '' )
	));
$base_user_url = ac_build_url(array(
		'path'     => array( 'news', '' ),
		'base_dir' => App::settings()->get('base_dir')
	));
$base_acc_url = ac_build_url(array(
		'path'      => array( 'user' ),
		'action'    => 'view',
		'arguments' => array( '' )
	));
$base_comment_url = ac_build_url(array(
		'path'      => array( 'news', 'comments' ),
		'arguments' => array( '' )
	));
$base_category_url = ac_build_url(array(
		'path'     => array( 'news', 'category', '' ),
		'base_dir' => App::settings()->get('base_dir')
	));
$categories = $page->contentType->categories();
$sidebar    = new Sidebar;
$wrapper    = new Tag('form');
$wrapper->attr('method', 'GET');
$sidebar->wrapper($wrapper);
$datetime_format = App::settings()->get('datetime_format');
ob_start();
?>
<input type="text" name="k" value="<?php echo $page->request->uri->getString('k') ?>">
<?php
$sidebar->append('keyword', array( array(
		'title'   => __('news', 'keyword'),
		'content' => ob_get_contents()
	)));
ob_clean();
$x = $page->request->uri->getArray('c');
?>
<select name="c[]" multiple="1">
	<?php foreach($categories as &$category) : ?>
		<option value="<?php echo $category->id ?>"
				<?php echo(in_array($category->id, $x, false) ? 'selected="selected"' : '') ?>>
			<?php echo htmlspecialchars($category->name) ?>
		</option>
	<?php endforeach; ?>
</select>
<?php
$sidebar->append('category', array( array(
		'title'   => __('news', 'category'),
		'content' => ob_get_contents()
	)));
ob_clean();
$x = $page->request->uri->getArray('s');
?>
<select name="s[]" multiple="1">
	<?php foreach(array(\Aqua\Content\Adapter\Post::STATUS_PUBLISHED,
		                \Aqua\Content\Adapter\Post::STATUS_DRAFT,
		                \Aqua\Content\Adapter\Post::STATUS_SCHEDULED) as $i) : ?>
		<option value="<?php echo $i ?>"
				<?php echo(in_array($i, $x, false) ? 'selected="selected"' : '') ?>>
			<?php echo __('news-status', $i) ?>
		</option>
	<?php endforeach; ?>
</select>
<?php
$sidebar->append('status', array( array(
		'title'   => __('news', 'status'),
		'content' => ob_get_contents()
	)));
ob_clean();
?>
<input class="ac-sidebar-submit" type="submit" value="<?php echo __('news', 'search') ?>">
<?php
$sidebar->append('submit', array(
	'class' => 'ac-sidebar-action',
	array(
		'content' => ob_get_contents()
	)));
ob_end_clean();
$page->theme->set('sidebar', $sidebar);
?>
<form method="POST" id="news-form">
	<table class="ac-table">
		<thead>
		<tr>
			<td colspan="11" style="text-align: right">
				<select name="action">
					<option value="delete"><?php echo __('news', 'delete') ?></option>
				</select>
				<input type="submit" name="x-bulk" value="<?php echo __('application', 'apply') ?>">
			</td>
		</tr>
		<tr class="alt">
			<td style="width: 30px; text-align: center"><input type="checkbox" ac-checkbox-toggle="posts[]"></td>
			<td><?php echo __('content', 'id') ?></td>
			<td><?php echo __('content', 'title') ?></td>
			<td><?php echo __('content', 'categories') ?></td>
			<td><?php echo __('content', 'slug') ?></td>
			<td><?php echo __('content', 'author') ?></td>
			<td><?php echo __('content', 'editor') ?></td>
			<td><?php echo __('content', 'status') ?></td>
			<td><?php echo __('content', 'publish-date') ?></td>
			<td><?php echo __('content', 'edit-date') ?></td>
			<td><?php echo __('content', 'comments') ?></td>
		</tr>
		</thead>
		<tbody>
		<?php if(empty($posts)) : ?>
			<tr>
				<td colspan="11" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td>
			</tr>
		<?php else : foreach($posts as &$post) : ?>
			<tr>
				<td><input type="checkbox" name="posts[]" value="<?php echo $post->uid ?>"></td>
				<td><?php echo $post->uid ?></td>
				<td><a href="<?php echo
						$base_admin_url . $post->uid ?>"><?php echo htmlspecialchars($post->title) ?></a></td>
				<td>
					<?php
					$categories = $post->categories();
					$_categories = array();
					if(empty($categories)) {
						echo '--';
					} else {
						foreach($categories as $c) {
							$_categories[] =
								'<a href="' . $base_category_url . $c->slug . '">' . htmlspecialchars($c->name) .
								'</a>';
						}
						echo implode(', ', $_categories);
					}
					unset($categories, $_categories);
					?>
				</td>
				<td><a href="<?php echo $base_user_url . $post->slug ?>"><?php echo htmlspecialchars($post->slug) ?></a>
				</td>
				<td><a href="<?php echo $base_acc_url . $post->authorId ?>"><?php echo $post->author()->display() ?></a>
				</td>
				<td>
					<?php if($post->lastEditorId) : ?>
						<a href="<?php echo $base_acc_url . $post->lastEditorId ?>"><?php echo $post->lastEditor()
								->display() ?></a>
					<?php else : echo '--'; endif; ?>
				<td><?php echo __('news-status', $post->status) ?></td>
				<td><?php echo $post->publishDate($datetime_format) ?></td>
				<td>
					<?php if($post->editDate) echo $post->editDate($datetime_format); else echo '--' ?>
				</td>
				<td>
					<a href="<?php echo $base_comment_url . $post->uid ?>">
						<div class="ac-comment-count">
							<span><?php echo number_format($post->commentCount()) ?></span>

							<div class="ac-comment-tip"></div>
							<div class="ac-comment-left"></div>
							<div class="ac-comment-right"></div>
						</div>
					</a>
				</td>
			</tr>
		<?php endforeach; endif; ?>
		</tbody>
		<tfoot>
		<tr>
			<td colspan="11" style="text-align: center;">
				<div style="position: relative">
					<?php echo $paginator->render() ?>
					<div style="position: absolute; top: 3px; right: 10px">
						<a href="<?php echo ac_build_url(array(
								'path'   => array( 'news' ),
								'action' => 'new'
							)) ?>">
							<button type="button"><?php echo __('news', 'new-post') ?></button>
						</a>
					</div>
				</div>
			</td>
		</tr>
		</tfoot>
	</table>
</form>
<span class="ac-search-result"><?php echo __('application',
                                             'search-results-' . ($post_count === 1 ? 's' : 'p'),
                                             number_format($post_count)) ?></span>
