<?php
namespace Aqua\Content;

use Aqua\Content\Feed\RssItem;
use Aqua\Core\App;

abstract class AbstractFilter
{
	/**
	 * @var \Aqua\Content\ContentType
	 */
	public $contentType;
	/**
	 * @var array
	 */
	public $options;

	/**
	 * @param \Aqua\Content\ContentType $content
	 * @param array                     $options
	 */
	public function __construct(ContentType $content, array $options)
	{
		$this->contentType = $content;
		$this->options     = $options;
	}

	/**
	 * @param \Aqua\Content\ContentData $content
	 * @param array                     $data
	 * @return null|bool
	 */
	public function parseData(ContentData $content, array &$data) { return null; }

	/**
	 * @param \Aqua\Content\ContentData $content
	 * @param array                     $data
	 * @return null|bool
	 */
	public function beforeUpdate(ContentData $content, array &$data) { return null; }

	/**
	 * @param \Aqua\Content\ContentData $content
	 * @param array                     $data
	 * @param array                     $updated
	 * @return null|bool
	 */
	public function afterUpdate(ContentData $content, array $data, array &$updated) { return null; }

	/**
	 * @param array $data
	 * @return null|bool
	 */
	public function beforeCreate(array &$data) { return null; }

	/**
	 * @param \Aqua\Content\ContentData $content
	 * @param array                     $data
	 * @return null|bool
	 */
	public function afterCreate(ContentData $content, array &$data) { return null; }

	/**
	 * @param \Aqua\Content\ContentData $content
	 * @return null|bool
	 */
	public function beforeDelete(ContentData $content) { return null; }

	/**
	 * @param \Aqua\Content\ContentData $content
	 * @return null|bool
	 */
	public function afterDelete(ContentData $content) { return null; }

	/**
	 * @param \Aqua\Content\ContentData $content
	 * @param array                     $data
	 * @return null|bool
	 */
	public function forge(ContentData $content, array $data) { return null; }

	/**
	 * @param \Aqua\Content\ContentData      $content
	 * @param \Aqua\Content\Feed\RssItem     $item
	 * @return null|bool
	 */
	public function rss(ContentData $content, RssItem $item) { return null; }

	/**
	 * @param string $opt
	 * @param mixed  $default
	 * @return mixed
	 */
	public final function getOption($opt, $default = null)
	{
		if(array_key_exists($opt, $this->options)) {
			return $this->options[$opt];
		} else {
			return $default;
		}
	}

	/**
	 * @param string|array $opt
	 * @param mixed        $value
	 * @return bool
	 */
	public final function setOption($opt, $value = null)
	{
		if(!is_array($opt)) {
			$opt = array( $opt => $value );
		}
		$this->options = array_filter(array_merge($this->options, $opt));
		$tbl           = ac_table('content_type_filters');
		$sth           = App::connection()->prepare("
		UPDATE `$tbl`
		SET _options = :opt
		WHERE _type = :type
		AND _name = :name
		LIMIT 1
		");
		$class         = explode('\\', get_class($this));
		$class         = end($class);
		$sth->bindValue(':opt', serialize($this->options), \PDO::PARAM_LOB);
		$sth->bindValue(':type', $this->contentType->id, \PDO::PARAM_INT);
		$sth->bindValue(':name', $class, \PDO::PARAM_INT);
		$sth->execute();
		if($sth->rowCount()) {
			ContentType::rebuildCache();

			return true;
		} else {
			return false;
		}
	}
}
