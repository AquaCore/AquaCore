<?php
use Aqua\Content\ContentType;
use Aqua\Core\App;

ContentType::rebuildCache();
foreach(ContentType::contentTypes() as $cType) {
	if($cType->hasFilter('FeaturedFilter')) {
		App::cache()->delete("featured_content.{$cType->id}");
	}
}
