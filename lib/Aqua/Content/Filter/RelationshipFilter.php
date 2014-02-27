<?php
namespace Aqua\Content\Filter;

use Aqua\Content\AbstractFilter;
use Aqua\Content\ContentData;
use Aqua\Core\App;

class RelationshipFilter
extends AbstractFilter
{
	public function afterUpdate(ContentData $content, array $data, array &$values)
	{
		if(!array_key_exists('parent', $data)) return false;
		$tbl = ac_table('content_relationship');
		$sth = App::connection()->prepare("
		DELETE FROM `$tbl`
		WHERE _content_id = ?
		");
		$sth->bindValue(1, $content->uid, \PDO::PARAM_INT);
		$sth->execute();
		$this->afterCreate($content, $data);
		$values['parent'] = $content->data['parent'];

		return true;
	}

	public function afterCreate(ContentData $content, array &$data)
	{
		if(empty($data['parent']) || $content->uid === $data['parent'] || !$this->contentType->get($data['parent'])) {
			return;
		}
		$tbl = ac_table('content_relationship');
		$sth = App::connection()->prepare("
		INSERT INTO `$tbl` (_content_id, _parent_id)
		VALUES (:id, :parent)
		ON DUPLICATE KEY UPDATE _parent_id = :parent
		");
		$sth->bindValue(':id', $content->uid, \PDO::PARAM_INT);
		$sth->bindValue(':parent', $data['parent'], \PDO::PARAM_INT);
		$sth->execute();
		$content->data['parent'] = (int)$data['parent'];
	}

	public function afterDelete(ContentData $content)
	{
		$tbl = ac_table('content_relationship');
		$sth = App::connection()->prepare("
		DELETE FROM `$tbl`
		WHERE _content_id = :id OR _parent_id = :id
		");
		$sth->bindValue(':id', $content->uid, \PDO::PARAM_INT);
		$sth->execute();
		$content->data['parent'] = null;
	}

	public function forge(ContentData $content, array $data)
	{
		if(array_key_exists('parent', $data) && $this->contentType->get($data['parent'])) {
			$content->data['parent'] = $data['parent'];
		} else {
			$content->data['parent'] = null;
		}
	}

	/**
	 * @param \Aqua\Content\ContentData $content
	 * @return \Aqua\Content\ContentData|null
	 */
	public function contentData_parent(ContentData $content)
	{
		if(!array_key_exists('parent', $content->data)) {
			$tbl = ac_table('content_relationship');
			$sth = App::connection()->prepare("
			SELECT _parent_id
			FROM `$tbl`
			WHERE _content_id = ?
			");
			$sth->bindValue(1, $content->uid, \PDO::PARAM_INT);
			$sth->execute();
			$content->data['parent'] = (int)$sth->fetchColumn(0);
			$sth->closeCursor();
		}

		return ($content->data['parent'] === null ? null : $this->contentType->get($content->data['parent']));
	}

	/**
	 * @param \Aqua\Content\ContentData $content
	 * @return \Aqua\Content\ContentData[]
	 */
	public function contentData_children(ContentData $content)
	{
		if($content->forged) return array();
		$tbl = ac_table('content_relationship');
		$sth = App::connection()->prepare("
		SELECT _content_id
		FROM `$tbl`
		WHERE _parent_id = ?
		");
		$sth->bindValue(1, $content->uid, \PDO::PARAM_INT);
		$sth->execute();
		$children = array();
		while($id = $sth->fetch(\PDO::FETCH_COLUMN, 1)) {
			$children[] = $this->contentType->get($id);
		}

		return $children;
	}

	/**
	 * @param array $data
	 * @return array
	 */
	public function contentType_relationshipTree($data = array())
	{
		$query      = "
		SELECT c._uid AS uid,
		       r._parent_id  AS parent_uid
		";
		$data       = array_unique($data);
		$join       = false;
		$column_map = array(
			'title'       => 'c._title',
			'slug'        => 'c._slug',
			'type'        => 'c._type',
			'author'      => 'c._author_id',
			'last_editor' => 'c._editor_id',
			'status'      => 'c._status',
			'protected'   => 'c._protected',
			'options'     => 'c._options',
			'content'     => 'c._content',
		);
		foreach($data as $col) {
			if(isset($column_map[$col])) {
				$column = $column_map[$col];
				$query .= ", $column AS $col";
			} else if($col === 'publish_date') {
				$query .= ', UNIX_TIMESTAMP(c._publish_date) AS publish_date';
			} else if($col === 'edit_date') {
				$query .= ', UNIX_TIMESTAMP(c._edit_date) AS edit_date';
			} else if(isset($this->contentType->fields[$col])) {
				list($name, $type) = $this->contentType->fields[$col];
				if($type === 'date') {
					$query .= ", UNIX_TIMESTAMP(t.`$name`) AS `$col`";
				} else {
					$query .= ", t.`$name` AS `$col`";
				}
				$join = true;
			}
		}
		$ctbl = ac_table('content');
		$rtbl = ac_table('content_relationship');
		$query .= "
		FROM `$ctbl` c
		LEFT JOIN `$rtbl` r
		ON c._uid = r._content_id
		";
		if($join && $this->contentType->table) {
			$query .= " INNER JOIN `{$this->contentType->table}` t ON t._uid = c._uid";
		}
		$query .= " WHERE c._type = ?";
		$tree = $reference = array();
		$sth  = App::connection()->prepare($query);
		$sth->bindValue(1, $this->contentType->id, \PDO::PARAM_INT);
		$sth->execute();
		while($data = $sth->fetch(\PDO::FETCH_ASSOC)) {
			$id     = (int)$data['uid'];
			$parent = (int)$data['parent_uid'];
			if($parent) {
				if(isset($reference[$parent])) {
					$branch = & $reference[$parent]['children'];
				} else {
					$tree[$parent]      = array( 'children' => array() );
					$reference[$parent] = & $tree[$parent];
					$branch             = & $reference[$parent]['children'];
				}
			} else {
				$branch = & $tree;
			}
			if(isset($reference[$id])) {
				$_branch = $reference[$id];
				unset($tree[$id]);
				$branch[$id]    = $data + $_branch;
				$reference[$id] = $branch[$id];
				unset($_branch);
			} else {
				$branch[$id]             = $data;
				$branch[$id]['children'] = array();
			}
			$reference[$id] = & $branch[$id];
			unset($branch);
		}
		unset($reference);

		return $tree;
	}
}
