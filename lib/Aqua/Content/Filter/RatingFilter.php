<?php
namespace Aqua\Content\Filter;

use Aqua\Content\AbstractFilter;
use Aqua\Content\ContentData;
use Aqua\Core\App;
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
		if(isset($data['rating_disabled']) && $data['rating_disabled']) $content->setMeta('rating-disabled', true);
	}

	public function afterCreate(ContentData $content, array &$data)
	{
		if(isset($data['rating_disabled']) && $data['rating_disabled']) $content->setMeta('rating-disabled', true);
	}

	public function forge(ContentData $content, array $data)
	{
		$content->meta['rating-votes-total'] = 0;
		$content->meta['rating-votes-sum']   = 0;
		$content->meta['rating-disabled']    = (isset($data['rating_disabled']) && $data['rating_disabled']);
	}

	/**
	 * @param \Aqua\Content\ContentData $content
	 * @return float|int
	 */
	public function contentData_ratingAverage(ContentData $content)
	{
		if(!($count = $content->getMeta('rating-votes-total', 0))) {
			return 0;
		} else {
			return ($content->getMeta('rating-votes-sum', 0) / $count);
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
		$weight      = max(0, min((int)$this->getOption('maxweight', 10), $weight));
		$tbl         = ac_table('content_ratings');
		$votes_total = $content->getMeta('rating-votes-total', 0);
		$votes_sum   = $content->getMeta('rating-votes-sum', 0);
		$old_weight  = $this->contentData_getRating($content, $user);
		$sth         = App::connection()->prepare("
		INSERT INTO `$tbl` (_user_id, _content_id, _weight)
		VALUES (?, ?, ?)
		ON DUPLICATE KEY UPDATE _weight = VALUES(_weight)
		");
		$sth->bindValue(1, $user->id, \PDO::PARAM_INT);
		$sth->bindValue(2, $content->uid, \PDO::PARAM_INT);
		$sth->bindValue(3, $weight, \PDO::PARAM_INT);
		$sth->execute();
		if($sth->rowCount()) {
			if($old_weight === null) {
				$votes_total += 1;
				$votes_sum += $weight;
			} else {
				$votes_sum -= (int)$old_weight;
				$votes_sum += $weight;
			}
			$content->setMeta(array(
					'rating-votes-total' => $votes_total,
					'rating-votes-sum'   => $votes_sum
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
