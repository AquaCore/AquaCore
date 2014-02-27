<?php
use Aqua\Content\Post;
use Aqua\Core\App;
use Aqua\User\Account;
/**
 * @var $post      \Aqua\Content\Adapter\Post
 * @var $rating    \Aqua\UI\Template
 * @var $comments  \Aqua\UI\Template
 * @var $paginator \Aqua\UI\Pagination|\Aqua\UI\PaginationPost
 * @var $page      \Page\Main\News
 */
$query = array();
if($paginator->currentPage !== 1) {
	$query['page'] = $paginator->currentPage;
}
$base_tag_url = ac_build_url(array( 'path' => array( 'news', 'tagged', '' ) ));
$author = $post->author();
$tags = $post->tags();
$page->theme->head->enqueueMeta('description')
	->name('description')
	->content($post->shortContent ? $post->shortContent : html_entity_decode(strip_tags(substr($post->plainText, 0, 150)), ENT_QUOTES, 'UTF-8'));
?>
<div class="ac-post-header">
	<div class="ac-post-info">
		<div class="ac-post-title"><a href="<?php echo ac_build_url(array( 'path' => array( 'news', $post->slug ) )) ?>"><?php echo htmlspecialchars($post->title); ?></a></div>
		<?php if(isset($rating)) echo $rating->render('content/rating') ?>
		<div class="ac-post-categories">
			<?php foreach($post->categories() as $category) : ?>
				<div class="ac-post-category ac-category-<?php echo $category->id ?>">
					<a href="<?php echo ac_build_url(array(
							'path' => array( 'news', 'category', $category->slug ),
						)) ?>"><?php echo htmlspecialchars($category->name) ?></a>
				</div>
			<?php endforeach; ?>
		</div>
		<div style="clear: both"></div>
	</div>
	<?php if(!empty($tags)) : ?>
	<div class="ac-post-tags">
		<?php foreach($post->tags() as $tag) : ?>
			<div class="ac-post-tag"><a href="<?php echo $base_tag_url . $tag ?>"><?php echo $tag; ?></a></div>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>
	<div class="ac-post-author">
		<?php echo __('news', 'posted-by', $author->display()) ?>
	</div>
	<div class="ac-post-date">
		<?php echo $post->publishDate(App::settings()->get('date_format')); ?>
		<div class="ac-post-time">
			<?php echo $post->publishDate(App::settings()->get('time_format')); ?>
		</div>
	</div>
	<div style="clear: both"></div>
</div>
<div class="ac-post-content">
	<?php
	if(isset($post->pages[$paginator->currentPage - 1])) {
		echo $post->pages[$paginator->currentPage - 1];
	} else {
		echo end($post->pages);
		reset($post->pages);
	}
?>
</div>
<?php
if($paginator->count > 1) {
	$paginator->capRange(1, 7);
	if($paginator->currentPage > 1) {
		$page->theme->head->enqueueLink('prev-page')
		->rel('prev')
		->href($paginator->url($paginator->currentPage - 1));
	}
	if(($paginator->currentPage + 1) <= $paginator->count) {
		$page->theme->head->enqueueLink('next-page')
		->rel('next')
		->href($paginator->url($paginator->currentPage + 1));
	}
	echo '<div class="ac-post-pagination">', $paginator->render(), '</div>';
}
if(isset($comments)) echo $comments->render('content/comments');
?>
