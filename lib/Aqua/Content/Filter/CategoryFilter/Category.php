<?php
namespace Aqua\Content\Filter\CategoryFilter;

use Aqua\Content\ContentType;
use Aqua\Core\App;
use Aqua\SQL\Query;

class Category
{
	/**
	 * @var int
	 */
	public $contentTypeId;
	/**
	 * @var int
	 */
	public $id;
	/**
	 * @var string
	 */
	public $name;
	/**
	 * @var string
	 */
	public $slug;
	/**
	 * @var string
	 */
	public $description;
	/**
	 * @var string
	 */
	public $image;
	/**
	 * @var string
	 */
	public $imageUrl;
	/**
	 * @var bool
	 */
	public $isImageUploaded = false;
	/**
	 * @var bool
	 */
	public $protected = false;
	/**
	 * @var int
	 */
	public $options;
	/**
	 * @var array
	 */
	public $meta;
	/**
	 * @var bool
	 */
	protected $_metaLoaded = false;

	/**
	 * @return string
	 */
	public function image()
	{
		if($this->image === null) {
			return \Aqua\BLANK;
		} else {
			return $this->imageUrl;
		}
	}

	/**
	 * @param array $data
	 * @return bool
	 */
	public function update(array $data)
	{
		return ContentType::getContentType($this->contentTypeId)->updateCategory($this, $data);
	}

	/**
	 * @return bool
	 */
	public function delete()
	{
		return ContentType::getContentType($this->contentTypeId)->deleteCategory($this);
	}

	public function removeImage($update = false)
	{
		if($this->isImageUploaded) {
			@unlink(\Aqua\ROOT . $this->image);
		}
		if($update) {
			$this->update(array( 'image' => '' ));
		}
	}

	public function count()
	{
		return Query::select(App::connection())
			->columns(array( 'count' => 'COUNT(1)' ))
			->setColumnType(array( 'count' => 'integer' ))
			->from(ac_table('content_categories'))
			->where(array( '_category_id' => $this->id ))
			->groupBy('_content_id')
			->query()
			->get('count', 0);
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
	 * @return \Aqua\Content\Filter\CategoryFilter\Category
	 */
	public function setMeta($key, $value = null)
	{
		if(!is_array($key)) $key = array( $key => $value );
		$tbl = ac_table('category_meta');
		$sth = App::connection()->prepare("
		INSERT INTO `$tbl` (_category_id, _key, _val)
		VALUES (:id, :key, :val)
		ON DUPLICATE KEY UPDATE _val = :val
		");
		foreach($key as $k => $val) {
			$sth->bindValue(':id', $this->id, \PDO::PARAM_INT);
			$sth->bindValue(':key', $k, \PDO::PARAM_STR);
			$sth->bindValue(':val', serialize($val), \PDO::PARAM_LOB);
			$sth->execute();
		}
		if($this->_metaLoaded) $this->meta = array_merge($this->meta, $key);

		return $this;
	}

	/**
	 * @param string|array $key
	 * @return \Aqua\Content\Filter\CategoryFilter\Category
	 */
	public function deleteMeta($key)
	{
		if(!is_array($key)) $key = array( $key );
		$tbl = ac_table('category_meta');
		$sth = App::connection()->prepare("
		DELETE FROM `$tbl`
		WHERE _comment_id = ? AND _key = ?
		");
		foreach($key as $k) {
			$sth->bindValue(1, $this->id, \PDO::PARAM_INT);
			$sth->bindValue(2, $k, \PDO::PARAM_STR);
			$sth->execute();
			if($this->_metaLoaded) unset($this->meta[$k]);
		}

		return $this;
	}

	/**
	 * @return \Aqua\Content\Filter\CategoryFilter\Category
	 */
	public function loadMeta()
	{
		$tbl = ac_table('category_meta');
		$sth = App::connection()->prepare("
		SELECT _key, _val
		FROM `$tbl`
		WHERE _comment_id = ?
		");
		$sth->bindValue(1, $this->id, \PDO::PARAM_INT);
		$sth->execute();
		$this->meta = array();
		while($data = $sth->fetch(\PDO::FETCH_ASSOC)) {
			$this->meta[$data[0]] = unserialize($data[1]);
		}
		$this->_metaLoaded = true;

		return $this;
	}

}
