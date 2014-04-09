<?php
/**
 * Remove edges that are the background $color specified from a gd image resource
 *
 * @param resource $img
 * @param int      $color
 */
function image_trim(&$img, $color)
{
	$top = $bottom = $left = $right = 0;
	$width = imagesx($img);
	$height = imagesy($img);
	for(; $top < $height; ++$top) {
		for($x = 0; $x < $width; ++$x) {
			if(imagecolorat($img, $x, $top) !== $color) {
				break 2;
			}
		}
	}
	for(; $bottom < $height; ++$bottom) {
		for($x = 0; $x < $width; ++$x) {
			if(imagecolorat($img, $x, $height - $bottom - 1) !== $color) {
				break 2;
			}
		}
	}
	for(; $left < $width; ++$left) {
		for($y = 0; $y < $height; ++$y) {
			if(imagecolorat($img, $left, $y) !== $color) {
				break 2;
			}
		}
	}
	for(; $right < $width; ++$right) {
		for($y = 0; $y < $height; ++$y) {
			if(imagecolorat($img, $width - $right - 1, $y) !== $color) {
				break 2;
			}
		}
	}
	$new = imagecreatetruecolor(max(1, $width - ($left + $right)), max(1, $height - ($top + $bottom)));
	imagefill($new, 0, 0, $color);
	imagesavealpha($new, true);
	imagecopy($new, $img, 0, 0, $left, $top, imagesx($new), imagesy($new));
	imagedestroy($img);
	$img = $new;
}
