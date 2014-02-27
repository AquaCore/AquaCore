<?php
use Aqua\Core\App;
/**
 * @var $form \Aqua\UI\Form
 * @var $page \Page\Admin\Settings
 */
include __DIR__ . '/settings-sidebar.php';
$option = &$menu->get('captcha');
$option['class'] = 'active';
?>
<table class="ac-settings-form">
	<?php echo $form->render(null, false, array( 'width', 'height', 'case_sensitive' )) ?>
	<?php if($file = App::settings()->get('captcha')->get('font_file')) : ?>
		<tr>
			<td colspan="3">
				<div class="ac-captcha-font-preview ac-delete-wrapper">
					<?php
					$height = 30;
					$name = ac_font_info($file, 1);
					$bb = imageftbbox(15, 0, $file, $name);
					$tx = $bb[4] - $bb[0];
					$ty = $bb[5] - $bb[1];
					$width = abs($bb[0]) + abs($bb[2]);
					$y = floor($height / 2 - $ty / 2 - $bb[1]);
					$img = imagecreatetruecolor($width, $height);
					imagefilledrectangle($img, 0, 0, $width, $height, imagecolorallocate($img, 232, 235, 239));
					imagettftext($img, 15, 0, 0, $y, imagecolorallocate($img, 34, 150, 239), $file, $name);
					ob_start();
					imagepng($img);
					$base64 = base64_encode(ob_get_contents());
					ob_end_clean();
					imagedestroy($img);
					$base64 = "data:image/png;base64,$base64";
					?>
					<img src="<?php echo $base64 ?>">
					<input type="submit" name="x-delete-captcha-font" class="ac-delete-button" value="">
				</div>
			</td>
		</tr>
	<?php endif; ?>
	<?php echo $form->render(null, false, array( 'font_file', 'font_size', 'use_font_shadow', 'font_color', 'font_shadow_color', 'bg_color', 'noise_color', 'noise_level', 'lines_color', 'min_lines', 'max_lines', 'distortion_amp', 'distortion_period', 'length', 'characters', 'expire', 'gc' )) ?>
	<tr>
		<td colspan="3">
			<div class="ac-settings-section">
				<div class="title"><?php echo __('settings', 'section-recaptcha') ?></div>
				<div class="separator"></div>
			</div>
		</td>
	</tr>
	<?php echo $form->render(null, false, array( 'use_recaptcha', 'recaptcha_ssl', 'recaptcha_public_key', 'recaptcha_private_key' )) ?>
</table>
