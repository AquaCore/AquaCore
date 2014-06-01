<?php
namespace Aqua\Ragnarok;

use Aqua\Core\L10n;

class Mob
{
	/**
	 * @var \Aqua\Ragnarok\Server\CharMap
	 */
	public $charmap;
	/**
	 * @var
	 */
	public $id;
	/**
	 * @var string
	 */
	public $iName;
	/**
	 * @var string
	 */
	public $kName;
	/**
	 * @var int
	 */
	public $level;
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
	public $minAttack;
	/**
	 * @var int
	 */
	public $maxAttack;
	/**
	 * @var int
	 */
	public $defence;
	/**
	 * @var int
	 */
	public $mDefence;
	/**
	 * @var int
	 */
	public $strength;
	/**
	 * @var int
	 */
	public $agility;
	/**
	 * @var int
	 */
	public $vitality;
	/**
	 * @var int
	 */
	public $intelligence;
	/**
	 * @var int
	 */
	public $dexterity;
	/**
	 * @var int
	 */
	public $luck;
	/**
	 * @var int
	 */
	public $attackRange;
	/**
	 * @var int
	 */
	public $skillRange;
	/**
	 * @var int
	 */
	public $sight;
	/**
	 * @var int
	 */
	public $size;
	/**
	 * @var int
	 */
	public $race;
	/**
	 * @var int
	 */
	public $element;
	/**
	 * @var int
	 */
	public $mode;
	/**
	 * @var int
	 */
	public $baseExp;
	/**
	 * @var int
	 */
	public $jobExp;
	/**
	 * @var int
	 */
	public $mvpExp;
	/**
	 * @var
	 */
	public $cardId;
	/**
	 * @var
	 */
	public $speed;
	/**
	 * @var
	 */
	public $aDelay;
	/**
	 * @var
	 */
	public $aMotion;
	/**
	 * @var
	 */
	public $dMotion;
	/**
	 * @var
	 */
	public $cardDropRate;
	/**
	 * @var bool
	 */
	public $custom;

	/**
	 * @return array
	 */
	public function mode()
	{
		$data = $this->mode;
		$db = L10n::getNamespace('ragnarok-mob-mode');
		$matches = array();
		do {
			$key = key($db);
			if($key && ($data & $key) === $key) {
				$matches[] = current($db);
				$data ^= $key;
			}
		} while($key && next($db) !== false);
		return $matches;
	}

	/**
	 * @return string
	 */
	public function size()
	{
		return __('ragnarok-size', $this->size);
	}

	/**
	 * @return string
	 */
	public function element()
	{
		return __('ragnarok-element', ($this->element % 10));
	}

	/**
	 * @return float
	 */
	public function elementLevel()
	{
		return floor($this->element / 20);
	}

	/**
	 * @return string
	 */
	public function race()
	{
		return __('ragnarok-race', $this->race);
	}

	/**
	 * @param int $precision
	 * @return array
	 */
	public function drops($precision = 3)
	{
		return $this->charmap->mobDrops($this->id, $precision);
	}
}
