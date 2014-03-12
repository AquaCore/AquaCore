<?php
use Aqua\Core\App;
use Aqua\UI\Sidebar;
/**
 * @var $form \Aqua\UI\Form
 * @var $page \Page\Admin\Settings
 */
$sidebar = new Sidebar();
ob_start(); ?>
<ul>
	<li><a href="#application-settings"><?php echo __('settings', 'application') ?></a></li>
	<li><a href="#user-settings"><?php echo __('settings', 'user') ?></a></li>
	<li><a href="#email-settings"><?php echo __('settings', 'email') ?></a></li>
	<li><a href="#content-settings"><?php echo __('settings', 'content') ?></a></li>
	<li><a href="#rss-settings"><?php echo __('settings', 'rss') ?></a></li>
	<li><a href="#captcha-settings"><?php echo __('settings', 'captcha') ?></a></li>
	<li><a href="#ragnarok-settings"><?php echo __('settings', 'ragnarok') ?></a></li>
</ul>
<?php
$sidebar->append('menu', array(
		'class' => 'ac-settings-menu ac-sidebar-navigation',
		array(
			'title' => __('settings', 'settings-categories'),
			'content' => ob_get_contents()
		)
	))->append('submit', array('class' => 'ac-sidebar-action', array(
		'content' => '<input type="submit" name="x-settings" value="' . __('application', 'submit') . '" form="settings">'
	)));
ob_end_clean();
$page->theme->template = 'sidebar-right';
$page->theme->set('sidebar', $sidebar);
$page->theme->footer->enqueueScript('settings-menu', true)
	->src($page->theme->url . '/scripts/settings.js');
$render_body = function($content) { return $content; };
?>
<form method="POST" enctype="multipart/form-data" id="settings" class="ac-settings-form">
	<div class="ac-settings-category" id="application-settings">
		<div class="page-title"><?php echo __('settings', 'application') ?></div>
		<table>
			<?php echo $form->render(null, $render_body, array( 'title', 'domain', 'base_dir', 'rewrite_url', 'language', 'ssl' )) ?>
			<tr>
				<td colspan="3">
					<div class="ac-settings-section">
						<div class="title"><?php echo __('settings', 'section-datetime') ?></div>
						<div class="separator"></div>
					</div>
				</td>
			</tr>
			<?php echo $form->render(null, $render_body, array( 'timezone', 'date_format', 'time_format', 'datetime_format' )) ?>
		</table>
	</div>
	<div class="ac-settings-category" id="user-settings">
		<div class="page-title"><?php echo __('settings', 'user') ?></div>

	</div>
	<div class="ac-settings-category" id="email-settings">
		<div class="page-title"><?php echo __('settings', 'email') ?></div>
		<table>
			<?php echo $form->render(null, $render_body, array( 'email_address', 'email_name' )) ?>
			<tr>
				<td colspan="3">
					<div class="ac-settings-section">
						<div class="title"><?php echo __('settings', 'section-smtp') ?></div>
						<div class="separator"></div>
					</div>
				</td>
			</tr>
			<?php echo $form->render(null, $render_body, array( 'use_smtp', 'smtp_host', 'smtp_port', 'smtp_encryption', 'smtp_username', 'smtp_password' )) ?>
		</table>
	</div>
	<div class="ac-settings-category" id="content-settings">
		<div class="page-title"><?php echo __('settings', 'content') ?></div>
		<table>
		</table>
	</div>
	<div class="ac-settings-category" id="rss-settings">
		<div class="page-title"><?php echo __('settings', 'rss') ?></div>
		<table>
			<?php if($url = App::settings()->get('rss')->get('image')) : ?>
			<tr>
				<td colspan="2"></td>
				<td>
					<div class="ac-rss-image-preview-wrapper">
						<div class="ac-rss-image-preview ac-delete-wrapper">
							<img src="<?php echo $url ?>">
							<input type="submit" name="x-delete-rss-image" class="ac-delete-button" value="">
						</div>
					</div>
				</td>
			</tr>
			<?php endif; ?>
			<?php echo $form->render(null, $render_body, array( 'rss_image', 'rss_title', 'rss_category', 'rss_description', 'rss_ttl', 'rss_copyright' )) ?>
		</table>
	</div>
	<div class="ac-settings-category" id="captcha-settings">
		<div class="page-title"><?php echo __('settings', 'captcha') ?></div>
		<table>
			<?php echo $form->render(null, $render_body, array( 'captcha_width', 'captcha_height', 'captcha_case' )) ?>
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
			<?php echo $form->render(null, $render_body, array( 'captcha_font', 'captcha_font_size', 'captcha_use_font_shadow', 'captcha_font_color', 'captcha_font_shadow_color', 'captcha_bg_color', 'captcha_noise_color', 'captcha_noise_tint', 'captcha_noise_level', 'captcha_lines_color', 'captcha_min_lines', 'captcha_max_lines', 'captcha_distortion_amp', 'captcha_distortion_period', 'captcha_characters', 'captcha_character_list', 'captcha_ttl', 'captcha_gc' )) ?>
			<tr>
				<td colspan="3">
					<div class="ac-settings-section">
						<div class="title"><?php echo __('settings', 'section-recaptcha') ?></div>
						<div class="separator"></div>
					</div>
				</td>
			</tr>
			<?php echo $form->render(null, $render_body, array( 'use_recaptcha', 'recaptcha_ssl', 'recaptcha_public_key', 'recaptcha_private_key' )) ?>
		</table>
	</div>
	<div class="ac-settings-category" id="ragnarok-settings">
		<div class="page-title"><?php echo __('settings', 'ragnarok') ?></div>
		<table>
			<?php echo $form->render(null, $render_body, array( 'ro_acc_url', 'ro_char_url', 'ro_item_script', 'ro_pincode_min', 'ro_pincode_max', 'ro_purchase_max' )) ?>
			<tr>
				<td colspan="3">
					<div class="ac-settings-section">
						<div class="title"><?php echo __('settings', 'section-emblem') ?></div>
						<div class="separator"></div>
					</div>
				</td>
			</tr>
			<?php echo $form->render(null, $render_body, array( 'ro_emblem_cache', 'ro_emblem_ttl', 'ro_emblem_compression' )) ?>
			<tr>
				<td colspan="3">
					<div class="ac-settings-section">
						<div class="title"><?php echo __('settings', 'section-sprite') ?></div>
						<div class="separator"></div>
					</div>
				</td>
			</tr>
			<?php echo $form->render(null, $render_body, array( 'ro_sprite_cache', 'ro_sprite_ttl', 'ro_sprite_compression' )) ?>
		</table>
	</div>
</form>
