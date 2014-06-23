<?php
namespace Aqua\Content\Filter\FeedFilter;

use Aqua\Core\App;

class AtomFeed
extends AbstractFeed
{
	public $atom;

	protected function build(Feed $feed)
	{
		$atom = $this->createElement('feed');
		$this->appendChild($atom);
		$this->atom = $atom;
		$atom->setAttribute('xmlns', 'http://www.w3.org/2005/Atom');
		$atom->appendChild($this->createElement('id', $feed->tag));
		$atom->appendChild($this->createElement('title', $feed->title));
		$atom->appendChild($this->createElement('updated', date(DATE_RFC3339, $feed->lastBuildDate)));
		if($feed->description) {
			$atom->appendChild($this->createElementCDATA('subtitle', $feed->description));
		}
		if($feed->copyright) {
			$atom->appendChild($this->createElement('rights', $feed->copyright));
		}
		if($feed->image) {
			$atom->appendChild($this->createElement('logo', $feed->image['url']));
		}
		if($feed->icon) {
			$atom->appendChild($this->createElement('icon', $feed->icon));
		}
		foreach($feed->authors as $author) {
			$atom->appendChild($this->person('author', $author));
		}
		foreach($feed->contributors as $contributor) {
			$atom->appendChild($this->person('contributor', $contributor));
		}
		foreach($feed->categories as $title => $term) {
			$category = $this->createElement('category');
			if($term) {
				$category->setAttribute('term', $term);
				$category->setAttribute('label', $title);
			} else {
				$category->setAttribute('term', $title);
			}
			$atom->appendChild($category);
		}
		$alternate = $this->createElement('link');
		$alternate->setAttribute('rel', 'alternate');
		$alternate->setAttribute('href', $feed->url);
		$atom->appendChild($alternate);
		foreach($feed->links as $linkData) {
			$link = $this->createElement('link');
			$link->setAttribute('rel', $linkData['rel']);
			$link->setAttribute('href', $linkData['href']);
			foreach(array( 'hreflang', 'title', 'length', 'type' ) as $key) {
				if(isset($linkData[$key])) {
					$link->setAttribute($key, $linkData[$key]);
				}
			}
			$atom->appendChild($link);
		}
		$generator = $this->createElement('generator', 'AquaCore');
		$generator->setAttribute('version', App::VERSION);
		$atom->appendChild($generator);
		foreach($feed->items as $item) {
			$atom->appendChild($this->buildItem($item));
		}
	}

	public function buildItem(FeedItem $item)
	{
		$entry = $this->createElement('entry');
		$entry->appendChild($this->createElement('id', $item->tag));
		$entry->appendChild($this->createElementCDATA('title', $item->title));
		$entry->appendChild($this->createElement('updated', date(DATE_RFC3339, $item->publishDate)));
		$alternate = $this->createElement('link');
		$alternate->setAttribute('rel', 'alternate');
		$alternate->setAttribute('href', $item->link);
		$entry->appendChild($alternate);
		if($item->enclosure) {
			$enclosure = $this->createElement('link');
			$enclosure->setAttribute('rel', 'enclosure');
			$enclosure->setAttribute('href', $enclosure['href']);
			$enclosure->setAttribute('type', $enclosure['type']);
			$enclosure->setAttribute('length', $enclosure['length']);
			$entry->appendChild($enclosure);
		}
		foreach($item->authors as $author) {
			$entry->appendChild($this->person('author', $author));
		}
		foreach($item->contributors as $contributor) {
			$entry->appendChild($this->person('contributor', $contributor));
		}
		if($item->summary) {
			$summary = $this->createElementCDATA('summary', $item->summary);
			$summary->setAttribute('type', $item->type);
			$entry->appendChild($summary);
		}
		$content = $this->createElementCDATA('content', $item->content);
		$content->setAttribute('type', $item->type);
		$entry->appendChild($content);

		return $entry;
	}

	public function person($name, $person)
	{
		$node = $this->createElement($name);
		$node->appendChild($this->createElement('name', $person['name']));
		if($person['url']) {
			$node->appendChild($this->createElement('uri', $person['url']));
		}
		if($person['email']) {
			$node->appendChild($this->createElement('email', $person['email']));
		}
		return $node;
	}
}
 