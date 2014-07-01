<?php
namespace Aqua\Content\Filter;

use Aqua\Content\AbstractFilter;
use Aqua\Content\Filter\CategoryFilter\Category;
use Aqua\Content\Filter\FeedFilter\AtomFeed;
use Aqua\Content\Filter\FeedFilter\Feed;
use Aqua\Content\Filter\FeedFilter\RssFeed;
use Aqua\Core\App;

class FeedFilter
extends AbstractFilter
{
	public function ContentType_rssFeed(Category $category = null)
	{
		return $this->xml('rss', $category);
	}

	public function ContentType_atomFeed(Category $category = null)
	{
		return $this->xml('atom', $category);
	}

	protected function xml($type, Category $category = null)
	{
		$limit = $this->getOption('limit', 10);
		if($category && $category->contentTypeId !== $this->contentType->id) {
			$category = null;
		}
		$cacheKey = "$type-feed.{$this->contentType->id}";
		if($category) { $cacheKey.= ".{$category->id}"; }
		if($xml = App::cache()->fetch($cacheKey, null)) {
			return $xml;
		}
		$feed = $this->ContentType_feed($limit, $category);
		$args = array( $type );
		if($category) { $args[] = $category->slug; }
		$feed->addLink(array(
			'protocol' => (App::settings()->get('ssl', 0) >= 2 ? 'https://' : 'http://'),
			'type'     => "application/$type+xml",
			'rel'      => 'self',
			'href'     => $this->contentType->url(array(
				'action' => 'feed',
				'arguments' => $args
			))
		));
		$class = sprintf('\Aqua\Content\Filter\FeedFilter\%sFeed', ucfirst($type));
		$dom = new $class($feed);
		$feedback = array( $dom );
		$this->notify($type, $feedback);
		$xml = array(
			'lastModified' => time(),
			'xml'          => $dom->saveXML()
		);
		if($this->ttl()) {
			App::cache()->store($cacheKey, $xml, $this->ttl());
		}
		return $xml;
	}

	public function ContentType_feed($limit, Category $category = null)
	{
		if($limit === null) {
			$limit = $this->getOption('limit', 10);
		}
		if($category && $category->contentTypeId !== $this->contentType->id) {
			$category = null;
		}
		$protocol = (App::settings()->get('ssl', 0) >= 2 ? 'https://' : 'http://');
		$tag = 'tag:' . \Aqua\DOMAIN . ':' . $this->contentType->id;
		if($category) { $tag.= ":{$category->id}"; }
		$title = $this->getOption('title', '');
		$feed = new Feed($tag);
		$feedback = array( $feed );
		$this->notify('create', $feedback);
		$feed->copyright($this->getOption('copyright'))
			->ttl($this->getOption('ttl', 10))
			->addCategory(array_fill_keys($this->getOption('categories', array()), null));
		if($this->getOption('icon')) {
			$feed->icon($protocol . \Aqua\DOMAIN . $this->getOption('icon'));
		}
		if($category) {
			$title.= " - {$category->name}";
			$feed->title($title)
				->url($this->contentType->url(array(
					'protocol' => $protocol,
					'path'     => array( 'category', $category->slug )
				), false))
				->description($category->description)
				->addCategory($category->name, $category->slug);
			if($category->image) {
				if($category->isImageUploaded) {
					$feed->image($protocol . \Aqua\DOMAIN . $category->image, $title, $feed->url);
				} else {
					$feed->image($category->imageUrl, $title, $feed->url);
				}
			}
			$search = $this->contentType->categorySearch()->where(array( 'category_id' => $category->id ));
		} else {
			$feed->title($title)
				->url($this->contentType->url(array( 'protocol' => $protocol ), false))
				->description($this->getOption('description', ''));
			if($this->getOption('image')) {
				$feed->image($protocol . \Aqua\DOMAIN . $this->getOption('image'), $title, $feed->url);
			}
			$search = $this->contentType->search();
		}
		if($limit !== 0) {
			$search->order(array( 'c._publish_date' => 'DESC' ))
			       ->limit($limit)
			       ->query();
			foreach($search as $item) {
				$feed->addItem($item, $this->getOption('length', null));
				$feedback = array( $item, $feed->getItem($item->id) );
				$this->notify('add-item', $feedback);
			}
		}

		return $feed;
	}

	public function ttl()
	{
		return $this->getOption('ttl', 1440) * 60;
	}

	public function clearCache($category = null)
	{
		if($this->contentType->hasFilter('CategoryFilter')) {
			if($category instanceof Category) {
				$category = array( $category );
			} else if($category === null || $category === true) {
				$category = $this->contentType->categories();
			}
			if(is_array($category)) {
				foreach($category as $cat) {
					App::cache()->delete(sprintf('atom-feed.%d.%d', $this->contentType->id, $cat->id));
					App::cache()->delete(sprintf('rss-feed.%d.%d', $this->contentType->id, $cat->id));
				}
			}
			if($category === true) {
				return;
			}
		}
		App::cache()->delete(sprintf('atom-feed.%d', $this->contentType->id));
		App::cache()->delete(sprintf('rss-feed.%d', $this->contentType->id));
	}
}
