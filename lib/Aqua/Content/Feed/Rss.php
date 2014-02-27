<?php
namespace Aqua\Content\Feed;

use Aqua\Content\ContentData;
use Aqua\Core\App;
use Aqua\Core\L10n;

class Rss
{
	/**
	 * @var string
	 */
	public $name;
	/**
	 * @var string
	 */
	public $title;
	/**
	 * @var string
	 */
	public $link;
	/**
	 * @var string
	 */
	public $description;
	/**
	 * @var int
	 */
	public $lastBuildDate;
	/**
	 * @var array
	 */
	public $image;
	/**
	 * @var string
	 */
	public $copyright;
	/**
	 * @var int
	 */
	public $ttl;
	/**
	 * @var array
	 */
	public $categories = array();
	/**
	 * @var \Aqua\Content\Feed\RssItem[]
	 */
	public $items = array();
	/**
	 * @var bool
	 */
	public $inCache = false;

	/**
	 * @param string $name
	 */
	public function __construct($name)
	{
		$this->name          = $name;
		$this->lastBuildDate = time();
	}

	/**
	 * @param string $title
	 * @return \Aqua\Content\Feed\Rss
	 */
	public function title($title)
	{
		$this->title = $title;

		return $this;
	}

	/**
	 * @param string $link
	 * @return \Aqua\Content\Feed\Rss
	 */
	public function link($link)
	{
		$this->link = $link;

		return $this;
	}

	/**
	 * @param string $description
	 * @return \Aqua\Content\Feed\Rss
	 */
	public function description($description)
	{
		$this->description = $description;

		return $this;
	}

	/**
	 * @param string      $url
	 * @param string      $title
	 * @param string      $link
	 * @param string|null $description
	 * @param int|null    $height
	 * @param int|null    $width
	 * @return \Aqua\Content\Feed\Rss
	 */
	public function image($url, $title, $link, $description = null, $height = null, $width = null)
	{
		$this->image = array(
			'url'         => $url,
			'title'       => $title,
			'link'        => $link,
			'description' => $description,
			'height'      => $height,
			'width'       => $width,
		);

		return $this;
	}

	/**
	 * @param string $copyright
	 * @return \Aqua\Content\Feed\Rss
	 */
	public function copyright($copyright)
	{
		$this->copyright = $copyright;

		return $this;
	}

	/**
	 * @param int $ttl
	 * @return \Aqua\Content\Feed\Rss
	 */
	public function ttl($ttl)
	{
		$this->ttl = $ttl;

		return $this;
	}

	/**
	 * @param string|array $category
	 * @param string|null  $domain
	 * @return \Aqua\Content\Feed\Rss
	 */
	public function category($category, $domain = null)
	{
		if(!is_array($category)) {
			if($domain) {
				$category = array( $category => $domain );
			} else {
				$category = array( $category );
			}
		}
		$this->categories = array_merge($this->categories, $category);

		return $this;
	}

	/**
	 * @param ContentData $content
	 * @param string      $link
	 * @return \Aqua\Content\Feed\Rss
	 */
	public function addItem(ContentData $content, $link)
	{
		$this->items[] = new RssItem($content, $link);

		return $this;
	}

	/**
	 * @param int $id
	 * @return \Aqua\Content\Feed\RssItem|null
	 */
	public function getItem($id)
	{
		return (array_key_exists($id, $this->items) ? $this->items[$id] : null);
	}

	/**
	 * @param string $name
	 * @return \Aqua\Content\Feed\RssItem|null
	 */
	public function getItemBySlug($name)
	{
		foreach($this->items as $item) {
			if($item->slug === $name) {
				return $item;
			}
		}

		return null;
	}

	/**
	 * @return \DOMDocument
	 */
	public function build()
	{
		$dom = new \DOMDocument('1.0', 'UTF-8');
		$dom->formatOutput = true;
		$rss = $dom->createElement('rss');
		$rss->setAttribute('version', '2.0');
		$channel = $dom->createElement('channel');
		$dom->appendChild($rss);
		$rss->appendChild($channel);
		$channel->appendChild($dom->createElement('title', str_replace(array('&', '<'), array('&#x26;', '&#x3C;'), $this->title)));
		$channel->appendChild($dom->createElement('link', $this->link ?: \Aqua\URL));
		$channel->appendChild($dom->createElement('language', L10n::getDefault()->code));
		$channel->appendChild($dom->createElement('lastBuildDate', date(DATE_RSS, $this->lastBuildDate)));
		if($this->description !== htmlspecialchars(strip_tags($this->description))) {
			$description = $dom->createElement('description');
			$description->appendChild($dom->createCDATASection($this->description));
			$channel->appendChild($description);
		} else {
			$channel->appendChild($dom->createElement('description', $this->description));
		}
		if($this->image) {
			$node = $dom->createElement('image');
			$node->setAttribute('url', $this->image['url']);
			$node->setAttribute('title', str_replace(array('&', '<'), array('&#x26;', '&#x3C;'), $this->image['title']));
			$node->setAttribute('link', $this->image['link']);
			if($this->image['description']) {
				$node->setAttribute('description', $this->image['description']);
			}
			if($this->image['width']) {
				$node->setAttribute('width', $this->image['width']);
			}
			if($this->image['height']) {
				$node->setAttribute('height', $this->image['height']);
			}
		}
		foreach($this->categories as $category) {
			$channel->appendChild($dom->createElement('category', str_replace(array('&', '<'), array('&#x26;', '&#x3C;'), $category)));
		}
		foreach($this->items as $item) {
			$channel->appendChild($item->build($dom));
		}

		return $dom;
	}

	/**
	 * @return \Aqua\Content\Feed\Rss
	 */
	public function save()
	{
		if($this->ttl) {
			$this->inCache = true;
			App::cache()->store("rss_{$this->name}", $this, $this->ttl * 60);
		}

		return $this;
	}

	/**
	 * @param string $name
	 * @return \Aqua\Content\Feed\Rss
	 */
	public static function get($name)
	{
		if(!($rss = App::cache()->fetch("rss_$name", null))) {
			return new self($name);
		} else {
			return $rss;
		}
	}
}
