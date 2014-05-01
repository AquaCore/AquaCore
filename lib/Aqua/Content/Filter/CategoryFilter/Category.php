<?php
namespace Aqua\Content\Filter\CategoryFilter;

use Aqua\Content\ContentType;
use Aqua\Core\App;
use Aqua\SQL\Query;
use Aqua\UI\Tag\Meta;

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
	 * @var \Aqua\Core\Meta
	 */
	public $meta;

	public function contentType()
	{
		return ContentType::getContentType($this->contentTypeId, 'id');
	}

	/**
	 * @return string
	 */
	public function image()
	{
		if(empty($this->image)) {
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

	public function __sleep()
	{
		return array( 'contentTypeId', 'id', 'name', 'slug',
		              'description', 'image', 'imageUrl',
		              'isImageUploaded', 'protected', 'options' );
	}

	public function __wakeup()
	{
		$this->meta = new Meta(ac_table('category_meta'), $this->id);
	}
}
