<?php
/**
 * @var $tag          string
 * @var $posts        \Aqua\Content\Adapter\Post[]
 * @var $post_count   int
 * @var $paginator    \Aqua\UI\Pagination|\Aqua\UI\PaginationPost
 * @var $page         \Page\Main\News
 */
$date_format = \Aqua\Core\App::settings()->get('date_format');
$time_format = \Aqua\Core\App::settings()->get('time_format');
?>
<table class="ac-headlines ac-table">
	<thead>
	<tr>
		<td style="text-align: right">
			<form method="GET">
				<?php echo ac_form_path()?>
				<input type="text" name="s" value="<?php echo htmlspecialchars($page->request->uri->getString('s')) ?>">
				<input type="submit" value="<?php echo __('news', 'search') ?>">
			</form>
		</td>
	</tr>
	</thead>
	<tbody>
	<?php if(empty($posts)) : ?>
		<tr><td class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td></tr>
	<?php else : foreach($posts as $post) : ?>
		<tr>
			<td>
				<?php $url = ac_build_url(array( 'path' => array( 'news', $post->slug ) )) ?>
				<div class="ac-headline-title"><a class="ac-headline-link" href="<?php echo $url ?>"><?php echo htmlspecialchars($post->title) ?></a></div>
				<div class="ac-post-categories">
					<?php foreach($post->categories() as $category) : ?>
						<div class="ac-post-category ac-category-<?php echo $category->id ?>">
							<a href="<?php echo ac_build_url(array(
									'path' => array( 'news', 'category', $category->slug ),
								)) ?>"><?php echo htmlspecialchars($category->name) ?></a>
						</div>
					<?php endforeach; ?>
				</div>
				<div class="ac-post-clear"></div>
				<div class="ac-headline-author">
					<?php echo __('news', 'posted-by', $post->author()->display()) ?>
				</div>
				<div class="ac-headline-date">
					<?php echo $post->publishDate($date_format) ?>
					<div class="ac-headline-time"><?php echo $post->publishDate($time_format) ?></div>
				</div>
				<div class="ac-headline-content">
					<?php echo $post->truncate(
						600,
						" ... <a class=\"ac-headline-link ac-headline-read-more\" href=\"$url\">" .
						__('content', 'read-more') .
						'</a>'
					) ?>
				</div>
			</td>
		</tr>
	<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
	<tr>
		<td style="text-align: center">
			<?php echo $paginator->render() ?>
		</td>
	</tr>
	</tfoot>
</table>
<span class="ac-search-result"><?php echo __('application', 'search-results-' . ($post_count === 1 ? 's' : 'p'), number_format($post_count)) ?></span>
