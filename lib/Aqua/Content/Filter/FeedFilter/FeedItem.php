<?php
namespace Aqua\Content\Filter\FeedFilter;

use Aqua\Content\ContentData;
use Aqua\Content\ContentType;
use Aqua\Core\App;

class FeedItem
{
	/**
	 * @var int
	 */
	public $tag;
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
	public $summary;
	/**
	 * @var string
	 */
	public $content;
	/**
	 * @var string
	 */
	public $type = 'html';
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
	 * @var int
	 */
	public $lastEditDate;
	/**
	 * @var array|null
	 */
	public $enclosure;
	/**
	 * @var array
	 */
	public $categories = array();
	/**
	 * @var array
	 */
	public $authors = array();
	/**
	 * @var array
	 */
	public $contributors = array();

	/**
	 * @param ContentData $content
	 * @param $length
	 */
	public function __construct(ContentData $content, $length = null)
	{
		$this->tag           = sprintf('tag:%s,%s:%d', \Aqua\DOMAIN, date('Y-m-d', $content->publishDate), $content->uid);
		$this->id            = $content->uid;
		$this->contentTypeId = $content->contentType->id;
		$this->title         = $content->title;
		$this->slug          = $content->slug;
		$this->publishDate   = $content->publishDate;
		$this->lastEditDate  = $content->editDate;
		$this->link          = $content->contentType->url(array(
			'protocol' => (App::settings()->get('ssl', 0) >= 2 ? 'https://' : 'http://'),
			'path'     => array( $content->slug )
		), false);
		$this->authors[]     = array(
			'name'  => $content->author()->displayName,
		    'email' => null,
		    'url'   => null
		);
		if($length) {
			$this->content = $content->truncate($length, '...');
			if($content->shortContent && strlen($content->shortContent) < strlen($this->content)) {
				$this->summary = $content->shortContent;
			}
		} else {
			$this->content = implode('<p style="page-break-before:always; border-bottom: 1px dotted;"></p>', $content->pages);
		}
	}

	/**
	 * @param string $url
	 * @param string $type
	 * @param int    $length
	 * @return \Aqua\Content\Filter\FeedFilter\FeedItem
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
