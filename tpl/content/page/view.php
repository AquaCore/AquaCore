<?php
/**
 * @var $content   \Aqua\Content\ContentData
 * @var $rating    \Aqua\UI\Template
 * @var $comments  \Aqua\UI\Template
 * @var $paginator \Aqua\UI\Pagination|\Aqua\UI\PaginationPost
 * @var $page      \Aqua\Site\Page
 */

use Aqua\UI\Menu;

$title = '<a href="' . $content->contentType->url(array( 'path' => array( $content->slug ) )) . '" style="float: left">';
$title.= htmlspecialchars($content->title);
$title.= '</a>';
if(isset($rating)) {
	$title.= $rating->render('content/rating');
}
$title.= '<div style="clear: both"></div>';

$page->title = $title;
if(!$content->forged) {
	$children = $content->children();
	if(count($children)) {
		$menu = new Menu;
		foreach($children as $child) {
			$menu->append($child->id, array(
				'title' => htmlspecialchars($child->title),
				'url' => ac_build_url(array( 'path' => array( 'page', $child->slug ) ))
			));
		}
		$page->theme->set('menu', $menu);
	}
}
?>
<div class="ac-post-content">
	<?php echo $content->pages[$paginator->currentPage - 1]; ?>
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
?>

