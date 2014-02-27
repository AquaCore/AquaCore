<?php
namespace Aqua\Ragnarok;

class Item
{
	public $id;

	/**
	 * @var \Aqua\Ragnarok\Server\CharMap
	 */
	public $charmap;
	public $itemId;
	public $name;
	public $type       = self::TYPE_INVENTORY;
	public $amount     = 0;
	public $charId     = 0;
	public $character  = '';
	public $identified = true;
	public $refine     = 0;
	public $cards      = array();
	public $attribute  = 0;
	public $equip      = 0;
	public $expire     = 0;
	public $uniqueId   = 0;
	public $slots      = 0;
	public $itemType   = 0;
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
					$cnt = __('ragnarok-card-count', $count);
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
