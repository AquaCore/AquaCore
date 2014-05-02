<?php
namespace Aqua\Content;

use Aqua\Core\App;
use Aqua\Event\Event;
use Aqua\Event\EventDispatcher;
use Aqua\Event\SubjectInterface;
use Aqua\SQL\Query;
use Aqua\SQL\Search;

class ContentType
implements \Serializable, SubjectInterface
{
	/**
	 * @var int
	 */
	public $id;
	/**
	 * @var string
	 */
	public $key;
	/**
	 * @var string
	 */
	public $name;
	/**
	 * @var string
	 */
	public $adapter;
	/**
	 * @var string
	 */
	public $table;
	/**
	 * @var bool
	 */
	public $listing;
	/**
	 * @var bool
	 */
	public $feed;
	/**
	 * @var int
	 */
	public $pluginId;
	/**
	 * @var \Aqua\Content\AbstractFilter[]
	 */
	public $filters = array();
	/**
	 * @var array
	 */
	public $fields = array();
	/**
	 * @var \Aqua\Content\ContentData[]
	 */
	public $content = array();
	/**
	 * @var \Aqua\Event\EventDispatcher
	 */
	public $dispatcher;
	/**
	 * @var \Aqua\Http\Uri
	 */
	protected $_uri;
	/**
	 * @var \Aqua\Content\ContentType[]
	 */
	public static $contentTypes;

	const CACHE_KEY = 'content_types';

	const CTYPE_POST = 1;
	const CTYPE_PAGE = 2;

	public function __destruct()
	{
		unset($this->filters);
	}

	public function serialize()
	{
		$filters = array();
		foreach($this->filters as $name => $filter) {
			$filters[$name] = array( get_class($filter), $filter->options );
		}

		return serialize(array(
				$this->id,
				$this->key,
				$this->name,
				$this->adapter,
				$this->fields,
				$this->listing,
				$this->feed,
				$this->pluginId,
				$filters
			));
	}

	public function unserialize($serialized)
	{
		$this->dispatcher = new EventDispatcher;
		list(
			$this->id,
			$this->key,
			$this->name,
			$this->adapter,
			$this->fields,
			$this->listing,
			$this->feed,
			$this->pluginId,
			$filters
			) = unserialize($serialized);
		foreach($filters as $name => $filter) {
			list($class, $options) = $filter;
			$this->filters[$name] = new $class($this, $options);
		}
	}

	/**
	 * @param string|int $id
	 * @param string     $type
	 * @return \Aqua\Content\ContentData|null
	 */
	public function get($id, $type = 'id')
	{
		if($type === 'id' && isset($this->content[$id])) {
			return $this->content[$id];
		} else {
			foreach($this->content as $content) {
				if($content->slug === $id) {
					return $content;
				}
			}
		}
		$select = Query::select(App::connection())
			->columns(array(
				'uid'           => 'c._uid',
				'type'          => 'c._type',
				'author'        => 'c._author_id',
				'last_editor'   => 'c._editor_id',
				'publish_date'  => 'UNIX_TIMESTAMP(c._publish_date)',
				'edit_date'     => 'UNIX_TIMESTAMP(c._edit_date)',
				'status'        => 'c._status',
				'slug'          => 'c._slug',
				'title'         => 'c._title',
				'protected'     => 'c._protected',
				'options'       => 'c._options',
				'content'       => 'c._content',
				'plain_content' => 'c._plain_content'
			))
			->from(ac_table('content'), 'c')
			->limit(1)
			->parser(array( __CLASS__, 'parseContentSql' ));
		if($this->table) {
			$select->innerJoin($this->table, 't._uid = c._uid', 't');
			foreach($this->fields as $alias => $field) {
				list($name, $type) = $field;
				if($type === 'date') {
					$select->columns(array( $alias => "UNIX_TIMESTAMP(t.`{$name}`)" ));
				} else {
					$select->columns(array( $alias => "t.`{$name}`" ));
				}
			}
		}
		switch($type) {
			case 'id':
			case 'uid':
				$select->where(array( 'c._uid' => $id ));
				break;
			case 'slug':
				$select->where(array( 'c._type' => $this->id, 'c._slug' => $id ));
				break;
			default:
				return null;
		}
		$select->query();

		return ($select->valid() ? $select->current() : null);
	}

	/**
	 * @return \Aqua\SQL\Search
	 */
	public function search()
	{
		$search                  = new Search(App::connection());
		$where                   = array(
			'uid'          => 'c._uid',
			'type'         => 'c._type',
			'author'       => 'c._author_id',
			'last_editor'  => 'c._editor_id',
			'publish_date' => 'c._publish_date',
			'edit_date'    => 'c._edit_date',
			'status'       => 'c._status',
			'slug'         => 'c._slug',
			'protected'    => 'c._protected',
			'options'      => 'c._options',
		);
		$columns                  = $where;
		$columns['title']         = 'c._title';
		$columns['content']       = 'c._content';
		$columns['plain_content'] = 'c._plain_content';
		$columns['publish_date']  = 'UNIX_TIMESTAMP(c._publish_date)';
		$columns['edit_date']     = 'UNIX_TIMESTAMP(c._edit_date)';
		if($this->table) {
			foreach($this->fields as $alias => $field) {
				list($name, $type) = $field;
				switch($type) {
					case 'date':
						$where[$alias]   = "t.`$name`";
						$columns[$alias] = "UNIX_TIMESTAMP(t.`$name`)";
						break;
					case 'blob':
					case 'text':
						$columns[$alias] = "t.`$name`";
						break;
					default:
						$where[$alias]   = "t.`$name`";
						$columns[$alias] = "t.`$name`";
						break;
				}
			}
			$search->rightJoin($this->table, 't._uid = c._uid', 't');
		}
		$search
			->columns($columns)
			->whereOptions($where)
			->from(ac_table('content'), 'c')
			->groupBy('c._uid')
			->where(array( 'type' => $this->id, 'AND' ))
			->parser(array( __CLASS__, 'parseContentSql' ));

		return $search;
	}

	/**
	 * @param array $data
	 * @return \Aqua\Content\ContentData
	 */
	public function forge(array $data)
	{
		if($class = $this->adapter) {
			$content = new $class;
		} else {
			$content = new ContentData;
		}
		$data += array(
			'title'        => '',
			'slug'         => '',
			'content'      => '',
			'status'       => 0,
			'author'       => 0,
			'last_editor'  => null,
			'publish_date' => time(),
			'edit_date'    => null,
			'protected'    => false,
			'options'      => 0
		);
		$content->forged       = true;
		$content->contentType  = & $this;
		$content->title        = $data['title'];
		$content->content      = $data['content'];
		$content->status       = $data['status'];
		$content->authorId     = $data['author'];
		$content->slug         = $data['slug'];
		$content->protected    = $data['protected'];
		$content->options      = $data['options'];
		$content->publishDate  = $data['publish_date'];
		$content->editDate     = $data['edit_date'];
		$content->lastEditorId = $data['last_editor'];
		$content->contentPlain = html_entity_decode(strip_tags($data['content']), ENT_QUOTES, 'UTF-8');
		ac_parse_content($content->content, $content->pages, $content->shortContent);
		foreach($this->fields as $alias => $field) {
			if(array_key_exists($alias, $data)) {
				$content->data[$alias] = $data[$alias];
			}
		}
		$content->ready();
		$feedback = array( $content, $data );
		$this->applyFilters('forge', $feedback);

		return $content;
	}

	/**
	 * @param array $options
	 * @return \Aqua\Content\ContentData|null
	 */
	public function create(array $options)
	{
		$feedback = array( &$options );
		$options += array(
			'author'       => 1,
			'publish_date' => time(),
			'title'        => '',
			'content'      => '',
			'protected'    => false,
			'options'      => 0,
			'status'       => 0
		);
		if(!isset($options['plain_content'])) {
			$options['plain_content'] = html_entity_decode(strip_tags($options['content']), ENT_QUOTES, 'UTF-8');
		}
		if(!isset($options['slug'])) {
			$options['slug'] = $this->slug($options['title']);
		}
		if($this->applyFilters('beforeCreate', $feedback) === false) {
			return null;
		}
		$tbl = ac_table('content');
		$sth = App::connection()->prepare("
		INSERT INTO `$tbl` (_type, _author_id, _publish_date, _title, _slug, _content, _plain_content, _protected, _options, _status)
		VALUES (:type, :author, :publish, :title, :slug, :content, :pcontent, :protected, :options, :status)
		");
		$sth->bindValue(':type', $this->id, \PDO::PARAM_INT);
		$sth->bindValue(':author', $options['author'], \PDO::PARAM_INT);
		$sth->bindValue(':publish', date('Y-m-d H:i:s', $options['publish_date']), \PDO::PARAM_STR);
		$sth->bindValue(':title', $options['title'], \PDO::PARAM_LOB);
		$sth->bindValue(':slug', $options['slug'], \PDO::PARAM_STR);
		$sth->bindValue(':content', $options['content'], \PDO::PARAM_LOB);
		$sth->bindValue(':pcontent', $options['plain_content'], \PDO::PARAM_LOB);
		$sth->bindValue(':protected', $options['protected'] ? 'y' : 'n', \PDO::PARAM_STR);
		$sth->bindValue(':options', $options['options'], \PDO::PARAM_INT);
		$sth->bindValue(':status', $options['status'], \PDO::PARAM_INT);
		$sth->execute();
		$id = App::connection()->lastInsertId();
		if($this->table) {
			$columns = $values = array();
			foreach($this->fields as $name => $field) {
				list($alias, $type) = $field;
				if(!array_key_exists($alias, $options)) {
					continue;
				}
				if($options[$alias] === null) {
					$values[$name]  = null;
					$columns[$name] = \PDO::PARAM_NULL;
				} else {
					switch($type) {
						case 'number':
							$columns[$name] = \PDO::PARAM_INT;
							break;
						case 'blob':
						case 'lob':
							$columns[$name] = \PDO::PARAM_LOB;
							break;
						default:
							$columns[$name] = \PDO::PARAM_STR;
							break;
					}
					$values[$name] = $options[$alias];
				}
			}
			$columns['_uid'] = \PDO::PARAM_INT;
			$values['_uid']  = $id;
			$insert_columns  = '`' . implode('`, `', array_keys($columns)) . '`';
			$insert_values   = str_repeat('?, ', count($columns));
			$sth             =
				App::connection()->prepare("INSERT INTO `{$this->table}` ($insert_columns) VALUES ($insert_values)");
			$i               = 0;
			foreach($columns as $name => $type) {
				$sth->bindValue(++$i, $values[$name], $type);
			}
			$sth->execute();
		}
		$content = $this->get($id);
		array_unshift($feedback, $content);
		$this->applyFilters('afterCreate', $feedback);
		$this->notify('create', $feedback);

		return $content;
	}

	/**
	 * @param string   $title
	 * @param int|null $id
	 * @return string
	 */
	public function slug($title, $id = null)
	{
		if($title === '') {
			$title = __('application', 'untitled');
		}
		$slug = ac_slug($title, 250);
		$select = Query::select(App::connection())
			->columns(array( 'slug' => '_slug' ))
			->where(array(
				'_type' => $this->id,
				'_slug' => array( Search::SEARCH_LIKE, addcslashes($slug, '%_\\') . '%' )
			))
			->from(ac_table('content'));
		if($id) {
			$select->where(array( '_uid' => array( Search::SEARCH_DIFFERENT, $id ) ));
		}
		$select->query();
		return ac_slug_available($slug, $select->getColumn('slug'));
	}

	public function attach($event, $listener)
	{
		$this->dispatcher->attach("content.$event", $listener);

		return $this;
	}

	public function detach($event, $listener)
	{
		$this->dispatcher->detach("content.$event", $listener);

		return $this;
	}

	public function notify($event, &$feedback = array())
	{
		return $this->dispatcher->notify("content.$event", $feedback);
	}

	/**
	 * @param string $filter
	 * @return bool
	 */
	public function hasFilter($filter)
	{
		return array_key_exists(strtolower($filter), $this->filters);
	}

	/**
	 * @param string $filter
	 * @return \Aqua\Content\AbstractFilter|null
	 */
	public function filter($filter)
	{
		$filter = strtolower($filter);

		return (array_key_exists($filter, $this->filters) ? $this->filters[$filter] : null);
	}

	/**
	 * @param string $method
	 * @param array  $arguments
	 * @return mixed
	 */
	public function applyFilters($method, array &$arguments)
	{
		$ret = null;
		foreach($this->filters as $filter) {
			$ret = call_user_func_array(array( $filter, $method ), $arguments);
			if($ret === false) {
				return false;
			}
		}

		return $ret;
	}

	public function url(array $options = array(), $admin = null)
	{
		if($admin === null) {
			$admin = \Aqua\PROFILE === 'ADMINISTRATION';
		}
		if(isset($options['path'])) {
			$options['path'] = array_merge(array( $this->key ), $options['path']);
		} else {
			$options['path'] = array( $this->key );
		}
		$options['base_dir'] = \Aqua\DIR;
		if($admin) {
			$options['base_dir'].= '/admin';
		}
		return ac_build_url($options);
	}

	public function __call($method, array $arguments)
	{
		foreach($this->filters as $filter) {
			if(method_exists($filter, "contentType_$method")) {
				return call_user_func_array(array( $filter, "contentType_$method" ), $arguments);
			}
		}

		return null;
	}

	public static function searchSite(array $contentTypes = null)
	{
		$where = array(
			'uid'          => 'c._uid',
			'type'         => 'c._type',
			'author'       => 'c._author_id',
			'last_editor'  => 'c._editor_id',
			'publish_date' => 'c._publish_date',
			'edit_date'    => 'c._edit_date',
			'status'       => 'c._status',
			'slug'         => 'c._slug',
			'protected'    => 'c._protected',
			'options'      => 'c._options',
		);
		$columns                  = $where;
		$columns['title']         = 'c._title';
		$columns['content']       = 'c._content';
		$columns['plain_content'] = 'c._plain_content';
		$columns['publish_date']  = 'UNIX_TIMESTAMP(c._publish_date)';
		$columns['edit_date']     = 'UNIX_TIMESTAMP(c._edit_date)';
		$search = Query::search(App::connection())
			->columns($columns)
			->whereOptions($where)
			->from(ac_table('content'), 'c')
			->groupBy('c._uid')
			->parser(array( __CLASS__, 'parseContentSql' ));
		$ids = array();
		if($contentTypes) foreach($contentTypes as $cType) {
			if($cType instanceof self || is_int($cType) && ($cType = self::getContentType($cType))) {
				$ids[] = $cType->id;
			}
		}
		if(!empty($ids)) {
			array_unshift($ids, Search::SEARCH_IN);
			$search->where(array( 'type' => $ids ));
		}
		return $search;
	}

	/**
	 * @return \Aqua\Content\ContentType[]
	 */
	public static function contentTypes()
	{
		self::$contentTypes !== null or self::loadContentTypes();

		return self::$contentTypes;
	}

	/**
	 * @param string|int $id
	 * @param string     $type
	 * @return \Aqua\Content\ContentType|null
	 */
	public static function getContentType($id, $type = 'id')
	{
		self::$contentTypes !== null or self::loadContentTypes();
		if($type === 'id') {
			return (isset(self::$contentTypes[$id]) ? self::$contentTypes[$id] : null);
		}
		$type = null;
		foreach(self::$contentTypes as $contentType) {
			if($contentType->key === $id) {
				$type = $contentType;
				break;
			}
		}
		reset(self::$contentTypes);

		return $type;
	}

	public static function loadContentTypes()
	{
		if(!(self::$contentTypes = App::cache()->fetch(self::CACHE_KEY, false))) {
			self::rebuildCache();
		}
	}

	/**
	 * @param \SimpleXMLElement $xml
	 * @param int               $pluginId
	 */
	public static function import(\SimpleXMLElement $xml, $pluginId = null)
	{
		$imports = array();
		foreach($xml->contenttype as $ctype) {
			$key  = (string)$ctype->attributes()->key;
			$name = (string)$ctype->name;
			if(!strlen($key) || strlen($name) || self::getContentType($key, 'key')) {
				continue;
			}
			$adapter = (string)$ctype->adapter;
			$sth = App::connection()->prepare(sprintf('
			INSERT INTO `%s` (_key, _name, _listing, _feed, _adapter, _table, _plugin_id)
			VALUES (:key, :name, :listing, :feed, :adapter, NULL, :plugin)
			', ac_table('content_type')));
			$sth->bindValue(':key', $key, \PDO::PARAM_STR);
			$sth->bindValue(':name', $name, \PDO::PARAM_STR);
			if(!$ctype->listing || filter_var((string)$ctype->listing, FILTER_VALIDATE_BOOLEAN)) {
				$sth->bindValue(':list', 'y', \PDO::PARAM_STR);
			} else {
				$sth->bindValue(':list', 'n', \PDO::PARAM_STR);
			}
			if(!$ctype->feed || filter_var((string)$ctype->feed, FILTER_VALIDATE_BOOLEAN)) {
				$sth->bindValue(':feed', 'y', \PDO::PARAM_STR);
			} else {
				$sth->bindValue(':feed', 'n', \PDO::PARAM_STR);
			}
			if($adapter) {
				$sth->bindValue(':adapter', $adapter, \PDO::PARAM_STR);
			} else {
				$sth->bindValue(':adapter', null, \PDO::PARAM_NULL);
			}
			if($pluginId) {
				$sth->bindValue(':plugin', $pluginId, \PDO::PARAM_INT);
			} else {
				$sth->bindValue(':plugin', null, \PDO::PARAM_NULL);
			}
			$sth->execute();
			$ctypeId = (int)App::connection()->lastInsertId();
			$filters = $ctype->filter;
			$table   = $ctype->table[0];
			if($filters) {
				self::_importFilters($filters, $ctypeId);
			}
			if($table) {
				self::_importTable($table, $ctypeId);
			}
			$imports[] = $ctypeId;
		}
		self::rebuildCache();
		foreach($imports as $id) {
			$feedback = array( self::getContentType($id) );
			Event::fire('content-type.import', $feedback);
		}
	}

	protected static function _importTable(\SimpleXMLElement $xml, $ctypeId = null)
	{
		$query   = Query::createTable(App::connection(), ac_table(uniqid("ctype_{$ctypeId}_")));
		$columns = array();
		$id      = 1;
		foreach($xml->column as $field) {
			$name       = "_c$id";
			$query->parseColumnXml($field, $name);
			$columns[(string)$field->attributes()->name] = array( $name, $query->getType($name) );
			++$id;
		}
		if(empty($columns)) {
			return;
		}
		$query->columns(array(
			'_uid' => array(
				'type'     => 'INT',
			    'unsigned' => true,
			    'null'     => false,
			    'primary'  => true
			)
		));
		$id = 0;
		foreach($xml->index as $index) {
			$name = (string)$index->attributes()->name;
			if(!$name) {
				$name = "_ctype_{$ctypeId}_index_{$id}";
			}
			$query->parseIndexXml($index, $name);
		}
		$attributes = $xml->attributes();
		if($engine = (string)$attributes->engine) {
			$query->engine($engine);
		}
		if($format = (string)$attributes->rowformat) {
			$query->rowFormat($format);
		}
		if($collation = (string)$attributes->collation) {
			$query->collation($collation);
		}
		if($charset = (string)$attributes->charset) {
			$query->charset($charset);
		}
		$query->query();
		App::connection()->prepare(sprintf('
		UPDATE INTO `%s`
		SET _table = ?
		WHERE id = ?
		', ac_table('content_type')))->execute(array( $query->tableName, $ctypeId ));
		$sth = App::connection()->prepare(sprintf('
		INSERT INTO `%s` (_type, _name, _alias, _field_type)
		VALUES (:id, :name, :alias, :type)
		', ac_table('content_type_fields')));
		foreach($columns as $column) {
			$sth->bindValue(':id', $ctypeId, \PDO::PARAM_INT);
			$sth->bindValue(':alias', $column[0], \PDO::PARAM_STR);
			$sth->bindValue(':type', $column[1], \PDO::PARAM_STR);
			$sth->execute();
		}
	}

	protected static function _importFilters(\SimpleXMLElement $xml, $ctypeId = null)
	{
		$sth = App::connection()->prepare(sprintf('
		INSERT INTO `%s` (_type, _name, _options)
		VALUES (:id, :name, :opt)
		', ac_table('content_type_filters')));
		foreach($xml as $filter) {
			$name = (string)$filter->attributes()->name;
			if(!strlen($name)) {
				continue;
			}
			$options = serialize((array)$filter->children());
			$sth->bindValue(':id', $ctypeId, \PDO::PARAM_INT);
			$sth->bindValue(':name', $name, \PDO::PARAM_STR);
			$sth->bindValue(':opt', $options, \PDO::PARAM_STR);
			$sth->execute();
		}
	}

	public static function rebuildCache()
	{
		$tbl = ac_table('content_type');
		$sth = App::connection()->query("
		SELECT id,
		       _key,
		       _name,
		       _adapter,
		       _table,
		       _listing,
		       _feed,
		       _plugin_id
		FROM `$tbl`
		");
		while($data = $sth->fetch(\PDO::FETCH_NUM)) {
			$type           = new self;
			$type->id       = (int)$data[0];
			$type->key      = $data[1];
			$type->name     = $data[2];
			$type->table    = $data[4];
			$type->listing  = ($data[5] === 'y');
			$type->feed     = ($data[6] === 'y');
			$type->pluginId = (int)$data[7];
			if($data[3]) {
				$adapter = "Aqua\\Content\\Adapter\\{$data[3]}";
				if(class_exists($adapter) && is_subclass_of($adapter, 'Aqua\\Content\\ContentData')) {
					$type->adapter = $adapter;
				}
			}
			self::$contentTypes[$type->id] = $type;
		}
		$sth->closeCursor();
		$tbl = ac_table('content_type_filters');
		$sth = App::connection()->prepare("
		SELECT _type,
		       _name,
		       _options
		FROM `$tbl`
		");
		$sth->execute();
		while($data = $sth->fetch(\PDO::FETCH_NUM)) {
			$class = "Aqua\\Content\\Filter\\{$data[1]}";
			if(isset(self::$contentTypes[$data[0]]) && class_exists($class) &&
			   is_subclass_of($class, 'Aqua\\Content\\AbstractFilter')) {
				self::$contentTypes[$data[0]]->filters[strtolower($data[1])] =
					new $class(self::$contentTypes[$data[0]], @unserialize($data[2]));
			}
		}
		$sth->closeCursor();
		$tbl = ac_table('content_type_fields');
		$sth = App::connection()->prepare("
		SELECT _type,
		       _name,
		       _alias,
		       _field_type
		FROM `$tbl`
		");
		$sth->execute();
		while($data = $sth->fetch(\PDO::FETCH_ASSOC)) {
			if(isset(self::$contentTypes[$data[0]])) {
				self::$contentTypes[$data[0]]->fields[$data[1]] = array( $data[2], $data[3] );
			}
		}
		App::cache()->store(self::CACHE_KEY, self::$contentTypes);
	}

	/**
	 * @param array $data
	 * @return \Aqua\Content\ContentData
	 */
	public static function parseContentSql(array $data)
	{
		$cType = self::getContentType($data['type'], 'id');
		if($class = $cType->adapter) {
			$content = new $class;
		} else {
			$content = new ContentData;
		}
		$feedback = array( $content, &$data );
		$content->contentType  = & $cType;
		$content->uid          = (int)$data['uid'];
		$content->status       = (int)$data['status'];
		$content->authorId     = (int)$data['author'];
		$content->lastEditorId = (int)$data['last_editor'];
		$content->publishDate  = (int)$data['publish_date'];
		$content->editDate     = (int)$data['edit_date'];
		$content->options      = (int)$data['options'];
		$content->protected    = ($data['protected'] === 'y');
		$content->title        = $data['title'];
		$content->slug         = $data['slug'];
		$content->content      = $data['content'];
		$content->contentPlain = $data['plain_content'];
		ac_parse_content($data['content'], $content->pages, $content->shortContent);
		foreach($cType->fields as $alias => $field) {
			if(!array_key_exists($alias, $data)) {
				continue;
			}
			list($name, $type) = $field;
			if($data[$alias] !== null) {
				switch($type) {
					case 'date':
					case 'number':
						$data[$alias] = (int)$data[$alias];
						break;
					case 'float':
						$data[$alias] = floatval($data[$alias]);
						break;
				}
			}
			$content->data[$alias] = $data[$alias];
		}
		$content->ready();
		$cType->applyFilters('parseData', $feedback);

		return $content;
	}
}
