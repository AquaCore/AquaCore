<?php
namespace Aqua\Content\Filter;

use Aqua\Content\AbstractFilter;
use Aqua\Content\ContentData;
use Aqua\Core\App;

class ScheduleFilter
extends AbstractFilter
{
	const STATUS_SCHEDULED = 2;

	public function parseData(ContentData $content, array &$data)
	{
		if((int)$data['status'] === self::STATUS_SCHEDULED && (int)$data['publish_date'] <= time()) {
			$sth = App::connection()->prepare(sprintf('
			UPDATE %s
			SET _status = ?
			WHERE _uid = ?
			LIMIT 1
			', ac_table('content')));
			$sth->bindValue(1, ContentData::STATUS_PUBLISHED, \PDO::PARAM_INT);
			$sth->bindValue(2, $data['uid'], \PDO::PARAM_INT);
			$sth->execute();
			$data['status'] = ContentData::STATUS_PUBLISHED;
		}
	}

	/**
	 * @param \Aqua\Content\ContentData $content
	 * @param array                     $data
	 * @return void
	 */
	public function beforeUpdate(ContentData $content, array &$data)
	{
		$this->schedule($data);
	}

	/**
	 * @param array $data
	 * @return void
	 */
	public function beforeCreate(array &$data)
	{
		$this->schedule($data);
	}

	/**
	 * @param array $data
	 * @return bool
	 */
	public function schedule(array &$data)
	{
		if(array_key_exists('publish_date', $data) && $data['publish_date'] > time() &&
		   (!array_key_exists('status', $data) || $data['status'] === ContentData::STATUS_PUBLISHED)) {
			$data['status'] = self::STATUS_SCHEDULED;

			return true;
		}

		return false;
	}
}
