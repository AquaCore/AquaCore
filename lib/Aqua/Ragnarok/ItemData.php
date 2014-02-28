<?php
namespace Aqua\Ragnarok;

use Aqua\Core\L10n;

class ItemData
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
	 * @var string
	 */
	public $enName = '';
	/**
	 * @var string
	 */
	public $jpName = '';
	/**
	 * @var int
	 */
	public $type = 0;
	/**
	 * @var int
	 */
	public $buyingPrice;
	/**
	 * @var int
	 */
	public $sellingPrice;
	/**
	 * @var float
	 */
	public $weight = 0;
	/**
	 * @var int
	 */
	public $attack = 0;
	/**
	 * @var int
	 */
	public $mattack = 0;
	/**
	 * @var int
	 */
	public $defence = 0;
	/**
	 * @var int
	 */
	public $range = 0;
	/**
	 * @var int
	 */
	public $slots = 0;
	/**
	 * @var int
	 */
	public $equipJob;
	/**
	 * @var int
	 */
	public $equipUpper;
	/**
	 * @var int
	 */
	public $equipGender;
	/**
	 * @var int
	 */
	public $equipLocation;
	/**
	 * @var int
	 */
	public $equipLevelMin;
	/**
	 * @var int
	 */
	public $equipLevelMax;
	/**
	 * @var int
	 */
	public $weaponLevel;
	/**
	 * @var bool
	 */
	public $refineable;
	/**
	 * @var int
	 */
	public $look;
	/**
	 * @var string
	 */
	public $description;
	/**
	 * @var string
	 */
	public $scriptUse;
	/**
	 * @var string
	 */
	public $scriptEquip;
	/**
	 * @var string
	 */
	public $scriptUnequip;
	/**
	 * @var int
	 */
	public $shopPrice;
	/**
	 * @var int
	 */
	public $shopCategoryId;
	/**
	 * @var bool
	 */
	public $inCashShop = false;
	/**
	 * @var bool
	 */
	public $custom = false;
	public $url;

	const ITEM_LOCATION = 1;
	const ITEM_GENDER   = 2;
	const ITEM_UPPER    = 3;
	const ITEM_JOB      = 4;

	public function isEquipment()
	{
		return ($this->type === 4 || $this->type === 5);
	}

	public function location($delimiter = ', ')
	{
		return implode($delimiter, self::getData($this->equipLocation, self::ITEM_LOCATION));
	}

	public function upper($delimiter = ', ')
	{
		return implode($delimiter, self::getData($this->equipUpper, self::ITEM_UPPER));
	}

	public function jobs($delimiter = ', ')
	{
		return implode($delimiter, self::getData($this->equipJob, self::ITEM_JOB));
	}

	public function gender($delimiter = ', ')
	{
		return implode($delimiter, self::getData($this->equipGender, self::ITEM_GENDER));
	}

	public function type()
	{
		return __('ragnarok-item-type', $this->type);
	}

	public function url(array $options)
	{
		return $this->charmap->uri->url();
	}

	public static function getData($data, $type)
	{
		switch($type) {
			case self::ITEM_LOCATION:
				$db = L10n::getDefault()->getNamespace('ragnarok-equip-location');
				break;
			case self::ITEM_GENDER:
				$db = L10n::getDefault()->getNamespace('ragnarok-equip-gender');
				break;
			case self::ITEM_UPPER:
				$db = L10n::getDefault()->getNamespace('ragnarok-equip-upper');
				break;
			case self::ITEM_JOB:
				$db = L10n::getDefault()->getNamespace('ragnarok-equip-job');
				break;
			default: return array();
		}
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
}
