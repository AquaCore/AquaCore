<?php
/**
 * @var $content      \Aqua\Content\ContentData[]
 * @var $contentCount int
 * @var $paginator    \Aqua\UI\Pagination|\Aqua\UI\PaginationPost
 * @var $contentType  \Aqua\Content\ContentType|null
 * @var $page         \Aqua\Site\Page
 */

use Aqua\Core\App;

if(App::user()->loggedIn() && $contentType && $contentType->hasFilter('SubscriptionFilter') &&
   $contentType->filter('SubscriptionFilter')->getOption('content', true)) {
	ob_start();
	$isSubbed = $contentType->isSubscribed(App::user()->account);
?>
<div class="ac-content-subscription <?php if($isSubbed) echo 'subscribed' ?>"
     title="<?php echo __('content', 'sub-desc') ?>">
	<div class="icon"></div>
	<form method="POST" action="<?php echo $contentType->url(array( 'action' => 'subscribe' )) ?>">
	<?php if($isSubbed) : ?>
		<button type="submit"><?php echo __('content', 'unsubscribe') ?></button>
	<?php else : ?>
		<input type="hidden" name="subscribe" value="1">
		<button type="submit"><?php echo __('content', 'subscribe') ?></button>
	<?php endif; ?>
	</form>
</div>
<?php
	$page->title.= ob_get_contents();
	ob_end_clean();
}

$dateFormat = \Aqua\Core\App::settings()->get('date_format');
$timeFormat = \Aqua\Core\App::settings()->get('time_format');
?>
<table class="ac-headlines ac-table">
	<thead>
	<tr>
		<td style="text-align: right">
			<form method="GET" <?php if(!$contentType) echo 'onsubmit="if(!document.getElementById(\'content-search\').value.trim()) { event.preventDefault(); event.stopPropagation(); return false; }"' ?>>
				<?php echo ac_form_path()?>
				<input type="search"
				       id="content-search"
				       name="s"
				       value="<?php echo htmlspecialchars($page->request->uri->getString('s')) ?>">
				<input type="submit" value="<?php echo __('application', 'search') ?>">
			</form>
		</td>
	</tr>
	</thead>
	<tbody>
	<?php if(empty($content)) : ?>
		<tr><td class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td></tr>
	<?php else : foreach($content as $post) : ?>
		<tr>
			<td>
				<?php $url = $post->contentType->url(array( 'path' => array( $post->slug ) )) ?>
				<div class="ac-headline-title"><a class="ac-headline-link" href="<?php echo $url ?>"><?php echo htmlspecialchars($post->title) ?></a></div>
				<?php if($post->contentType->hasFilter('CategoryFilter') &&
				         ($categories = $post->categories())) : ?>
				<div class="ac-post-categories">
					<?php foreach($categories as $_category) : ?>
						<div class="ac-post-category ac-category-<?php echo $_category->id ?>">
							<a href="<?php echo $post->contentType->url(array(
									'path' => array( 'category', $_category->slug ),
								)) ?>"><?php echo htmlspecialchars($_category->name) ?></a>
						</div>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>
				<div class="ac-post-clear"></div>
				<?php if($post->contentType->hasFilter('TagFilter') &&
				         ($tags = $post->tags())) : ?>
					<div class="ac-post-tags">
						<?php foreach($tags as $tag) : ?>
							<div class="ac-post-tag">
								<a href="<?php echo $post->contentType->url(array(
										'path' => array( 'tagged', $tag ),
									)) ?>"><?php echo htmlspecialchars($tag) ?></a>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
				<div class="ac-headline-author">
					<?php echo __('news', 'posted-by', $post->author()->display()) ?>
				</div>
				<div class="ac-headline-date">
					<?php echo $post->publishDate($dateFormat) ?>
					<div class="ac-headline-time"><?php echo $post->publishDate($timeFormat) ?></div>
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
			<div style="position: relative">
			<?php echo $paginator->render() ?>
			<?php
			if($contentType && $contentType->hasFilter('FeedFilter')) : ?>
				<div style="position: absolute; top: 0; right: 10px; line-height: 2em">
				<?php if(isset($category)) : ?>
					<a style="margin-right: 10px;" href="<?php echo $contentType->url(array(
							'action' => 'feed',
					        'arguments' => array( 'rss', $category->slug )
						)) ?>"><?php echo __('content', 'rss-feed') ?></a>
					<a href="<?php echo $contentType->url(array(
							'action' => 'feed',
					        'arguments' => array( 'atom', $category->slug )
						)) ?>"><?php echo __('content', 'atom-feed') ?></a>
				<?php else : ?>
					<a style="margin-right: 10px;" href="<?php echo $contentType->url(array(
							'action' => 'feed',
					        'arguments' => array( 'rss' )
						)) ?>"><?php echo __('content', 'rss-feed') ?></a>
					<a href="<?php echo $contentType->url(array(
							'action' => 'feed',
					        'arguments' => array( 'atom' )
						)) ?>"><?php echo __('content', 'atom-feed') ?></a>
				<?php endif; ?>
				</div>
			<?php endif; ?>
			</div>
		</td>
	</tr>
	</tfoot>
</table>
<span class="ac-search-result"><?php echo __('application', 'search-results-' . ($contentCount === 1 ? 's' : 'p'), number_format($contentCount)) ?></span>
