<?php
/**
 * @var $content      \Aqua\Content\ContentData[]
 * @var $contentCount int
 * @var $paginator    \Aqua\UI\Pagination|\Aqua\UI\PaginationPost
 * @var $contentType  \Aqua\Content\ContentType
 * @var $category     \Aqua\Content\Filter\CategoryFilter\Category
 * @var $page         \Aqua\Site\Page
 */
?>
<?php if($category->description) : ?>
	<div class="ac-category-description">
		<?php echo htmlspecialchars($category->description) ?>
	</div>
<?php endif; ?>
<?php
$tpl = new \Aqua\UI\Template;
$tpl->set('content', $content)
	->set('contentCount', $contentCount)
	->set('paginator', $paginator)
	->set('contentType', $contentType)
	->set('category', $category)
	->set('page', $page);
echo $tpl->render("content/{$contentType->key}/search", 'content/search');
