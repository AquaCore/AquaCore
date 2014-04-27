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
	 * @param int               $plugin_id
	 */
	public static function import(\SimpleXMLElement $xml, $plugin_id = null)
	{
		$sth = App::connection()->prepare(sprintf('
		INSERT INTO `$tbl` (_key, _name, _listing, _feed, _adapter, _table, _plugin_id)
		VALUES (:key, :name, :list, :feed, :adapter, NULL, :plugin)
		ON DUPLICATE KEY UPDATE
		_name = VALUES(_name),
		_adapter = VALUES(_adapter),
		_listing = VALUES(_listing),
		_feed = VALUES(_feed),
		_pugin_id = VALUES(_plugin_id)
		', ac_table('content_type')));
		$tbl = App::connection()->prepare(sprintf('
		UPDATE `%s`
		SET _table = :table
		WHERE id = :id
		', ac_table('content_type')));
		$fields = App::connection()->prepare(sprintf('
		INSERT INTO `%s` (_type, _name, _alias, _field_type)
		VALUES (:id, :name, :alias, :type)
		', ac_table('content_type_fields')));
		$filters = App::connection()->prepare(sprintf('
		INSERT INTO `%s` (_type, _name, _options)
		VALUES (:id, :name, :opt)
		', ac_table('content_type_filters')));
		$imports = array();
		foreach($xml->contenttype as $ctype) {
			$key = (string)$ctype->attributes()->key;
			$name = (string)$ctype->name;
			if(!strlen($key) || !strlen($name) || self::getContentType($key, 'key')) {
				continue;
			}
			$adapter = (string)$ctype->adapter;
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
			if($plugin_id) {
				$sth->bindValue(':plugin', $plugin_id, \PDO::PARAM_INT);
			} else {
				$sth->bindValue(':plugin', null, \PDO::PARAM_NULL);
			}
			$sth->execute();
			$id = App::connection()->lastInsertId();
			foreach($ctype->filter as $filter) {
				$name = (string)$filter->attributes()->name;
				if(!strlen($name)) {
					continue;
				}
				$options = serialize((array)$filter->children());
				$filters->bindValue(':id', $id, \PDO::PARAM_INT);
				$filters->bindValue(':name', $name, \PDO::PARAM_STR);
				$filters->bindValue(':opt', $options, \PDO::PARAM_STR);
				$filters->execute();
			}
			if($table = $ctype->table[0]) {
				$query   = Query::createTable(App::connection(), ac_table("ctype_$id"));
				$columns = array();
				$i       = 0;
				foreach($table->column as $field) {
					$attributes = $field->attributes();
					$name       = (string)$attributes->name;
					switch($name) {
						case '':
						case 'uid':
						case 'type':
						case 'status':
						case 'title':
						case 'slug':
						case 'content':
						case 'plain_content':
						case 'author':
						case 'editor':
						case 'publish_date':
						case 'edit_date':
						case 'protected':
						case 'options':
						case 'keyword':
						case 'score':
							continue;
					}
					$column_type = strtoupper((string)$attributes->type);
					switch($column_type) {
						case 'TIMESTAMP':
						case 'DATETIME':
						case 'DATE':
							$type = 'date';
							break;
						case 'TIME':
						case 'YEAR':
							$type = 'time';
							break;
						case 'TINYINT':
						case 'SMALLINT':
						case 'MEDIUMINT':
						case 'BIGINT':
							$type = 'number';
							break;
						case 'DECIMAL':
						case 'NUMBER':
						case 'FLOAT':
						case 'REAL':
						case 'DOUBLE':
							$length    = (string)$attributes->length;
							$precision = (string)$attributes->precision;
							if(($length && !ctype_digit($length)) ||
							   !ctype_digit($precision) ||
							   intval($precision) < 1
							) {
								continue;
							}
							$type = 'float';
							break;
						case 'VARCHAR':
						case 'CHAR':
							if(!($length = (string)$attributes->length) ||
							   !ctype_digit($length)
							) {
								continue;
							}
							$type = 'string';
							break;
						case 'BINARY':
						case 'VARBINARY':
							if(!($length = (string)$attributes->length) ||
							   !ctype_digit($length) ||
							   intval($length) < 1
							) {
								continue;
							}
							$type = 'binary';
							break;
						case 'TINYBLOB':
						case 'MEDIUMBLOB':
						case 'BLOB':
						case 'LONGBLOB':
							$type = 'blob';
							break;
						case 'TINYTEXT':
						case 'MEDIUMTEXT':
						case 'TEXT':
						case 'LONGTEXT':
							$type = 'text';
							break;
						case 'ENUM':
						case 'SET':
							$type = strtolower($column_type);
							break;
						default:
							continue;
					}
					++$i;
					$default = (string)$attributes->default;
					$query->columns(array(
							"c_$i" => array(
								'type'      => $column_type,
								'length'    => (int)$attributes->length,
								'precision' => (int)$attributes->precision,
								'charset'   => (string)$attributes->charset,
								'collation' => (string)$attributes->collation,
								'format'    => (string)$attributes->format,
								'storage'   => (string)$attributes->storage,
								'default'   => ($default === 'NULL' ? null : $default),
								'unsigned'  => filter_var((string)$attributes->unsigned,
								                          FILTER_VALIDATE_BOOLEAN),
								'zeroFill'  => filter_var((string)$attributes->zerofill,
								                          FILTER_VALIDATE_BOOLEAN),
								'null'      => filter_var((string)$attributes->null,
								                          FILTER_VALIDATE_BOOLEAN),
								'primary'   => filter_var((string)$attributes->primary,
								                          FILTER_VALIDATE_BOOLEAN),
								'unique'    => filter_var((string)$attributes->unique,
								                          FILTER_VALIDATE_BOOLEAN),
								'values'    => (array)$field->value,
								'reference' => (array)$field->reference[0],
							)
						));
					$columns[$name] = array( "_c$i", $type );
				}
				if(!empty($columns)) {
					$query->column(array(
						'_uid' => array(
							'type'     => 'INT',
							'unsigned' => true,
							'null'     => false,
							'primary'  => true
							)
						));
					$i = 0;
					foreach($table->index as $index) {
						$attributes = $index->attributes();
						$name       = (string)$attributes->name;
						if(!$name) {
							$name = "_ctype_{$id}__index_{$i}";
							++$i;
						}
						$query->index(array(
							$name => array(
								'type'         => (string)$attributes->type,
								'using'        => (string)$attributes->using,
								'match'        => (string)$attributes->match,
								'onDelete'     => (string)$attributes->ondelete,
								'onUpdate'     => (string)$attributes->onupdate,
								'keyBlockSize' => (int)$attributes->keyblocksize,
								'columns'      => (array)$index->column,
								)
							));
					}
					$attributes = $table->attributes();
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
					foreach($columns as $alias => $data) {
						$fields->bindValue(':id', $id, \PDO::PARAM_INT);
						$fields->bindValue(':name', $data[0], \PDO::PARAM_STR);
						$fields->bindValue(':type', $data[1], \PDO::PARAM_STR);
						$fields->bindValue(':alias', $alias, \PDO::PARAM_STR);
						$fields->execute();
					}
					$tbl->bindValue(':id', $id, \PDO::PARAM_INT);
					$tbl->bindValue(':table', $query->tableName, \PDO::PARAM_STR);
					$tbl->execute();
				}
			}
			$imports[] = $id;
		}
		self::rebuildCache();
		foreach($imports as $id) {
			$feedback = array( self::getContentType($id) );
			Event::fire('content-type.import', $feedback);
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
