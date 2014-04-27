<?php
use Aqua\Content\ContentType;
use Aqua\Core\App;

/**
 * @var $page \Page\Main
 */

$date_format = App::settings()->get('date_format');
$time_format = App::settings()->get('time_format');
$posts = ContentType::getContentType(ContentType::CTYPE_POST)->featured(5);
?>
<!--- Featured Posts --->
<table class="ac-headlines ac-table">
	<thead>
	<tr class="alt">
		<td>
			<div style="position: relative">
			<div class="ac-site-search">
				<form method="GET"
				      action="<?php ac_build_path(array(
						'path' => array( 'content' ),
				        'action' => 'search'
					)) ?>"
				      onsubmit="if(!document.getElementById('content-search').value.trim()) { event.preventDefault(); event.stopPropagation(); return false; }">
				<?php echo ac_form_path(array( 'content' ), 'search') ?>
				<input type="search"
				       id="content-search"
				       name="s"
				       placeholder="<?php echo __('application', 'search') ?>">
				</form>
			</div>
			<div class="ac-headlines-links">
				<a href="<?php echo ac_build_url(array( 'path' => array( 'news' ) )) ?>">
					<?php echo __('news', 'more-posts') ?>
				</a>
				<a href="<?php echo ac_build_url(array( 'path' => array( 'news' ), 'action' => 'feed' )) ?>">
					<?php echo __('content', 'rss-feed') ?>
				</a>
			</div>
			</div>
		</td>
	</tr>
	</thead>
	<tbody>
	<?php if(empty($posts)) : ?>
		<tr>
			<td class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td>
		</tr>
	<?php else : foreach($posts as &$post) : ?>
		<tr>
			<td>
				<?php $url = ac_build_url(array( 'path' => array( 'news', $post->slug ) )) ?>
				<div class="ac-headline-title"><a class="ac-headline-link"
				                                  href="<?php echo $url ?>"><?php echo htmlspecialchars($post->title) ?></a>
				</div>
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
		<td></td>
	</tr>
	</tfoot>
</table>
<div>
</div>
