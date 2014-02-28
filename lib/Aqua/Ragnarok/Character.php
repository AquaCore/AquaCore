<?php
namespace Aqua\Ragnarok;

use Aqua\Core\App;
use Aqua\Event\Event;
use Aqua\Ragnarok\Server\CharMap;

class Character
{
	/**
	 * @var \Aqua\Ragnarok\Server\CharMap
	 */
	public $charmap;
	/**
	 * @var int
	 */
	public $id;
	/**
	 * @var int
	 */
	public $accountId;
	/**
	 * @var string
	 */
	public $name;
	/**
	 * Slot position
	 * @var int
	 */
	public $slot;
	/**
	 * @var int
	 */
	public $class;
	/**
	 * @var int
	 */
	public $baseLevel;
	/**
	 * @var int
	 */
	public $jobLevel;
	/**
	 * @var int
	 */
	public $baseExp;
	/**
	 * @var int
	 */
	public $jobExp;
	/**
	 * @var bool
	 */
	public $online = false;
	/**
	 * @var int
	 */
	public $zeny;
	/**
	 * @var int
	 */
	public $homunculusId;
	/**
	 * @var int
	 */
	public $elementalId;
	/**
	 * @var int
	 */
	public $petId;
	/**
	 * @var int
	 */
	public $partnerId;
	/**
	 * @var int
	 */
	public $motherId;
	/**
	 * @var int
	 */
	public $fatherId;
	/**
	 * @var int
	 */
	public $childId;
	/**
	 * @var int
	 */
	public $partyId;
	/**
	 * @var int
	 */
	public $guildId;
	/**
	 * @var string
	 */
	public $guildName;
	/**
	 * @var string
	 */
	public $lastMap;
	/**
	 * @var int
	 */
	public $lastX;
	/**
	 * @var int
	 */
	public $lastY;
	/**
	 * @var string
	 */
	public $saveMap;
	/**
	 * @var int
	 */
	public $saveX;
	/**
	 * @var int
	 */
	public $saveY;
	/**
	 * @var int
	 */
	public $fame;
	/**
	 * @var int
	 */
	public $karma;
	/**
	 * @var int
	 */
	public $manner;
	/**
	 * @var int
	 */
	public $option;
	/**
	 * @var int
	 */
	public $CPOptions;
	/**
	 * @var int
	 */
	public $strength;
	/**
	 * @var int
	 */
	public $vitality;
	/**
	 * @var int
	 */
	public $dexterity;
	/**
	 * @var int
	 */
	public $intelligence;
	/**
	 * @var int
	 */
	public $luck;
	/**
	 * @var int
	 */
	public $hp;
	/**
	 * @var int
	 */
	public $sp;
	/**
	 * @var int
	 */
	public $maxHp;
	/**
	 * @var int
	 */
	public $maxSp;
	/**
	 * @var int
	 */
	public $statusPoints;
	/**
	 * @var int
	 */
	public $skillPoints;
	/**
	 * @var int
	 */
	public $deleteDate;
	/**
	 * @var array
	 */
	protected $_skills;
	/**
	 * @var
	 */
	protected $_uri;

	const OPT_DISABLE_WHO_IS_ONLINE     = 0x001; // Hide character from online players list
	const OPT_DISABLE_MAP_WHO_IS_ONLINE = 0x002; // Hide character's map from online players list
	const OPT_DISABLE_ZENY_LADDER       = 0x004; // Hide character from zeny ladder

	public function divorce($keep_child = false)
	{
		if(!$this->partnerId) {
			return false;
		}
		$ids = array( $this->id, $this->partnerId );
		$query = "UPDATE {$this->charmap->table('char')} SET partner_id = 0";
		if(!$keep_child && $this->childId) {
			$query.= ', father = 0, mother = 0, child = 0 WHERE char_id IN (? ,?, ?)';
			$ids[] = $this->childId;
		} else {
			$query.= ' WHERE char_id IN (?, ?)';
		}
		$sth = $this->charmap->connection()->prepare($query);
		if($sth->execute($ids) && $sth->rowCount()) {
			$partner = $this->charmap->character($this->partnerId, 'id');
			$feedback = array( $this, $partner, (bool)$keep_child );
			Event::fire('ragnarok.divorce', $feedback);
			$this->charmap->log->logDivorce($this, $keep_child);
			$this->partnerId = 0;
			if(!$keep_child) {
				$this->childId = 0;
			}
			return true;
		} else {
			return false;
		}
	}

	public function update(array $options)
	{
		$columns = array(
		);
		$options = array_intersect_key($options, $columns);
		if(empty($options)) return false;
		$update = '';
		foreach($options as $key => &$value) {
			if(isset($columns[$key][2])) {
				$args = array_slice($columns[$key], 2);
				array_unshift($args, $value);
				$value = call_user_func_array($columns[$key][2], $args);
			}
			$update.= " `{$columns[$key][1]}` = ?, ";
		}
		$update = substr($update, 0, -1);
		$sth = $this->charmap->connection()->prepare("
		UPDATE {$this->charmap->table('char')}
		SET {$update}
		WHERE char_id = ?
		LIMIT 1
		");
		$options[] = $this->id;
		$sth->execute(array_values($options));
		array_pop($options);
		$feedback = array( $this, $options );
		Event::fire('ragnarok.update-char', $feedback);
		return true;
	}

	public function skill()
	{
		if($this->_skills === null) {
			$sth = $this->charmap->connection()->prepare("
			SELECT id, lv, flag
			FROM {$this->charmap->table('skill')}
			WHERE char_id = ?
			");
			$sth->bindValue(1, $this->id, \PDO::PARAM_INT);
			$sth->execute();
			while($data = $sth->fetch(\PDO::FETCH_NUM)) {
				$this->_skills[$data[0]] = array(
					'level' => intval($data[1]),
					'flag'  => intval($data[2])
				);
			}
		}
		return $this->_skills;
	}

	public function job()
	{
		return __('ragnarok-jobs', $this->class);
	}

	public function homunculus()
	{
		if($this->homunculusId) {
			return null;
		} else {
		}
	}

	public function guild()
	{

	}

	public function party()
	{

	}

	public function account()
	{
		return $this->charmap->server->login->get($this->accountId);
	}

	public function url(array $options)
	{
		if(!$this->_uri) {
			$this->_uri = clone $this->charmap->uri;
			$this->_uri->path[] = 'c';
			if(App::settings()->get('ragnarok')->get('char_name_url', true)) {
				$this->_uri->path[] = $this->name;
			} else {
				$this->_uri->path[] = $this->id;
			}
		}
		return $this->_uri->url($options);
	}

	public function head()
	{
		return ac_char_head($this);
	}

	public function sprite()
	{
		return ac_char_body($this);
	}
}
