<?php
use Aqua\Content\Page;
use Aqua\UI\Menu;
/**
 * @var $paginator \Aqua\UI\Pagination
 * @var $content   \Aqua\Content\Adapter\Page
 * @var $page      \Page\Main\Page
 * @var $rating    \Aqua\UI\Template
 */
$title = '';
if($parent = $content->parent()) {
	$title.= '<a class="ac-page-parent" href="' . ac_build_url(array( 'path' => array( 'page', urlencode($parent->slug) ) ));
	$title.='" style="float: left"><div class="ac-page-parent-icon"></div></a>';
}
$title.= '<a href="' . ac_build_url(array( 'path' => array( 'page', urlencode($content->slug) ) )) . '" style="float: left">';
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
					'url' => ac_build_url(array( 'path' => array( 'page', urlencode($child->slug) ) ))
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
