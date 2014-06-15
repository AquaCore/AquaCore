<?php
namespace Aqua\Content\Filter;

use Aqua\Content\AbstractFilter;
use Aqua\Content\ContentData;
use Aqua\Content\Feed\RssItem;
use Aqua\Content\Filter\CategoryFilter\Category;
use Aqua\Core\App;
use Aqua\Core\Meta;

class CategoryFilter
extends AbstractFilter
{
	public $categories;

	public function afterUpdate(ContentData $content, array $data, array &$values)
	{
		if(!array_key_exists('category', $data)) return null;
		$this->afterDelete($content);
		$this->afterCreate($content, $data);
		$values['category'] = $content->data['categories'];

		return true;
	}

	public function afterCreate(ContentData $content, array &$data)
	{
		if(empty($data['category'])) return;
		if(!is_array($data['category'])) $data['category'] = array( $data['category'] );
		$data['category']            = array_unique(array_filter($data['category']));
		$sth                         = App::connection()->prepare(sprintf('
		REPLACE INTO %s (_content_id, _category_id)
		VALUES (:content, :category)
		', ac_table('content_categories')));
		$content->data['categories'] = array();
		foreach($data['category'] as $category) {
			if(!$this->contentType_getCategory($category)) continue;
			$sth->bindValue(':content', $content->uid, \PDO::PARAM_INT);
			$sth->bindValue(':category', $category, \PDO::PARAM_INT);
			$sth->execute();
			$content->data['categories'][] = $category;
		}
	}

	public function afterDelete(ContentData $content)
	{
		$sth = App::connection()->prepare(sprintf('
		DELETE FROM %s
		WHERE _content_id = :id
		', ac_table('content_categories')));
		$sth->bindValue(':id', $content->uid, \PDO::PARAM_INT);
		$sth->execute();
		$sth->closeCursor();
		$content->data['categories'] = array();
	}

	public function forge(ContentData $content, array $data)
	{
		$content->data['categories'] = array();
		if(array_key_exists('category', $data)) {
			foreach($data['category'] as $id) {
				if($category = $this->contentType_getCategory($id, 0)) {
					$content->data['categories'][$category->id] = $category;
				}
			}
		}
	}

	public function rss(ContentData $content, RssItem $item)
	{
		foreach($this->contentData_categories($content) as $category) {
			$item->categories[] = $category->name;
		}
	}

	/**
	 * @param \Aqua\Content\ContentData $content
	 * @return \Aqua\Content\Filter\CategoryFilter\Category[]
	 */
	public function contentData_categories(ContentData $content)
	{
		if(!isset($content->data['categories'])) {
			$sth = App::connection()->prepare(sprintf('
			SELECT _category_id
			FROM %s
			WHERE _content_id = ?
			', ac_table('content_categories')));
			$sth->bindValue(1, $content->uid, \PDO::PARAM_INT);
			$sth->execute();
			$content->data['categories'] = array();
			while($id = $sth->fetch(\PDO::FETCH_COLUMN, 0)) {
				if($category = $this->contentType_getCategory($id)) {
					$content->data['categories'][$id] = $category;
				}
			}
		}

		return $content->data['categories'];
	}

	/**
	 * @return \Aqua\Content\Filter\CategoryFilter\Category[]
	 */
	public function contentType_categories()
	{
		$this->categories !== null or $this->loadCategories();

		return $this->categories;
	}

	/**
	 * @param int|string $id
	 * @param string     $type
	 * @return \Aqua\Content\Filter\CategoryFilter\Category
	 */
	public function contentType_getCategory($id, $type = 'id')
	{
		$this->categories !== null or $this->loadCategories();
		if($type === 'id') {
			return (isset($this->categories[$id]) ? $this->categories[$id] : null);
		}
		$category = null;
		foreach($this->categories as $category) {
			if($category->slug === $id) break;
		}
		reset($this->categories);

		return $category;
	}

	/**
	 * @param array $data
	 * @return \Aqua\Content\Filter\CategoryFilter\Category[]
	 */
	public function contentType_createCategory(array $data)
	{
		$data += array(
			'name'        => '',
			'image'       => '',
			'description' => '',
			'protected'   => false,
			'options'     => 0
		);
		if(!array_key_exists('slug', $data)) {
			$data['slug'] = $this->slug($data['name']);
		}
		$sth = App::connection()->prepare(sprintf('
		INSERT INTO %s (_type, _name, _slug, _image, _description, _options, _protected)
		VALUES (:type, :name, :slug, :image, :desc, :opt, :protected)
		', ac_table('categories')));
		$sth->bindValue(':type', $this->contentType->id, \PDO::PARAM_INT);
		$sth->bindValue(':name', $data['name'], \PDO::PARAM_STR);
		$sth->bindValue(':slug', $data['slug'], \PDO::PARAM_STR);
		$sth->bindValue(':image', $data['image'], \PDO::PARAM_STR);
		$sth->bindValue(':desc', $data['description'], \PDO::PARAM_STR);
		$sth->bindValue(':opt', $data['options'], \PDO::PARAM_INT);
		$sth->bindValue(':protected', ($data['protected'] ? 'y' : 'n'), \PDO::PARAM_STR);
		$sth->execute();
		$this->rebuildCache();
		$category = $this->contentType_getCategory((int)App::connection()->lastInsertId(), 0);
		$feedback = array( $category );
		$this->notify('create', $feedback);

		return $category;
	}

	/**
	 * @param \Aqua\Content\Filter\CategoryFilter\Category $category
	 * @return bool
	 */
	public function contentType_deleteCategory(Category $category)
	{
		if($category->protected) return false;
		$sth          = App::connection()->prepare(sprintf('
		DELETE FROM %s WHERE _category_id = :id;
		DELETE FROM %s WHERE _id = :id;
		DELETE FROM %s WHERE id = :id;
		', ac_table('content_categories'), ac_table('category_meta'), ac_table('categories')));
		$sth->bindValue(':id', $category->id, \PDO::PARAM_INT);
		$sth->execute();
		$sth->closeCursor();
		$feedback = array( $category );
		$this->notify('delete', $feedback);
		$this->rebuildCache();

		return true;
	}

	/**
	 * @param \Aqua\Content\Filter\CategoryFilter\Category  $category
	 * @param array                                         $data
	 * @return bool
	 */
	public function contentType_updateCategory(Category $category, array $data)
	{
		$data = array_intersect_key($data, array_flip(array(
				'name', 'slug', 'image', 'description', 'options', 'protected'
			)));
		if(empty($data)) return false;
		$data   = array_map(function ($val) { return (is_string($val) ? trim($val) : $val); }, $data);
		$values = array();
		$update = '';
		if(array_key_exists('slug', $data)) {
			$values['slug'] = substr($data['slug'], 0, 255);
			$update .= '_slug = ?, ';
		}
		if(array_key_exists('name', $data) && $data['name'] !== $category->name) {
			$values['name'] = $data['name'];
			$update .= '_name = ?, ';
			if(!array_key_exists('slug', $values)) {
				$values['slug'] = $this->slug($data['name'], $category->id);
				$update .= '_slug = ?, ';
			}
		}
		if(array_key_exists('image', $data) && $data['image'] !== $category->image) {
			$values['image'] = $data['image'];
			$update .= '_image = ?, ';
		}
		if(array_key_exists('description', $data) && $data['description'] !== $category->description) {
			$values['description'] = $data['description'];
			$update .= '_description = ?, ';
		}
		if(array_key_exists('options', $data) && $data['options'] !== $category->options) {
			$values['options'] = $data['options'];
			$update .= '_options = ?, ';
		}
		if(array_key_exists('protected', $data) && (bool)$data['protected'] !== $category->protected) {
			$values['protected'] = ($data['protected'] ? 'y' : 'n');
			$update .= '_protected = ?, ';
		}
		if(empty($values)) {
			return false;
		}
		$update   = substr($update, 0, -2);
		$values[] = $category->id;
		$sth      = App::connection()->prepare(sprintf('
		UPDATE %s
		SET %s
		WHERE id = ?
		', ac_table('categories'), $update));
		$sth->execute(array_values($values));
		if(!$sth->rowCount()) {
			return false;
		}
		array_pop($values);
		if(array_key_exists('protected', $values)) {
			$values['protected'] = ($values['protected'] === 'y');
		}
		$feedback = array( $category, $values );
		$this->notify('update', $feedback);
		foreach($values as $key => $val) {
			$category->$key = $val;
		}
		if(isset($values['image'])) {
			if($values['image'] === '') {
				$category->isImageUploaded = false;
				$category->imageUrl = '';
			} else if(substr($values['image'], 0, 9) === '/uploads/') {
				$category->isImageUploaded = true;
				$category->imageUrl = \Aqua\URL . $values['image'];
			} else {
				$category->isImageUploaded = false;
				$category->imageUrl = $values['image'];
			}
		}
		$this->rebuildCache();

		return true;
	}

	/**
	 * @return \Aqua\SQL\Search
	 */
	public function contentType_categorySearch()
	{
		$search = $this->contentType->search();
		$search
			->columns(array( 'category_id' => 'cc._category_id' ))
			->whereOptions(array( 'category_id' => 'cc._category_id' ))
			->from(ac_table('content_categories'), 'cc')
			->groupBy(array( 'c._uid' ))
			->innerJoin(ac_table('content'), 'c._uid = cc._content_id', 'c');
		if($this->contentType->table && isset($search->joins[$this->contentType->table])) {
			$search->joins[$this->contentType->table]->on = 't._uid = cc._content_id';
		}

		return $search;
	}

	/**
	 * @param string   $title
	 * @param int|null $id
	 * @return string
	 */
	public function slug($title, $id = null)
	{
		if($title === '') $title = __('application', 'untitled');
		$slug = ac_slug($title, 250);
		$sth  = App::connection()->prepare(sprintf('
		SELECT _slug
		FROM %s
		WHERE _type = ?
		AND _slug LIKE ?
		', ac_table('categories')) . ($id !== null ? 'AND id != ?' : ''));
		$sth->bindValue(1, $this->contentType->id, \PDO::PARAM_INT);
		$sth->bindValue(2, addcslashes($slug, '%_\\') . '%', \PDO::PARAM_STR);
		if($id !== null) $sth->bindValue(3, $id, \PDO::PARAM_INT);
		$sth->execute();
		$num   = 0;
		$regex = '/' . preg_quote($slug, '/') . '-[0-9]+/i';
		while($pl = $sth->fetch(\PDO::FETCH_COLUMN, 0)) {
			if($pl === $slug && $num === 0) {
				$num = 2;
			} else if(preg_match($regex, $pl, $matches)) {
				$n = intval($matches[1]) + 1;
				if($num < $n) $num = $n;
			}
		}
		if($num) $slug .= "-$num";

		return $slug;
	}

	public function loadCategories()
	{
		if(!($this->categories = App::cache()->fetch('content_categories.' . $this->contentType->id, null))) {
			$this->rebuildCache();
		}
	}

	public function rebuildCache()
	{
		$sth = App::connection()->prepare(sprintf('
		SELECT id,
		       _type,
		       _name,
		       _slug,
		       _image,
		       _description,
		       _options
		FROM %s
		WHERE _type = ?
		', ac_table('categories')));
		$sth->bindValue(1, $this->contentType->id, \PDO::PARAM_INT);
		$sth->execute();
		$this->categories = array();
		while($data = $sth->fetch(\PDO::FETCH_NUM)) {
			$category                = new Category;
			$category->meta          = new Meta(ac_table('category_meta'),
			                                    $data[0]);
			$category->id            = (int)$data[0];
			$category->contentTypeId = (int)$data[1];
			$category->name          = $data[2];
			$category->slug          = $data[3];
			$category->image         = $data[4];
			if(substr($category->image, 0, 9) === '/uploads/') {
				$category->isImageUploaded = true;
				$category->imageUrl = \Aqua\URL . $category->image;
			} else if($category->image !== '') {
				$category->imageUrl = $category->image;
			}
			$category->description = $data[5];
			$category->options     = (int)$data[6];
			$this->categories[$category->id] = $category;
		}
		App::cache()->store('content_categories.' . $this->contentType->id, $this->categories);
	}
}
