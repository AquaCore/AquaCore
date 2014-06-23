<?php
namespace Aqua\Content;

use Aqua\Content\Feed\RssItem;
use Aqua\Core\App;
use Aqua\Event\EventDispatcher;
use Aqua\Event\SubjectInterface;

abstract class AbstractFilter
implements SubjectInterface
{
	/**
	 * @var string
	 */
	public $name;
	/**
	 * @var \Aqua\Content\ContentType
	 */
	public $contentType;
	/**
	 * @var array
	 */
	public $options = array();
	/**
	 * @var \Aqua\Event\EventDispatcher
	 */
	protected $_dispatcher;

	/**
	 * @param \Aqua\Content\ContentType $content
	 * @param array                     $options
	 */
	public function __construct(ContentType $content, array $options)
	{
		$this->contentType = $content;
		$this->options     = $options;
		$this->_dispatcher = new EventDispatcher;
		$this->name        = get_class($this);
		if($name = strstr($this->name, '/')) {
			$this->name = substr($name, 1);
		}
		$this->init();
	}

	public function init() { }

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
		$this->options = array_filter(array_merge($this->options, $opt), function($val) {
			return ($val !== null);
		});
		$sth           = App::connection()->prepare(sprintf('
		UPDATE %s
		SET _options = :opt
		WHERE _type = :type
		AND _name = :name
		LIMIT 1
		', ac_table('content_type_filters')));
		$class         = explode('\\', get_class($this));
		$class         = end($class);
		$sth->bindValue(':opt', serialize($this->options), \PDO::PARAM_STR);
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

	public function attach($event, $listener)
	{
		$this->_dispatcher->attach("{$this->name}.$event", $listener);
		return $this;
	}

	public function detach($event, $listener)
	{
		$this->_dispatcher->detach("{$this->name}.$event", $listener);
		return $this;
	}

	public function notify($event, &$feedback = array())
	{
		array_unshift($feedback, $this->contentType);
		$result =  $this->_dispatcher->notify("{$this->name}.$event", $feedback);
		array_shift($feedback);
		return $result;
	}
}
