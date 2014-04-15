<?php
namespace Aqua\Content;

use Aqua\Core\App;
use Aqua\Event\Event;
use Aqua\User\Account;

class ContentData
implements \Serializable
{
	/**
	 * @var \Aqua\Content\ContentType
	 */
	public $contentType;
	/**
	 * @var int
	 */
	public $uid;
	/**
	 * @var int
	 */
	public $status;
	/**
	 * @var int
	 */
	public $authorId;
	/**
	 * @var int|null
	 */
	public $lastEditorId;
	/**
	 * @var int
	 */
	public $publishDate;
	/**
	 * @var int|null
	 */
	public $editDate;
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
	public $contentPlain;
	/**
	 * @var string
	 */
	public $shortContent;
	/**
	 * @var array
	 */
	public $pages = array();
	/**
	 * @var string
	 */
	public $plainText;
	/**
	 * @var bool
	 */
	public $protected;
	/**
	 * @var int
	 */
	public $options;
	/**
	 * @var array
	 */
	public $data = array();
	/**
	 * @var bool
	 */
	public $forged = false;
	/**
	 * @var array
	 */
	public $meta;
	/**
	 * @var bool
	 */
	protected $_metaLoaded = false;

	const STATUS_PUBLISHED = 0;
	const STATUS_DRAFT     = 1;

	public function ready()
	{
		if($this->forged) {
			$this->_metaLoaded = true;
		}
	}

	/**
	 * @return \Aqua\User\Account
	 */
	public function author()
	{
		return Account::get($this->authorId);
	}

	/**
	 * @return \Aqua\User\Account|null
	 */
	public function lastEditor()
	{
		if(!$this->lastEditorId) {
			return null;
		} else {
			return Account::get($this->lastEditorId);
		}
	}

	/**
	 * @param string $format
	 * @return string
	 */
	public function publishDate($format)
	{
		return strftime($format, $this->publishDate);
	}

	/**
	 * @param string $format
	 * @return string
	 */
	public function editDate($format)
	{
		return strftime($format, $this->editDate);
	}

	/**
	 * @param int    $max
	 * @param string $append
	 * @return string
	 */
	public function truncate($max, $append = '')
	{
		$content = '';
		$printed = 0;
		$pattern = '%</?([a-z]+)[^>]*/?>|&#?[a-zA-Z0-9]+;|[\x80-\xFF][\x80-\xBF]*%miS';
		$offset = 0;
		$tags = array();
		while($printed < $max && preg_match($pattern, $this->content, $match, PREG_OFFSET_CAPTURE, $offset)) {
			$tagLength = strlen($match[0][0]);
			$tagOffset = $match[0][1];
			$str = substr($this->content, $offset, $tagOffset - $offset);
			if(($printed + strlen($str)) > $max) {
				$str = substr($str, 0, $max - $printed);
			}
			$printed += strlen($str);
			if(substr($match[0][0], 0, 2) === '</') {
				$name = strtolower($match[1][0]);
				for($i = count($tags) - 1; $i >= 0; --$i) {
					if($name === $tags[$i]) {
						break;
					}
					$printed.= "</{$tags[$i]}>";
					array_pop($tags);
				}
			} else if($match[0][0][0] === '<' && isset($match[1]) &&
			          substr($match[0][0], -2) !== '/>') {
				$tags[] = strtolower($match[1][0]);
			}
			$content.= $str . $match[0][0];
			$offset = $tagOffset + $tagLength;
		}
		if($printed < $max) {
			$content.= substr($this->content, $offset, $max - $printed);
		}
		$content = substr($content, 0, strrpos($content, ' '));
		$inline = array(
			'b', 'big', 'i', 'small', 'tt', 'label',
			'abbr', 'acronym', 'cite', 'code', 'input',
			'dfn', 'em', 'kdb', 'strong', 'samp', 'select',
			'var', 'a', 'bdo', 'br', 'img', 'map', 'textarea',
			'object', 'span', 'sub', 'sup', 'button'
		);
		for($i = count($tags) - 1; $i >= 0; --$i) {
			$name = $tags[$i];
			if($append && !in_array($name, $inline, true)) {
				$content.= $append;
				$append = null;
			}
			$content.= "</$name>";
		}
		if($append) {
			$content.= $append;
		}

		return $content;
	}

	/**
	 * @param array $data
	 * @return bool
	 */
	public function update(array $data)
	{
		$updated  = false;
		$feedback = array( &$data );
		if($this->applyFilters('beforeUpdate', $feedback) === false) return false;
		if($this->contentType->table) {
			$columns = $values = $custom_values = array();
			foreach($this->contentType->fields as $name => $field) {
				list($alias, $type) = $field;
				if(!array_key_exists($alias, $data)) continue;
				if($data[$alias] === null) {
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
					$values[$name] = $data[$alias];
				}
				$custom_values[$alias] = $values[$name];
			}
			$query = "UPDATE `{$this->contentType->table}` SET ";
			$query .= '`' . implode('` = ?, `', array_keys($columns)) . '` =?';
			$query .= ' WHERE _uid = ? LIMIT 1';
			$sth = App::connection()->prepare($query);
			$i   = 0;
			foreach($columns as $name => $type) $sth->bindValue(++$i, $values[$name], $type);
			$sth->bindValue(++$i, $this->uid, \PDO::PARAM_INT);
			$sth->execute();
			if($sth->rowCount()) {
				foreach($custom_values as $key => $val) {
					$this->data[$key] = $val;
				}
				$updated = true;
			} else {
				unset($custom_values);
			}
		}
		$values       = array();
		$update       = '';
		$content_data = array();
		do {
			$edit = array_intersect_key($data, array_flip(array(
					'author', 'last_editor', 'status',
					'publish_date', 'edit_date',
					'content', 'plain_content',
					'title', 'slug',
					'protected', 'options'
				)));
			if(empty($edit)) break;
			$edit = array_map(function ($val) { return (is_string($val) ? trim($val) : $val); }, $edit);
			if(array_key_exists('author', $edit) && $edit['author'] !== $this->authorId) {
				$values['author'] = $content_data['authorId'] = $edit['author'];
				$update .= '_author_id = ?, ';
			}
			if(array_key_exists('status', $edit) && $edit['status'] !== $this->status) {
				$values['status'] = $content_data['status'] = $edit['status'];
				$update .= '_status = ?, ';
			}
			if(array_key_exists('publish_date', $edit) && $edit['publish_date'] !== $this->publishDate) {
				$values['publish_date'] = date('Y-m-d H:i:s', $edit['publish_date']);
				$content_data['publishDate'] = $edit['publish_date'];
				$update .= '_publish_date = ?, ';
			}
			if(array_key_exists('edit_date', $edit) && $edit['edit_date'] !== $this->editDate) {
				$values['edit_date'] = date('Y-m-d H:i:s', $edit['edit_date']);
				$content_data['editDate'] = $edit['edit_date'];
				$update .= '_edit_date = ?, ';
			}
			if(array_key_exists('plain_content', $edit)) {
				$values['plain_content'] = $edit['plain_content'];
				$update .= '_plain_content = ?, ';
			}
			if(array_key_exists('content', $edit)) {
				$values['content'] = $content_data['content'] = $edit['content'];
				$update .= '_content = ?, ';
				if(!array_key_exists('plain_content', $values)) {
					$values['plain_content'] = html_entity_decode(strip_tags($edit['content']), ENT_QUOTES, 'UTF-8');
					$update .= '_plain_content = ?, ';
				}
			}
			if(array_key_exists('slug', $edit)) {
				$values['slug'] = $content_data['slug'] = substr($edit['slug'], 0, 255);
				$update .= '_slug = ?, ';
			}
			if(array_key_exists('title', $edit) && $edit['title'] !== $this->title) {
				$values['title'] = $content_data['title'] = $edit['title'];
				$update .= '_title = ?, ';
				if(!array_key_exists('slug', $values)) {
					$values['slug'] = $content_data['slug'] = $this->contentType->slug($edit['title'], $this->uid);
					$update .= '_slug = ?, ';
				}
			}
			if(array_key_exists('protected', $edit) && (bool)$edit['protected'] !== $this->protected) {
				$content_data['protected'] = (bool)$edit['protected'];
				$values['protected']       = ($content_data['protected'] ? 'y' : 'n');
				$update .= '_protected = ?, ';
			}
			if(array_key_exists('options', $edit) && $edit['options'] !== $this->options) {
				$values['options'] = $content_data['options'] = $edit['options'];
				$update .= '_options = ?, ';
			}
			if(array_key_exists('last_editor', $edit) && $edit['last_editor'] !== $this->lastEditorId) {
				$values['last_editor'] = $content_data['lastEditorId'] = $edit['last_editor'];
				$update .= '_editor_id = ?, ';
			}
			if(empty($values)) {
				$values = array();
				break;
			}
			$values[] = $this->uid;
			$update   = substr($update, 0, -2);
			$tbl      = ac_table('content');
			$sth      = App::connection()->prepare("UPDATE `$tbl` SET $update WHERE _uid = ? LIMIT 1");
			$sth->execute(array_values($values));
			array_pop($values);
			if(isset($custom_values)) $values = array_merge($values, $custom_values);
			if(!$sth->rowCount()) {
				$values = array();
				break;
			}
			$updated = true;
			if(array_key_exists('protected', $values)) $values['protected'] = $content_data['protected'];
			if(array_key_exists('publish_date', $values)) $values['publish_date'] = $content_data['publishDate'];
			if(array_key_exists('edit_date', $values)) $values['edit_date'] = $content_data['editDate'];
			foreach($content_data as $key => $value) {
				$this->$key = $value;
			}
		} while(0);
		if(isset($custom_values)) {
			$values = array_merge($values, $custom_values);
		}
		$feedback = array( &$data, &$values );
		if($this->applyFilters('afterUpdate', $feedback) || $updated) {
			if(!isset($values['edit_date'])) {
				$tbl = ac_table('content');
				$sth = App::connection()->prepare("
				UPDATE `$tbl`
				SET _edit_date = NOW()
				WHERE _uid = ?
				LIMIT 1
				");
				$sth->bindValue(1, $this->uid, \PDO::PARAM_INT);
				$sth->execute();
				$values['edit_date'] = $this->editDate = time();
			}
			$feedback = array( $this, $values );
			Event::fire('content.update', $feedback);

			return true;
		} else {
			return false;
		}
	}

	/**
	 * @return bool
	 */
	public function delete()
	{
		if($this->protected || $this->applyFilters('beforeDelete') === false) return false;
		$content_tbl = ac_table('content');
		$meta_tbl    = ac_table('content_meta');
		$sth         = App::connection()->prepare("
		DELETE FROM `$content_tbl` WHERE _uid = :id;
		DELETE FROM `$meta_tbl` WHERE _content_id = :id;
		");
		$sth->bindValue(':id', $this->uid, \PDO::PARAM_INT);
		$sth->execute();
		$this->applyFilters('afterDelete');
		$feedback = array( $this );
		Event::fire('content.delete', $feedback);

		return true;
	}

	/**
	 * @param string $key
	 * @param mixed  $default
	 * @return mixed
	 */
	public function data($key, $default = null)
	{
		return (array_key_exists($key, $this->data) ? $this->data[$key] : $default);
	}

	/**
	 * @param string $key
	 * @param mixed  $default
	 * @return mixed
	 */
	public function getMeta($key, $default = null)
	{
		$this->_metaLoaded or $this->loadMeta();
		if(array_key_exists($key, $this->meta)) {
			return $this->meta[$key];
		} else {
			return $default;
		}
	}

	/**
	 * @param array|string $key
	 * @param string       $value
	 * @return \Aqua\Content\ContentData
	 */
	public function setMeta($key, $value = null)
	{
		if($this->forged) return $this;
		if(!is_array($key)) $key = array( $key => $value );
		$tbl = ac_table('content_meta');
		$sth = App::connection()->prepare("
		INSERT INTO `$tbl` (_content_id, _key, _val)
		VALUES (:id, :key, :val)
		ON DUPLICATE KEY UPDATE _val = :val
		");
		foreach($key as $k => $val) {
			$sth->bindValue(':id', $this->uid, \PDO::PARAM_INT);
			$sth->bindValue(':key', $k, \PDO::PARAM_STR);
			$sth->bindValue(':val', serialize($val), \PDO::PARAM_LOB);
			$sth->execute();
			$sth->closeCursor();
		}
		if($this->_metaLoaded) {
			$this->meta = array_merge($this->meta, $key);
		}

		return $this;
	}

	/**
	 * @param string|array $key
	 * @return \Aqua\Content\ContentData
	 */
	public function deleteMeta($key)
	{
		if($this->forged) return $this;
		if(!is_array($key)) $key = array( $key );
		$tbl = ac_table('content_meta');
		$sth = App::connection()->prepare("
		DELETE FROM `$tbl`
		WHERE _content_id = ? AND _key = ?
		LIMIT 1
		");
		foreach($key as $k) {
			$sth->bindValue(1, $this->uid, \PDO::PARAM_INT);
			$sth->bindValue(2, $k, \PDO::PARAM_STR);
			$sth->execute();
			$sth->closeCursor();
			if($this->_metaLoaded) unset($this->meta[$k]);
		}

		return $this;
	}

	public function loadMeta()
	{
		if($this->forged) return $this;
		$tbl = ac_table('content_meta');
		$sth = App::connection()->prepare("
		SELECT _key, _val
		FROM `$tbl`
		WHERE _content_id = ?
		");
		$sth->bindValue(1, $this->uid, \PDO::PARAM_INT);
		$sth->execute();
		$this->meta = array();
		while($data = $sth->fetch(\PDO::FETCH_NUM)) {
			$this->meta[$data[0]] = unserialize($data[1]);
		}
		$this->_metaLoaded = true;

		return $this;
	}

	/**
	 * @param string      $method
	 * @param array       $arguments
	 * @return bool|null
	 */
	public function applyFilters($method, array &$arguments = array())
	{
		$ret = null;
		array_unshift($arguments, $this);
		foreach($this->contentType->filters as $filter) {
			$_ret = call_user_func_array(array( $filter, $method ), $arguments);
			if($_ret === false) {
				return false;
			} else if($_ret !== null) {
				$ret = $_ret;
			}
		}
		array_shift($arguments);

		return $ret;
	}

	public function __call($method, array $arguments)
	{
		array_unshift($arguments, $this);
		foreach($this->contentType->filters as $filter) {
			if(method_exists($filter, "contentData_$method")) {
				return call_user_func_array(array( $filter, "contentData_$method" ), $arguments);
			}
		}

		return null;
	}

	public function __get($key)
	{
		return $this->data($key);
	}

	public function serialize()
	{
		$data = array();
		foreach($this->contentType->fields as $alias => $field) {
			if(array_key_exists($alias, $this->data)) {
				$data[$alias] = $this->data[$alias];
			}
		}

		return serialize(array(
				$this->contentType->id,
				$this->uid,
				$this->status,
				$this->authorId,
				$this->lastEditorId,
				$this->publishDate,
				$this->editDate,
				$this->options,
				$this->protected,
				$this->title,
				$this->slug,
				$this->content,
				$data
			));
	}

	public function unserialize($serialized)
	{
		list(
			$ctype,
			$this->uid,
			$this->status,
			$this->authorId,
			$this->lastEditorId,
			$this->publishDate,
			$this->editDate,
			$this->options,
			$this->protected,
			$this->title,
			$this->slug,
			$this->content,
			$this->data
			) = unserialize($serialized);
		ac_parse_content($this->content, $this->pages, $this->shortContent);
		$this->contentType = ContentType::getContentType($ctype);
	}
}
