<?php
namespace Aqua\Content\Filter;

use Aqua\Content\AbstractFilter;
use Aqua\Content\ContentData;
use Aqua\Core\App;
use Aqua\SQL\Search;

class FeaturedFilter
extends AbstractFilter
{
	/**
	 * @var array
	 */
	public $featured;

	const OPT_FEATURED = 1024;

	public function beforeUpdate(ContentData $content, array &$data)
	{
		$this->setFeatured($content->options, $data);
	}

	public function afterUpdate(ContentData $content, array $data, array &$values)
	{
		$this->rebuildCacheUpdated($content);
	}

	public function beforeCreate(array &$data)
	{
		$this->setFeatured(0, $data);
	}

	public function afterCreate(ContentData $content, array &$data)
	{
		$this->rebuildCacheUpdated($content);
	}

	public function afterDelete(ContentData $content)
	{
		$this->rebuildCacheUpdated($content);
	}

	/**
	 * @param int   $options
	 * @param array $data
	 */
	public function setFeatured($options, array &$data)
	{
		if(isset($data['featured']) && $data['featured']) {
			$data['options'] = $options | self::OPT_FEATURED;
		} else {
			$data['options'] = $options & ~self::OPT_FEATURED;
		}
	}

	/**
	 * @param \Aqua\Content\ContentData $content
	 */
	public function rebuildCacheUpdated(ContentData $content)
	{
		if($content->options & self::OPT_FEATURED && ($limit = $this->limit())) {
			$this->rebuildCache($limit);
		}
	}

	/**
	 * @return int
	 */
	public function limit()
	{
		if($this->featured === null &&
		   !($this->featured = App::cache()->fetch('featured_content.' . $this->contentType->id, null))
		) {
			return 0;
		} else {
			return count($this->featured);
		}
	}

	/**
	 * @param \Aqua\Content\ContentData $content
	 * @return int
	 */
	public function contentData_isFeatured(ContentData $content)
	{
		return ($content->options & self::OPT_FEATURED);
	}

	/**
	 * @param int $limit
	 * @return array
	 */
	public function contentType_featured($limit = 5)
	{
		if(($this->featured === null &&
		   !($this->featured = App::cache()->fetch('featured_content.' . $this->contentType->id, null))) ||
		   count($this->featured) < $limit) {
			$this->rebuildCache($limit);
		}

		return array_filter(array_slice($this->featured, 0, $limit));
	}

	/**
	 * @param int $limit
	 */
	public function rebuildCache($limit = 5)
	{
		$this->featured = $this->contentType
			->search()
			->order(array( 'publish_date' => 'DESC' ))
			->where(array( 'options' => array( Search::SEARCH_AND, self::OPT_FEATURED ) ))
			->limit($limit)
			->query()
			->results;
		for($i = count($this->featured); $i < $limit; ++$i) {
			$this->featured[$i] = null;
		}
		App::cache()->store('featured_content.' . $this->contentType->id, $this->featured);
	}
}
