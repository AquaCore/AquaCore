<?php
namespace Aqua\Content\Feed;

use Aqua\Content\ContentData;
use Aqua\Content\ContentType;

class RssItem
{
	/**
	 * @var int
	 */
	public $id;
	/**
	 * @var int
	 */
	public $contentTypeId;
	/**
	 * @var string
	 */
	public $slug;
	/**
	 * @var string
	 */
	public $title;
	/**
	 * @var string
	 */
	public $content;
	/**
	 * @var string
	 */
	public $link;
	/**
	 * @var string
	 */
	public $comments;
	/**
	 * @var int
	 */
	public $publishDate;
	/**
	 * @var array|null
	 */
	public $enclosure;
	/**
	 * @var array
	 */
	public $categories = array();

	/**
	 * @param ContentData $content
	 * @param string      $link
	 */
	public function __construct(ContentData $content, $link)
	{
		$this->id            = $content->uid;
		$this->contentTypeId = $content->contentType->id;
		$this->title         = $content->title;
		$this->slug          = $content->slug;
		$this->publishDate   = $content->publishDate;
		$this->content       = ($content->shortContent ? : $content->pages[0]);
		$this->link          = $link;
		$feedback            = array( $this );
		$content->applyFilters('rss', $feedback);
	}

	/**
	 * @param string $url
	 * @param string $type
	 * @param int    $length
	 * @return \Aqua\Content\Feed\RssItem
	 */
	public function enclosure($url, $type, $length)
	{
		$this->enclosure = array(
			'url'    => $url,
			'type'   => $type,
			'length' => $length
		);

		return $this;
	}

	/**
	 * @param \DomDocument $dom
	 * @return \DOMElement
	 */
	public function build(\DomDocument $dom)
	{
		$node = $dom->createElement('item');
		$node->appendChild($dom->createElement('title', str_replace(array('&', '<'), array('&#x26;', '&#x3C;'), $this->title)));
		$node->appendChild($dom->createElement('link', $this->link));
		$node->appendChild($dom->createElement('guid', $this->slug));
		$node->appendChild($dom->createElement('pubDate', date(DATE_RSS, $this->publishDate)));
		if($this->content !== htmlspecialchars(strip_tags($this->content))) {
			$description = $dom->createElement('description');
			$description->appendChild($dom->createCDATASection($this->content));
			$node->appendChild($description);
		} else {
			$node->appendChild($dom->createElement('description', $this->content));
		}
		if($this->comments) {
			$node->appendChild($dom->createElement('comments', $this->comments));
		}
		if(!empty($this->enclosure)) {
			$enclosure = $dom->createElement('enclosure');
			$enclosure->setAttribute('url', $this->enclosure['url']);
			$enclosure->setAttribute('type', $this->enclosure['type']);
			$enclosure->setAttribute('length', $this->enclosure['length']);
			$node->appendChild($enclosure);
		}
		foreach($this->categories as $category) {
			$node->appendChild($dom->createElement('category', str_replace(array('&', '<'), array('&#x26;', '&#x3C;'), $category)));
		}

		return $node;
	}

	/**
	 * @return \Aqua\Content\ContentData|null
	 */
	public function content()
	{
		if(!($ctype = ContentType::getContentType($this->contentTypeId)) ||
		   !($content = $ctype->get($this->id))) {
			return null;
		} else {
			return $content;
		}
	}
}
