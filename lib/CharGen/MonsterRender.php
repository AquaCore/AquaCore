<?php
namespace CharGen;

/**
* @fileoverview MonsterRender - Helper to render a monster, homunculus or pets.
* @author Vincent Thibault (alias KeyWorld - Twitter: @robrowser)
* @version 1.4.6
*/

class MonsterRender extends RORender
{
	/**
	 * Monster actions constants
	 */
	const ACTION_IDLE       = 0;
	const ACTION_WALK       = 1;
	const ACTION_ATTACK     = 2;
	const ACTION_HURT       = 3;
	const ACTION_DIE        = 4;
	const ACTION_FUNNY      = 5; // PET/Homunculus action


	/**
	 * Public options
	 */
	public $param = array(
		"class"     => 0,
		"accessory" => 0 // pets accessory
	);


	/**
	 * Public access
	 */
	public $body_animation = 0;


	/**
	 * Render, return an image
	 */
	public function render()
	{
		// Initialised the image
		$img = imagecreatetruecolor( $this->image_size[0], $this->image_size[1] );
		imagealphablending( $img, false);
		imagesavealpha( $img, true);
		imagefill( $img, 0, 0, imagecolorallocatealpha($img, 0xff, 0xff, 0xff, 0x7f) );

		$view = $this->param;

		// Draw shadow
		$this->renderImage( $img, array(
			"path"  => "data/sprite/shadow",
			"scale" => DB::get_shadow_factor($view['class'])
		));

		// Draw unit
		$spr = DB::get_entity_path($view['class']);
		$act = ( !empty($view['accessory']) ) ? DB::get_pet_accessory( $view['accessory'] ) : $spr . '.act';

		$this->renderImage( $img, array(
			"spr" => $spr . '.spr',
			"act" => $act
		));

		// Return resulted image
		return $img;
	}
}

?>
