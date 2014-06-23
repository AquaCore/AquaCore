<?php
namespace Aqua\Content\Filter\FeedFilter;

use Aqua\Content\ContentData;

class Feed
{
	/**
	 * @var string
	 */
	public $tag;
	/**
	 * @var string
	 */
	public $title;
	/**
	 * @var string
	 */
	public $language;
	/**
	 * @var string
	 */
	public $url;
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
	public $icon;
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
	 * @var array
	 */
	public $links = array();
	/**
	 * @var array
	 */
	public $authors = array();
	/**
	 * @var array
	 */
	public $contributors = array();
	/**
	 * @var \Aqua\Content\Filter\FeedFilter\FeedItem[]
	 */
	public $items = array();

	/**
	 * @param string $tag
	 */
	public function __construct($tag)
	{
		$this->tag           = $tag;
		$this->lastBuildDate = time();
	}

	/**
	 * @param string $title
	 * @return \Aqua\Content\Filter\FeedFilter\Feed
	 */
	public function title($title)
	{
		$this->title = $title;

		return $this;
	}

	/**
	 * @param string $link
	 * @return \Aqua\Content\Filter\FeedFilter\Feed
	 */
	public function url($link)
	{
		$this->url = $link;

		return $this;
	}

	/**
	 * @param array $links
	 * @return \Aqua\Content\Filter\FeedFilter\Feed
	 */
	public function addLink(array $links)
	{
		foreach($links as $key => $link) {
			if(is_array($link)) {
				$link += array( 'rel' => '', 'href' => '' );
				$this->links[] = $link;
				unset($links[$key]);
			}
		}
		if(!empty($links)) {
			$links+= array( 'rel'  => '', 'href' => '', );
			$this->links[] = $links;
		}

		return $this;
	}

	/**
	 * @param string $description
	 * @return \Aqua\Content\Filter\FeedFilter\Feed
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
	 * @return \Aqua\Content\Filter\FeedFilter\Feed
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
	 * @param string $url
	 * @return \Aqua\Content\Filter\FeedFilter\Feed
	 */
	public function icon($url)
	{
		$this->icon = $url;

		return $this;
	}

	/**
	 * @param string $copyright
	 * @return \Aqua\Content\Filter\FeedFilter\Feed
	 */
	public function copyright($copyright)
	{
		$this->copyright = $copyright;

		return $this;
	}

	/**
	 * @param int $ttl
	 * @return \Aqua\Content\Filter\FeedFilter\Feed
	 */
	public function ttl($ttl)
	{
		$this->ttl = $ttl;

		return $this;
	}

	/**
	 * @param string|array $category
	 * @param string|null  $domain
	 * @return \Aqua\Content\Filter\FeedFilter\Feed
	 */
	public function addCategory($category, $domain = null)
	{
		if(is_array($category)) {
			$this->categories = array_merge($this->categories, $category);
		} else {
			$this->categories[$category] = $domain;
		}

		return $this;
	}

	/**
	 * @param string      $author
	 * @param string|null $email
	 * @param string|null $url
	 * @return \Aqua\Content\Filter\FeedFilter\Feed
	 */
	public function addAuthor($author, $email = null, $url = null)
	{
		if(is_array($author)) {
			foreach($author as &$a) {
				if(is_array($a)) {
					$a += array(
						'name'  => '',
					    'email' => null,
					    'url'   => null
					);
				} else {
					$a = array(
						'name'  => $a,
					    'email' => null,
					    'url'   => null
					);
				}
			}
			$this->authors = array_merge($this->authors, $author);
		} else {
			$this->authors[] = array(
				'name'  => $author,
			    'email' => $email,
			    'url'   => $url
			);
		}

		return $this;
	}

	/**
	 * @param string      $contributor
	 * @param string|null $email
	 * @param string|null $url
	 * @return \Aqua\Content\Filter\FeedFilter\Feed
	 */
	public function addContributor($contributor, $email = null, $url = null)
	{
		if(is_array($contributor)) {
			foreach($contributor as &$a) {
				if(is_array($a)) {
					$a += array(
						'name'  => '',
					    'email' => null,
					    'url'   => null
					);
				} else {
					$a = array(
						'name'  => $a,
					    'email' => null,
					    'url'   => null
					);
				}
			}
			$this->contributors = array_merge($this->authors, $contributor);
		} else {
			$this->contributors[] = array(
				'name'  => $contributor,
			    'email' => $email,
			    'url'   => $url
			);
		}

		return $this;
	}

	/**
	 * @param ContentData $content
	 * @param int|null    $length
	 * @return \Aqua\Content\Filter\FeedFilter\Feed
	 */
	public function addItem(ContentData $content, $length = null)
	{
		$this->items[$content->uid] = new FeedItem($content, $length);

		return $this;
	}

	/**
	 * @param int $id
	 * @return \Aqua\Content\Filter\FeedFilter\FeedItem|null
	 */
	public function getItem($id)
	{
		return (array_key_exists($id, $this->items) ? $this->items[$id] : null);
	}

	/**
	 * @param string $name
	 * @return \Aqua\Content\Filter\FeedFilter\FeedItem|null
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
}
