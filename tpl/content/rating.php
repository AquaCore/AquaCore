<?php

/**
 * @var $content \Aqua\Content\ContentData
 * @var $page    \Aqua\Site\Page
 */

use Aqua\Core\App;
use Aqua\UI\ScriptManager;

$max_weight = (int)$content->contentType->filter('RatingFilter')->getOption('maxweight', 10);
if($max_weight % 2) {
	$weight = $max_weight;
} else {
	$weight = $max_weight / 2;
}
$avg = $content->ratingAverage();
if((!$content->contentType->hasFilter('ArchiveFilter') ||
    !$content->isArchived()) &&
   App::user()->role()->hasPermission('rate') &&
   !$content->forged) {
	$page->theme->addSettings('contentRating', array(
			'contentType'   => $content->contentType->id,
			'contentId'     => $content->uid,
			'averageRating' => $avg,
			'userRating'    => $content->getRating(App::user()->account),
			'maxWeight'     => $max_weight
		));
	$page->theme->footer->enqueueScript(ScriptManager::script('aquacore.rating'));
	$page->theme->footer->enqueueScript('tpl.content-rating')
		->type('text/javascript')
		->append('new AquaCore.Rating($(".ac-content-rating"), AquaCore.settings.contentRating);');
}
?>
<div class="ac-content-rating">
	<div class="ac-content-rating-avg" style="width: <?php echo $weight * 16 ?>px">
		<div class="ac-content-rating-hover" style="width: 0"></div>
		<div class="ac-content-rating-fill"
		     style="width: <?php echo($avg === 0 ? 0 : $avg / ($max_weight / 100)) ?>%"></div>
	</div>
</div>
