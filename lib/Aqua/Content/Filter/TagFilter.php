<?php
namespace Aqua\Content\Filter;

use Aqua\Content\AbstractFilter;
use Aqua\Content\ContentData;
use Aqua\Core\App;

class TagFilter
extends AbstractFilter
{
	public $tags;

	public function afterUpdate(ContentData $content, array $edit, array &$values)
	{
		if(!array_key_exists('tags', $edit)) return null;
		$this->afterDelete($content, false);
		$this->afterCreate($content, $edit, false);
		$this->rebuildCache();
		$values['tags'] = $content->data['tags'];
		return true;
	}

	public function afterCreate(ContentData $content, array &$data, $rebuild = true)
	{
		if(empty($data['tags'])) return;
		if(!is_array($data['tags'])) $data['tags'] = preg_split('/\s*,\s*/', $data['tags']);
		$data['tags'] = array_filter($data['tags']);
		$tbl = ac_table('tags');
		$sth = App::connection()->prepare("
		INSERT INTO `$tbl` (_name, _content_id)
		VALUES (:name, :id)
		");
		$content->data['tags'] = array();
		foreach($data['tags'] as $tag) {
			$tag = substr($tag, 0, 255);
			$sth->bindValue(':name', $tag, \PDO::PARAM_STR);
			$sth->bindValue(':id', $content->uid, \PDO::PARAM_INT);
			$sth->execute();
			$sth->closeCursor();
			$content->data['tags'][] = $tag;
		}
		if($rebuild) $this->rebuildCache();
	}

	public function afterDelete(ContentData $content, $rebuild = true)
	{
		$tbl = ac_table('tags');
		$sth = App::connection()->prepare("
		DELETE FROM `$tbl`
		WHERE _content_id = ?
		");
		$sth->bindValue(1, $content->uid, \PDO::PARAM_INT);
		$sth->execute();
		$content->data['tags'] = array();
		if($rebuild) $this->rebuildCache();
	}

	public function forge(ContentData $content, array $data)
	{
		$content->data['tags'] = array();
		if(!empty($data['tags'])) {
			if(is_array($data['tags'])) $content->data['tags'] = $data['tags'];
			else $content->data['tags'] = preg_split('/\s*,\s*/', $data['tags']);
		}
	}

	public function contentData_tags(ContentData $content)
	{
		if(!isset($content->data['tags'])) {
			$tbl = ac_table('tags');
			$sth = App::connection()->prepare("
			SELECT _name
			FROM `$tbl`
			WHERE _content_id = ?
			ORDER BY _content_id, id
			");
			$sth->bindValue(1, $content->uid, \PDO::PARAM_INT);
			$sth->execute();
			$content->data['tags'] = $sth->fetchAll(\PDO::FETCH_COLUMN, 0);
		}
		return $content->data['tags'];
	}

	public function contentType_tags()
	{
		$key = 'content_tags.' . $this->contentType->id;
		if($this->tags === null && !($this->tags = App::cache()->fetch($key, null))) {
			$this->rebuildCache();
		}
		return $this->tags;
	}

	public function contentType_tagSearch()
	{
		$search = $this->contentType->search();
		$search
			->columnModifier('DISTINCT')
			->whereOptions(array( 'tag' => 'tag._name' ))
			->from(ac_table('tags'), 'tag')
			->groupBy(array( 'tag.id' ))
			->leftJoin(ac_table('content'), 'c._uid = tag._content_id', 'c');
		if($this->contentType->table && isset($search->joins[$this->contentType->table])) {
			$search->joins[$this->contentType->table]['on'] = 't._uid = tag._content_id';
		}
		return $search;
	}

	public function rebuildCache()
	{
		$ttbl = ac_table('tags');
		$ctbl = ac_table('content');
		$query = "
		SELECT DISTINCT t._name, COUNT(_name)
		FROM `$ttbl` t
		LEFT JOIN `$ctbl` c
		ON c._uid = t._content_id
		WHERE c._type = ?
		GROUP BY _name";
		$sth = App::connection()->prepare($query);
		$sth->bindValue(1, $this->contentType->id, \PDO::PARAM_INT);
		$sth->execute();
		$this->tags = array();
		while($data = $sth->fetch(\PDO::FETCH_NUM)) {
			$this->tags[$data[0]] = (int)$data[1];
		}
		App::cache()->store('content_tags.' . $this->contentType->id, $this->tags);
	}
}
