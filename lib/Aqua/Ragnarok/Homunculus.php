<?php
namespace Aqua\Ragnarok;

class Homunculus
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
	public $ownerId;
	/**
	 * @var string
	 */
	public $ownerName;
	/**
	 * @var int
	 */
	public $class;
	/**
	 * @var int
	 */
	public $previousClass;
	/**
	 * @var string
	 */
	public $name;
	/**
	 * @var int
	 */
	public $level;
	/**
	 * @var int
	 */
	public $experience;
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
	public $hp;
	/**
	 * @var int
	 */
	public $sp;
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
	public $agility;
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
	public $intimacy;
	/**
	 * @var int
	 */
	public $hunger;
	/**
	 * @var bool
	 */
	public $alive;
	/**
	 * @var bool
	 */
	public $vaporized;
	/**
	 * @var bool
	 */
	public $renamed;

	const TYPE_LIF         = 0x001;
	const TYPE_AMISTR      = 0x002;
	const TYPE_FILIR       = 0x004;
	const TYPE_VANILMIRTH  = 0x008;
	const TYPE_EVOLVED     = 0x010;
	const TYPE_MUTATED     = 0x020;
	const TYPE_S           = 0x040;

	public static function classType($class)
	{
		$type = 0;
		switch($class) {
			case 6005:
			case 6013:
				$type |= self::TYPE_MUTATED;
			case 6001:
			case 6009:
				$type |= self::TYPE_LIF; break;
				break;
			case 6006:
			case 6014:
				$type |= self::TYPE_MUTATED;
			case 6002:
			case 6010:
				 $type |= self::TYPE_AMISTR; break;
				break;
			case 6007:
			case 6015:
				$type |= self::TYPE_MUTATED;
			case 6003:
			case 6011:
				$type |= self::TYPE_FILIR; break;
				break;
			case 6008:
			case 6016:
				$type |= self::TYPE_MUTATED;
			case 6004:
			case 6012:
				$type |= self::TYPE_VANILMIRTH; break;
				break;
			default: return self::TYPE_S;
		}
		if($class > 6008) {
			$type |= self::TYPE_EVOLVED;
		}
		return $type;
	}

	public function name()
	{
		return ($this->renamed ? $this->name : $this->className());
	}

	public function className()
	{
		return __('ragnarok-homunculus', $this->class);
	}

	public function sprite()
	{
		return \Aqua\ROOT . '/assets/images/homunculus/' . $this->class . '.gif';
	}
}
