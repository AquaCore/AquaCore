<?php
namespace Aqua\Content\Filter\FeedFilter;

class RssFeed
extends AbstractFeed
{
	protected function build(Feed $feed)
	{
		$rss = $this->createElement('rss');
		$rss->setAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
		$rss->setAttribute('version', '2.0');
		$channel = $this->createElement('channel');
		$this->appendChild($rss);
		$rss->appendChild($channel);
		$channel->appendChild($this->createElement('title', $this->escape($feed->title)));
		$channel->appendChild($this->createElement('link', $feed->url));
		$channel->appendChild($this->createElement('lastBuildDate', date(DATE_RSS, $feed->lastBuildDate)));
		$channel->appendChild($this->createElementCDATA('description', $feed->description));
		if($feed->image) {
			$image = $this->createElement('image');
			$image->appendChild($this->createElement('url', $feed->image['url']));
			$image->appendChild($this->createElement('title', $this->escape($feed->image['title'])));
			$image->appendChild($this->createElement('link', $feed->image['link']));
			if($feed->image['description']) {
				$image->appendChild($this->createElement('description', $this->escape($feed->image['description'])));
			}
			if($feed->image['width']) {
				$image->appendChild($this->createElement('width', $feed->image['width']));
			}
			if($feed->image['height']) {
				$image->appendChild($this->createElement('height', $feed->image['height']));
			}
			$channel->appendChild($image);
		}
		if($feed->icon) {
			$channel->appendChild($this->createElement('atom:icon', $feed->icon));
		}
		foreach($feed->authors as $author) {
			$channel->appendChild($this->person('author', $author));
		}
		foreach($feed->contributors as $contributor) {
			$channel->appendChild($this->person('contributor', $contributor));
		}
		foreach($feed->categories as $title => $domain) {
			$category = $this->createElement('category', $title);
			if($domain) {
				$category->setAttribute('domain', $domain);
			}
			$channel->appendChild($category);
		}
		foreach($feed->links as $linkData) {
			$link = $this->createElement('atom:link');
			$link->setAttribute('rel', $linkData['rel']);
			$link->setAttribute('href', $linkData['href']);
			foreach(array( 'hreflang', 'title', 'length', 'type' ) as $key) {
				if(isset($linkData[$key])) {
					$link->setAttribute($key, $linkData[$key]);
				}
			}
			$channel->appendChild($link);
		}
		foreach($feed->items as $item) {
			$channel->appendChild($this->buildItem($item));
		}
	}

	protected function buildItem(FeedItem $item)
	{
		$node = $this->createElement('item');
		$node->appendChild($this->createElement('title', $this->escape($item->title)));
		$node->appendChild($this->createElement('link', $item->link));
		$node->appendChild($this->createElement('pubDate', date(DATE_RSS, $item->publishDate)));
		$guid = $this->createElement('guid', $item->tag);
		$guid->setAttribute('isPermalink', 'false');
		$node->appendChild($guid);
		$node->appendChild($this->createElementCDATA('description', $item->content));
		if($item->comments) {
			$node->appendChild($this->createElement('comments', $item->comments));
		}
		if(!empty($item->enclosure)) {
			$enclosure = $this->createElement('enclosure');
			$enclosure->setAttribute('url', $item->enclosure['url']);
			$enclosure->setAttribute('type', $item->enclosure['type']);
			$enclosure->setAttribute('length', $item->enclosure['length']);
			$node->appendChild($enclosure);
		}
		foreach($item->authors as $author) {
			$str = $author['name'];
			if(isset($author['email'])) {
				$str = "{$author['email']} ($str)";
			}
			$node->appendChild($this->createElement('author', $str));
		}
		foreach($item->categories as $title => $domain) {
			$category = $this->createElement('category', $title);
			if($domain) {
				$category->setAttribute('domain', $domain);
			}
			$node->appendChild($category);
		}
		return $node;
	}

	protected function escape($str)
	{
		return str_replace(array('&', '<'), array('&#x26;', '&#x3C;'), $str);
	}
}
