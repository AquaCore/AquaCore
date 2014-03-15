<?php
namespace Aqua\Content\Filter;

use Aqua\Content\AbstractFilter;
use Aqua\Content\ContentData;
use Aqua\Core\App;

class ArchiveFilter
extends AbstractFilter
{
	const STATUS_ARCHIVED = 3;

	public function parseData(ContentData $content, array &$data)
	{
		if((int)$data['status'] === ContentData::STATUS_PUBLISHED &&
		   !$content->getMeta('disable-archive', false)) {
			if(!($date = (int)$content->getMeta('archive-date'))) {
				$date = $data['publish_date'] + ($this->getOption('interval', 20) * 86400);
			}
			if($date > time()) {
				return;
			}
			$tbl = ac_table('content');
			$sth = App::connection()->prepare("
			UPDATE `$tbl`
			SET _status = ?
			WHERE _uid = ?
			LIMIT 1
			");
			$sth->bindValue(1, self::STATUS_ARCHIVED, \PDO::PARAM_INT);
			$sth->bindValue(2, $data['uid'], \PDO::PARAM_INT);
			$sth->execute();
			$data['status'] = self::STATUS_ARCHIVED;
		}
	}


	public function beforeUpdate(ContentData $content, array &$data)
	{
		$this->archive($content, $data);
	}

	public function afterCreate(ContentData $content, array &$data)
	{
		$this->archive($content, $data);
	}

	public function contentData_isArchived(ContentData $content)
	{
		return ($content->status === self::STATUS_ARCHIVED);
	}

	public function archive(ContentData $content, array &$data)
	{
		if(array_key_exists('archiving', $data) && !$data) {
			$content->setMeta('disable-archiving', true);
			return null;
		}
		if(array_key_exists('archive_date', $data) && $data['archive_date']) {
			$content
				->deleteMeta('disable-archiving')
				->setMeta('archive-date', (int)$data['archive_date']);
			if($data['archive_date'] <= $data['publish_date'] &&
			   (int)$data['status'] === ContentData::STATUS_PUBLISHED) {
				$data['status'] = self::STATUS_ARCHIVED;
				return true;
			}
		} else {
			$content->deleteMeta('archive-date');
		}
		return null;
	}
}
