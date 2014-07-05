<?php
/**
 * @var $content   \Aqua\Content\ContentData
 * @var $rating    \Aqua\UI\Template
 * @var $comments  \Aqua\UI\Template
 * @var $paginator \Aqua\UI\Pagination|\Aqua\UI\PaginationPost
 * @var $page      \Aqua\Site\Page
 */

use Aqua\Core\App;
use Aqua\Content\Filter\SubscriptionFilter;

$query = array();
if($paginator->currentPage !== 1) {
	$query['page'] = $paginator->currentPage;
}
$author = $content->author();
if($content->contentType->hasFilter('TagFilter')) {
$tags = $content->tags();
}
$page->theme->head->enqueueMeta('description')
	->name('description')
	->content($content->shortContent ? $content->shortContent : html_entity_decode(strip_tags(substr($content->plainText, 0, 150)), ENT_QUOTES, 'UTF-8'));
if($content->contentType->hasFilter('SubscriptionFilter') &&
   $content->contentType->hasFilter('CommentFilter') &&
   $content->contentType->filter('SubscriptionFilter')->getOption('comments', true) &&
   App::user()->loggedIn()) {
	$subType = $content->isSubscribed(App::user()->account);
	ob_start();
	$formAction = $content->contentType->url(array(
		'action' => 'subscribe',
		'arguments' => array( $content->slug, SubscriptionFilter::COMMENT_SUBSCRIPTION )
	));
?>
<div class="ac-content-subscription has-options <?php switch($subType) {
	case SubscriptionFilter::COMMENT_SUBSCRIPTION:
		echo 'subscribed-comment'; break;
	case SubscriptionFilter::REPLY_SUBSCRIPTION:
		echo 'subscribed-reply'; break;
} ?>">
	<div class="icon"></div>
	<?php echo __('content', 'subscribe') ?>
	<ul class="ac-content-sub-types">
		<?php if($subType === SubscriptionFilter::COMMENT_SUBSCRIPTION) : ?>
			<li class="active" title="<?php echo __('content', 'sub-comment') ?>"><?php echo __('content', 'comments') ?></li>
		<?php else : ?>
			<li title="<?php echo __('content', 'sub-comment') ?>">
				<form method="POST" action="<?php echo $formAction ?>">
					<input type="hidden" name="type" value="<?php echo SubscriptionFilter::COMMENT_SUBSCRIPTION ?>">
					<button type="submit"><?php echo __('content', 'comments') ?></button>
				</form>
			</li>
		<?php endif; ?>
		<?php if($subType === SubscriptionFilter::REPLY_SUBSCRIPTION) : ?>
			<li class="active" title="<?php echo __('content', 'sub-reply') ?>"><?php echo __('content', 'replies') ?></li>
		<?php else : ?>
			<li title="<?php echo __('content', 'sub-reply') ?>">
				<form method="POST" action="<?php echo $formAction ?>">
					<input type="hidden" name="type" value="<?php echo SubscriptionFilter::REPLY_SUBSCRIPTION ?>">
					<button type="submit"><?php echo __('content', 'replies') ?></button>
				</form>
			</li>
		<?php endif; ?>
		<?php if($subType) : ?>
			<li class="unsubscribe">
				<form method="POST" action="<?php echo $formAction ?>">
					<button type="submit"><?php echo __('content', 'unsubscribe') ?></button>
				</form>
			</li>
		<?php endif; ?>
	</ul>
</div>
<?php
	$page->title.= ob_get_contents();
	ob_end_clean();
}
?>
<div class="ac-post-header">
	<div class="ac-post-info">
		<div class="ac-post-title">
			<a href="<?php echo $content->contentType->url(array( 'path' => array( $content->slug ) )) ?>">
				<?php echo htmlspecialchars($content->title); ?>
			</a>
		</div>
		<?php if(isset($rating)) echo $rating->render("content/{$content->contentType->key}/rating", 'content/rating') ?>
		<?php if($content->contentType->hasFilter('CategoryFilter') &&
		         ($categories = $content->categories())) : ?>
		<div class="ac-post-categories">
			<?php foreach($categories as $category) : ?>
				<div class="ac-post-category ac-category-<?php echo $category->id ?>">
					<a href="<?php echo $content->contentType->url(array(
							'path' => array( 'category', $category->slug ),
						)) ?>" rel="tag"><?php echo htmlspecialchars($category->name) ?></a>
				</div>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>
		<div style="clear: both"></div>
	</div>
	<?php if($content->contentType->hasFilter('TagFilter') &&
	         ($tags = $content->tags())) : ?>
		<div class="ac-post-tags">
			<?php foreach($tags as $tag) : ?>
				<div class="ac-post-tag">
					<a href="<?php echo $content->contentType->url(array( 'path' => array( 'tagged', $tag ) )) ?>" rel="tag">
						<?php echo $tag; ?>
					</a>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
	<div class="ac-post-author">
		<?php echo __('news', 'posted-by', $author->display()) ?>
	</div>
	<div class="ac-post-date">
		<?php echo $content->publishDate(App::settings()->get('date_format')); ?>
		<div class="ac-post-time">
			<?php echo $content->publishDate(App::settings()->get('time_format')); ?>
		</div>
	</div>
	<div style="clear: both"></div>
</div>
<div class="ac-post-content">
	<?php
	if(isset($content->pages[$paginator->currentPage - 1])) {
		echo $content->pages[$paginator->currentPage - 1];
	} else {
		echo end($content->pages);
		reset($content->pages);
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
if(isset($comments)) {
	echo $comments->render('content/comments');
}
?>
