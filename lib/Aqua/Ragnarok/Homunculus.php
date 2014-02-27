<?php
namespace Aqua\Ragnarok;

class Homunculus
{
	public $id;
	public $ownerId;
	public $class;
	public $previousClass;
	public $name;
	public $level;
	public $intimacy;
	public $alive;
	public $vaporized;
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
			case 6006:
			case 6014:
				$type |= self::TYPE_MUTATED;
			case 6002:
			case 6010:
				 $type |= self::TYPE_AMISTR; break;
			case 6007:
			case 6015:
				$type |= self::TYPE_MUTATED;
			case 6003:
			case 6011:
				$type |= self::TYPE_FILIR; break;
			case 6008:
			case 6016:
				$type |= self::TYPE_MUTATED;
			case 6004:
			case 6012:
				$type |= self::TYPE_VANILMIRTH; break;
			default: return self::TYPE_S;
		}
		if($class > 6008) {
			$type |= self::TYPE_EVOLVED;
		}
		return $type;
	}

	public function name()
	{
		return $this->renamed ? $this->name : $this->className();
	}

	public function className()
	{
		return __('ragnarok.homunculus', $this->class);
	}

	public function sprite()
	{
		return \Aqua\ROOT . '/assets/images/homunculus/' . $this->class . '.gif';
	}
}
