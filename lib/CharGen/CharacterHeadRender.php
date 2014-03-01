<?php
namespace CharGen;

/**
* @fileoverview CharacterHeadRender - Helper to render the character head (and hats)
* @author Vincent Thibault (alias KeyWorld - Twitter: @robrowser)
* @version 1.4.5
*/

class CharacterHeadRender extends RORender
{
	/**
	 * Character options
	 */
	public $param = array(
		"sex"           => "M",
		"hair"          =>  0 ,
		"hair_color"    =>  0 ,
		"head_top"      =>  0 ,
		"head_mid"      =>  0 ,
		"head_bottom"   =>  0 ,
	);


	/**
	 * Image setting
	 */
	public $image_size    = array( 80,  80 );
	public $dot_reference = array( 40, 120 );
	public $doridori      = 0;


	/**
	 * Bind parameter with some SQL datas
	 */
	public function loadFromSqlData($args)
	{
		foreach( $this->param as $type => &$view )
		{
			if ( isset($args[$type]) )
				$view = $args[$type];
		}
	}


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

		// Draw head
		$this->renderImage( $img, array(
			"path"      => DB::get_head_path( $view['hair'], $view['sex'] ),
			"pal"       => DB::get_head_pal_path( $view['hair'], $view['sex'], $view['hair_color'] )
		));

		// Draw head top
		if ( !empty($view['head_top']) )
			 $this->renderImage( $img, array(
			 	"path"      => DB::get_hat_path( $view['head_top'], $view['sex'] )
			));

		// Draw head mid
		if ( !empty($view['head_mid']) && $view['head_mid'] !== $view['head_top'] ) // Don't render the same sprite twice
			$this->renderImage( $img, array(
			 	"path"      => DB::get_hat_path( $view['head_mid'], $view['sex'] )
			));

		// Draw head bot
		if ( !empty($view['head_bottom']) && $view['head_bottom'] !== $view['head_mid'] ) // Don't render the same sprite twice
			$this->renderImage( $img, array(
			 	"path"      => DB::get_hat_path( $view['head_bottom'], $view['sex'] )
			));

		// Return resulted image
		return $img;
	}
}

?>