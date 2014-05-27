<?php
namespace Aqua\Content\Filter;

use Aqua\Content\AbstractFilter;
use Aqua\Content\ContentData;
use Aqua\Core\App;
use Aqua\SQL\Query;
use Aqua\User\Account;

class RatingFilter
extends AbstractFilter
{
	public function afterDelete(ContentData $content)
	{
		$tbl = ac_table('content_ratings');
		$sth = App::connection()->prepare("
		DELETE FROM `$tbl`
		WHERE _content_id = ?
		");
		$sth->bindValue(1, $content->uid, \PDO::PARAM_INT);
		$sth->execute();
	}

	public function afterUpdate(ContentData $content, array $data, array &$updated)
	{
		if(isset($data['rating_disabled']) && $data['rating_disabled']) $content->meta->set('ratingDisabled', true);
	}

	public function afterCreate(ContentData $content, array &$data)
	{
		if(isset($data['rating_disabled']) && $data['rating_disabled']) $content->meta->set('ratingDisabled', true);
	}

	public function forge(ContentData $content, array $data)
	{
		$content->meta['ratingVotesTotal'] = 0;
		$content->meta['ratingVotesSum']   = 0;
		$content->meta['ratingDisabled']   = (isset($data['rating_disabled']) && $data['rating_disabled']);
	}

	/**
	 * @param \Aqua\Content\ContentData $content
	 * @return float|int
	 */
	public function contentData_ratingAverage(ContentData $content)
	{
		if(($count = (int)$content->meta->get('ratingVotesTotal', 0)) < 1) {
			return 0;
		} else {
			return ($content->meta->get('ratingVotesSum', 0) / $count);
		}
	}

	/**
	 * @param \Aqua\Content\ContentData $content
	 * @param \Aqua\User\Account        $user
	 * @param int                       $weight
	 * @return bool
	 */
	public function contentData_rate(ContentData $content, Account $user, $weight)
	{
		$weight     = max(0, min((int)$this->getOption('maxweight', 10), $weight));
		if($content->meta->exists('ratingVotesTotal') &&
		   $content->meta->exists('ratingVotesSum')) {
			$votesTotal = $content->meta->get('ratingVotesTotal', 0);
			$votesSum   = $content->meta->get('ratingVotesSum', 0);
		} else {
			$select = Query::select(App::connection())
				->columns(array( 'count' => 'COUNT(1)',
				                 'sum'   => 'SUM(_weight)' ))
				->setColumnType(array( 'count' => 'integer',
				                       'sum'   => 'integer' ))
				->from(ac_table('content_ratings'))
				->where(array( '_content_id' => $content->uid ))
				->query();
			$votesTotal = $select->get('count');
			$votesSum   = $select->get('sum');
		}
		$oldWeight  = $this->contentData_getRating($content, $user);
		$sth        = App::connection()->prepare(sprintf('
		INSERT INTO `%s` (_user_id, _content_id, _weight)
		VALUES (?, ?, ?)
		ON DUPLICATE KEY UPDATE _weight = VALUES(_weight)
		', ac_table('content_ratings')));
		$sth->bindValue(1, $user->id, \PDO::PARAM_INT);
		$sth->bindValue(2, $content->uid, \PDO::PARAM_INT);
		$sth->bindValue(3, $weight, \PDO::PARAM_INT);
		$sth->execute();
		if($sth->rowCount()) {
			if($oldWeight === null) {
				$votesTotal += 1;
				$votesSum += $weight;
			} else {
				$votesSum -= (int)$oldWeight;
				$votesSum += $weight;
			}
			$content->meta->set(array(
					'ratingVotesTotal' => $votesTotal,
					'ratingVotesSum'   => $votesSum
				));
		}

		return $weight;
	}

	/**
	 * @param \Aqua\Content\ContentData $content
	 * @param \Aqua\User\Account        $user
	 * @return int|null
	 */
	public function contentData_getRating(ContentData $content, Account $user = null)
	{
		if(!$user || $content->forged) {
			return null;
		}
		$tbl = ac_table('content_ratings');
		$sth = App::connection()->prepare("
		SELECT _weight
		FROM `$tbl`
		WHERE _user_id = ?
		AND _content_id = ?
		LIMIT 1
		");
		$sth->bindValue(1, $user->id, \PDO::PARAM_INT);
		$sth->bindValue(2, $content->uid, \PDO::PARAM_INT);
		$sth->execute();
		if($sth->rowCount()) {
			return (int)$sth->fetchColumn(0);
		} else {
			return null;
		}
	}
}
