<?php
namespace Aqua\Ragnarok;

class Item
{
	public $id;

	/**
	 * @var \Aqua\Ragnarok\Server\CharMap
	 */
	public $charmap;
	/**
	 * @var int
	 */
	public $itemId;
	/**
	 * @var int
	 */
	public $name;
	/**
	 * @var int int
	 */
	public $type       = self::TYPE_INVENTORY;
	/**
	 * @var int
	 */
	public $amount     = 0;
	/**
	 * @var int
	 */
	public $charId     = 0;
	/**
	 * @var string
	 */
	public $character  = '';
	/**
	 * @var bool
	 */
	public $identified = true;
	/**
	 * @var int
	 */
	public $refine     = 0;
	/**
	 * @var array
	 */
	public $cards      = array();
	/**
	 * @var int
	 */
	public $attribute  = 0;
	/**
	 * @var int
	 */
	public $equip      = 0;
	/**
	 * @var int
	 */
	public $expire     = 0;
	/**
	 * @var int
	 */
	public $uniqueId   = 0;
	/**
	 * @var int
	 */
	public $slots      = 0;
	/**
	 * @var int
	 */
	public $itemType   = 0;
	/**
	 * @var int
	 */
	public $bound      = 0;
	/**
	 * @var array
	 */
	public $data       = array();

	const TYPE_INVENTORY = 0;
	const TYPE_CART      = 1;
	const TYPE_STORAGE   = 2;

	public function name($plain = true)
	{
		if(!$this->identified) {
			return __('ragnarok', 'unidentified');
		}
		$prefix = '';
		$suffix = '';
		$classes  = 'ac-item ';
		if($this->attribute === 1) { $classes.= 'ac-item-broken '; }
		if($this->refine) {
			$prefix.= "+$this->refine ";
			$classes.= "ac-item-refined ac-item-refine-{$this->refine} ";
		}
		if($this->cards[0] === 255 || $this->cards[0] === 254) {
			if($sc = ($this->cards[1] >> 8) / 5) {
				$prefix.= __('ragnarok-item-sc', $sc);
				$classes.= "ac-item-sc-$sc ";
			}
			if($this->character) {
				$prefix.= __('ragnarok', 'item-forger', $this->character) . ' ';
			} else {
				$prefix.= __('ragnarok', 'item-forger', __('ragnarok', 'unknown')) . ' ';
			}
			if($element = $this->cards[1] & 0x0F) {
				$prefix.= __('ragnarok-attribute', $element) . ' ';
				$classes.= "ac-item-attribute-$element ";
			}
		} else if(($this->itemType === 4 || $this->itemType === 5)) {
			$cards = array_fill_keys($this->cards, 0);
			unset($cards[0]);
			$enchant = 0;
			foreach($this->cards as $card) {
				if($card > 4700 && $card < 4899) {
					unset($cards[$card]);
					++$enchant;
				} else if($card > 500) {
					++$cards[$card];
				}
			}
			if($enchant) {
				$classes.= "ac-item-enchanted-{$enchant} ";
			}
			foreach($cards as $id => $count) {
				if($count) {
					$cnt = __('ragnarok-card-count', $count - 1);
					if(($card_name = __('ragnarok-card-prefix', $id)) !== $id) {
						$prefix.= "$cnt $card_name ";
					} else if($count > 1) {
						list($of, $card_name) = explode(' ', __('ragnarok-card-suffix', $id), 2);
						$suffix.= "$of $cnt $card_name ";
					} else {
						$suffix.= __('ragnarok-card-suffix', $id) . ' ';
					}
				}
			}
		}
		$name = $this->name;
		if($prefix) {
			$name = "$prefix $name";
		}
		if($suffix) {
			$name = "$name $suffix";
		}
		if(!$plain) {
			$name = "<span class=\"$classes\">$name</span>";
		}
		return $name;
	}

	public function card($num, &$card_id, &$type)
	{
		if($this->cards[0] === 255 || $this->cards[0] === 254) {
			$card_id = 0;
			$type    = -1;
		} else if(isset($this->cards[$num])) {
			$card_id = $this->cards[$num];
			$type = ($num >= ($this->slots -1) && $this->cards[$num] >= 4700 && $this->cards[$num] <= 4899);
		}
	}
}
