<?php
namespace Aqua\Captcha;

use Aqua\Core\App;
use Aqua\Core\Settings;

class Captcha
{
	/**
	 * @var \Aqua\Core\Settings
	 */
	public $settings;

	const CAPTCHA_INCOMPLETE       = 0;
	const CAPTCHA_INCORRECT_ANSWER = 1;
	const CAPTCHA_SUCCESS          = 2;

	/**
	 * @param \Aqua\Core\Settings $settings
	 */
	public function __construct(Settings $settings)
	{
		$this->settings = $settings;
		if(ac_probability($this->settings->get('gc_probability', 0))) {
			$this->gc();
		}
	}

	/**
	 * Replace a captcha code with a new random one
	 *
	 * @param string $key
	 * @param string $ipAddress
	 * @return bool
	 */
	public function refresh($key, $ipAddress)
	{
		if(!$this->checkValidKey($key)) {
			return false;
		}
		$sth = App::connection()->prepare(sprintf('
		UPDATE %s
		SET _code = :code
		WHERE _ip_address = :ip
		AND id = :id
		LIMIT 1
		', ac_table('captcha')));
		$sth->bindValue(':id', $key, \PDO::PARAM_STR);
		$sth->bindValue(':ip', $ipAddress, \PDO::PARAM_LOB);
		$sth->bindValue(':code', $this->generateCode(), \PDO::PARAM_STR);

		return ($sth->execute() && $sth->rowCount());
	}

	/**
	 * Create a new captcha key
	 *
	 * @param string $ipAddress
	 * @return string The captcha key
	 */
	public function create($ipAddress)
	{
		$sth = App::connection()->prepare(sprintf('
		INSERT INTO %s (id, _ip_address, _code)
		VALUES (:id, :ip, :code)
		ON DUPLICATE KEY UPDATE _code = VALUES(_code)
		', ac_table('captcha')));
		$key = bin2hex(secure_random_bytes(16));
		$sth->bindValue(':id', $key, \PDO::PARAM_STR);
		$sth->bindValue(':ip', $ipAddress, \PDO::PARAM_LOB);
		$sth->bindValue(':code', $this->generateCode(), \PDO::PARAM_STR);
		$sth->execute();

		return $key;
	}

	/**
	 * Get a user's captcha code
	 *
	 * @param string $key
	 * @param string $ip_address
	 * @return null|string
	 */
	public function getCode($key, $ip_address)
	{
		if(!$this->checkValidKey($key)) {
			return null;
		}
		$sth = App::connection()->prepare(sprintf('
		SELECT _code
		FROM %s
		WHERE _ip_address = :ip
		AND id = :id
		AND _date > DATE_SUB(NOW(), INTERVAL :interval MINUTE)
		LIMIT 1
		', ac_table('captcha')));
		$sth->bindValue(':id', $key, \PDO::PARAM_STR);
		$sth->bindValue(':ip', $ip_address, \PDO::PARAM_LOB);
		$sth->bindValue(':interval', $this->settings->get('expire', 30), \PDO::PARAM_LOB);
		$sth->execute();

		return $sth->fetchColumn(0);
	}

	/**
	 * Validate a user's captcha input against the value
	 * stored in the database and deletes it.
	 *
	 * @param string $key
	 * @param string $ipAddress
	 * @param string $input
	 * @return int Error ID
	 * @see \Aqua\Captcha\Captcha::CAPTCHA_*
	 */
	public function validate($key, $ipAddress, $input)
	{
		if(!($code = $this->getCode($key, $ipAddress))) {
			return self::CAPTCHA_INCOMPLETE;
		}
		if(!$this->settings->get('case_sensitive', false)) {
			$code  = strtolower($code);
			$input = strtolower($input);
		}
		$this->delete($key, $ipAddress);
		if($input !== $code) {
			return self::CAPTCHA_INCORRECT_ANSWER;
		} else {
			return self::CAPTCHA_SUCCESS;
		}
	}

	/**
	 * @param string $key
	 * @param string $ip_address
	 */
	public function delete($key, $ip_address)
	{
		$sth = App::connection()->prepare(sprintf('
		DELETE FROM %s
		WHERE _ip_address = :ip AND id = :id
		LIMIT 1
		', ac_table('captcha')));
		$sth->bindValue(':id', $key, \PDO::PARAM_STR);
		$sth->bindValue(':ip', $ip_address, \PDO::PARAM_LOB);
		$sth->execute();
	}

	/**
	 * Delete expired captcha keys
	 */
	public function gc()
	{
		$sth = App::connection()->prepare(sprintf('
		DELETE FROM %s
		WHERE _date < DATE_SUB(NOW(), INTERVAL :interval MINUTE)
		', ac_table('captcha')));
		$sth->bindValue(':interval', $this->settings->get('expire', 30), \PDO::PARAM_INT);
		$sth->execute();
	}

	/**
	 * @return string
	 */
	public function generateCode()
	{
		$code       = '';
		$charLen    = mt_rand($this->settings->get('min_length', 4), $this->settings->get('max_length', 7));
		$characters = $this->settings->get('characters', '');
		$range      = strlen($characters);
		$len        = (int)(log($range, 2) / 8) + 1;
		for($i = 0; $i < $charLen; ++$i) {
			$code .= $characters[hexdec(bin2hex(secure_random_bytes($len))) % $range];
		}

		return $code;
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function checkValidKey($key)
	{
		return (strlen($key) === 32 && ctype_xdigit($key));
	}

	/**
	 * @param string $key
	 * @param string $ipAddress
	 * @param int    $quality
	 * @return bool
	 */
	public function render($key, $ipAddress, $quality = 9)
	{
		if(!($captcha_code = $this->getCode($key, $ipAddress))) {
			return false;
		}
		$width  = $this->settings->get('width', 1);
		$height = $this->settings->get('height', 1);
		$img    = imagecreatetruecolor($width, $height);
		$this->drawBackground($img);
		$this->addNoise($img);
		$this->drawText($img, $captcha_code);
		$this->addLines($img);
		imagepng($img, null, $quality);
		imagedestroy($img);

		return true;
	}

	/**
	 * @param resource $img
	 */
	public function drawBackground(&$img)
	{
		$bgColor = $this->settings->get('background_color', 0xFFFFFF);
		$bgImage = $this->settings->get('background_image', null);
		$width   = $this->settings->get('width', 1);
		$height  = $this->settings->get('height', 1);
		$this->rgb($bgColor, $r, $g, $b);
		imagefilledrectangle($img, 0, 0, $width, $height, imagecolorallocate($img, $r, $g, $b));
		if(is_array($bgImage)) {
			$bgImage = $bgImage[array_rand($bgImage)];
		}
		if(substr($bgImage, 0, 9) === '/uploads/') {
			$bgImage = \Aqua\ROOT . $bgImage;
		}
		if($bgImage && is_readable($bgImage)) {
			$bg = null;
			switch(pathinfo($bgImage, PATHINFO_EXTENSION)) {
				case 'jpg':
				case 'jpeg':
					$bg = @imagecreatefromjpeg($bgImage);
					break;
				case 'png':
					$bg = @imagecreatefrompng($bgImage);
					break;
				case 'gif':
					$bg = @imagecreatefromgif($bgImage);
					break;
			}
			if(is_resource($bg)) {
				$w  = imagesx($bg);
				$h  = imagesy($bg);
				$x2 = $y2 = 0;
				$x  = ($width - $w) / 2;
				$y  = ($height - $h) / 2;
				if($x < 0) {
					$x2 = abs($x);
					$x  = 0;
				}
				if($y < 0) {
					$y2 = abs($y);
					$y  = 0;
				}
				imagecopyresized($img, $bg, $x, $y, $x2, $y2, $w, $h, $w, $h);
				imagedestroy($bg);
			}
		}
	}

	/**
	 * @param resource $img
	 */
	public function addNoise(&$img)
	{
		$width  = $this->settings->get('width', 1);
		$height = $this->settings->get('height', 1);
		if($this->settings->get('noise_level', 0) > 0) {
			$noise = min($this->settings->get('noise_level'), 10) / 14;
			$this->rgb($this->settings->get('noise_color', 0x707070), $r, $g, $b);
			$noiseColor[0] = imagecolorallocatealpha($img, $r, $g, $b, 60);
			$this->rgb($this->settings->get('noise_color_alt', 0xA6A6A6), $r, $g, $b);
			$noiseColor[1] = imagecolorallocatealpha($img, $r, $g, $b, 60);
			for($i = 0; $i < $width; ++$i) {
				for($j = 0; $j < $height; ++$j) {
					if((mt_rand() / mt_getrandmax()) < $noise) {
						imagesetpixel($img, $i, $j, $noiseColor[mt_rand(0, 1)]);
					}
				}
			}
		}
	}

	/**
	 * @param resource $img
	 * @param string   $captchaCode
	 */
	public function drawText(&$img, $captchaCode)
	{
		$width     = $this->settings->get('width', 1);
		$height    = $this->settings->get('height', 1);
		$size      = $this->settings->get('font_size', 15);
		$color     = $this->settings->get('font_color', 0x000000);
		$variation = $this->settings->get('font_color_variation', 0x848484);
		$fontFile  = $this->settings->get('font_file');
		if(is_array($fontFile)) {
			$fontFile = $fontFile[array_rand($fontFile)];
		}
		if(substr($fontFile, 0, 9) === '/uploads/') {
			$fontFile = \Aqua\ROOT . $fontFile;
		}
		if(!$fontFile || !is_readable($fontFile)) {
			$os_name = php_uname('s');
			if(strtoupper(substr($os_name, 0, 3)) === 'WIN') {
				$fontFile = 'C:/Windows/Fonts';
			} else if(strtoupper(substr($os_name, 0, 5)) === 'LINUX') {
				$fontFile = '/usr/share/fonts/truetype';
			} else {
				if(strtoupper(substr($os_name, 0, 7)) === 'FREEBSD') {
					$fontFile = '/usr/local/lib/X11/fonts/TrueType';
				}
			}
			$fontFile .= '/arial.ttf';
		}
		$tmp          = imagecreatetruecolor($width, $height);
		$transparency = imagecolorallocatealpha($tmp, 0, 0, 0, 127);
		imagefill($tmp, 0, 0, $transparency);
		imagesavealpha($tmp, true);
		$len        = strlen($captchaCode);
		$w          = 20;
		$h          = 20;
		$bbox       = imagettfbbox($size, 0, $fontFile, $captchaCode);
		$maxHeight = abs(
				max(array( $bbox[1], $bbox[3], $bbox[5], $bbox[7] )) -
				min(array( $bbox[1], $bbox[3], $bbox[5], $bbox[7] ))
			) + ($size / 2);
		for($i = 0; $i < $len; ++$i) {
			$this->mix($color, $variation, mt_rand(0, 100) / 100, $r, $g, $b);
			$c          = imagecolorallocate($tmp, $r, $g, $b);
			$angle      = mt_rand(-35, 35);
			$bbox       = imagettftext($tmp, $size * (mt_rand(90, 160) / 100), $angle, $w, $maxHeight, $c, $fontFile, $captchaCode[$i]);
			$bbox_width = ($bbox[2] - $bbox[0]);
			$w += $bbox_width;
			$h = max($h, $bbox[5] - $bbox[1]);
			if($i !== $len) {
				$w += mt_rand(0, $bbox_width);
			}
		}
		$this->distort($tmp);
		imagecopyresampled($img, $tmp, 0, 0, 0, 0, $width, $height, $width, $height);
		imagedestroy($tmp);
	}

	/**
	 * @param resource $img
	 */
	public function addLines(&$img)
	{
		$width  = $this->settings->get('width', 1);
		$height = $this->settings->get('height', 1);
		if($this->settings->get('max_lines', 0) > 0) {
			$lines     = mt_rand($this->settings->get('min_lines', 0), abs($this->settings->get('max_lines', 0)));
			$color     = $this->settings->get('font_color', 0x000000);
			$variation = $this->settings->get('font_color_variation', 0x848484);
			for($i = 0; $i < $lines; ++$i) {
				$this->mix($color, $variation, mt_rand(0, 100) / 100, $r, $g, $b);
				$line_color = imagecolorallocate($img, $r, $g, $b);
				$x          = $width * (1 + $i) / ($lines + 1);
				$x += (0.5 - (0.0001 * mt_rand(0, 9999))) * $width / $lines;
				$y     = mt_rand($height * 0.1, $height * 0.9);
				$theta = ((0.0001 * mt_rand(0, 9999)) - 0.5) * M_PI * 0.7;
				$w     = $width;
				$len   = mt_rand($w * 0.4, $w * 0.7);
				$lwid  = rand(0, 1);
				$k     = (0.0001 * mt_rand(0, 9999)) * 0.6 + 0.2;
				$k     = $k * $k * 0.5;
				$phi   = (0.0001 * mt_rand(0, 9999)) * 6.28;
				$step  = 0.5;
				$dx    = $step * cos($theta);
				$dy    = $step * sin($theta);
				$n     = $len / $step;
				$amp   = 1.5 * (0.0001 * mt_rand(0, 9999)) / ($k + 5.0 / $len);
				$x0    = $x - 0.5 * $len * cos($theta);
				$y0    = $y - 0.5 * $len * sin($theta);
				for($j = 0; $j < $n; ++$j) {
					$x = $x0 + $j * $dx + $amp * $dy * sin($k * $j * $step + $phi);
					$y = $y0 + $j * $dy - $amp * $dx * sin($k * $j * $step + $phi);
					imagefilledrectangle($img, $x, $y, $x + $lwid, $y + $lwid, $line_color);
				}
			}
		}
	}

	/**
	 * @param resource $img
	 */
	public function distort(&$img)
	{
		$width     = $this->settings->get('width', 1);
		$height    = $this->settings->get('height', 1);
		$amplitude = mt_rand(5, 15);
		$period    = mt_rand(10, 30);
		$width2    = $width * 2;
		$height2   = $height * 2;
		$transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
		$tmp       = imagecreatetruecolor($width2, $height2);
		imagealphablending($tmp, false);
		imagecopyresampled($tmp, $img, $width / 2, $height / 2, 0, 0, $width, $height, $width, $height);
		imagedestroy($img);
		$img = imagecreatetruecolor($width, $height);
		imagefill($tmp, 0, 0, $transparent);
		imagefill($img, 0, 0, $transparent);
		imagealphablending($img, false);
		imagealphablending($tmp, false);
		for($i = 0; $i < $width2; $i += 2) {
			imagecopy($tmp, $tmp, $i - 2, sin($i / $period) * $amplitude, $i, 0, 2, $height2);
		}
		image_trim($tmp, $transparent);
		$width2 = imagesx($tmp);
		$height2 = imagesy($tmp);
		$x = ($width - $width2) / 2;
		$y = ($height - $height2) / 2;
		imagecopyresampled($img, $tmp, $x, $y, 0, 0, $width2, $height2, $width2, $height2);
		imagesavealpha($img, true);
		imagedestroy($tmp);
	}

	/**
	 * @param int $color
	 * @param     $red
	 * @param     $green
	 * @param     $blue
	 */
	public function rgb($color, &$red, &$green, &$blue)
	{
		$blue  = $color & 255;
		$green = ($color >> 8) & 255;
		$red   = ($color >> 16) & 255;
	}

	/**
	 * @param int   $color1
	 * @param int   $color2
	 * @param float $alpha
	 * @param       $red
	 * @param       $green
	 * @param       $blue
	 */
	public function mix($color1, $color2, $alpha, &$red, &$green, &$blue)
	{
		$alpha2 = 1 - $alpha;
		$this->rgb($color1, $r1, $g1, $b1);
		$this->rgb($color2, $r2, $g2, $b2);
		$red   = ($alpha2 * $r1) + ($alpha * $r2);
		$green = ($alpha2 * $g1) + ($alpha * $g2);
		$blue  = ($alpha2 * $b1) + ($alpha * $b2);
	}
}
