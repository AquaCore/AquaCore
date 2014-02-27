<?php
use Aqua\Core\App;
use Aqua\Ragnarok\Server;
use Aqua\Log\ErrorLog;
use CharGen\Client;
use CharGen\DB;

isset($_GET['x']) or die;

define('Aqua\ROOT',         str_replace('\\', '/', rtrim(__DIR__, DIRECTORY_SEPARATOR)));
define('Aqua\SCRIPT_NAME', basename(__FILE__));
define('Aqua\ENVIRONMENT', 'MINIMAL');
define('Aqua\PROFILE',     'CHARGEN');

require_once 'lib/bootstrap.php';

$response = App::response();
$response->capture();

try {
	$uri = App::request()->uri;
	$response->setHeader('Content-Type', 'image/png');
	Server::init();
	do {
		$action = $uri->getString('x', '');
		$settings = App::settings()->get('chargen')->get(($action === 'guild' ? 'emblem' : 'sprite'));
		$cache_ttl = $settings->get('cache_ttl', 900);
		if($settings->get('cache_browser')) {
			if($cache_ttl > 0) {
				$response->setHeader('Cache-Control', "max-age=$cache_ttl, public");
			} else if($cache_ttl === 0) {
				$response
					->setHeader('Cache-Control', 'public')
					->setHeader('Expires', strtotime('+1 years'));
			}
		}
		if(!($server = $uri->getString('s', false)) ||
		   !($charmap = $uri->getString('c', false)) ||
		   ($id = $uri->getInt('i', false, 0)) === false) {
			break;
		}
		if(!($server = Server::get($server)) || !($charmap = $server->charmap($charmap))) {
			break;
		}
		if($action === 'guild') {
			$missing_emblem_file = \Aqua\ROOT . '/tmp/emblem/missing_emblem.png';
			$empty_emblem = function() use ($response, $missing_emblem_file) {
				if(file_exists($missing_emblem_file)) {
					$content = file_get_contents($missing_emblem_file);
					$response->setHeader('Content-Length', strlen($content));
					echo $content;
				} else {
					$response->setHeader('Content-Length', '0');
				}
			};
			$dir = \Aqua\ROOT . "/tmp/emblem/{$server->key}/{$charmap->key}";
			$file = $dir . "/$id.png";
			if($cache_ttl > -1) {
				if(file_exists($file) && ($cache_ttl === 0 || (time() - filemtime($file)) < $cache_ttl)) {
					$response->setHeader('Content-Length', filesize($file));
					readfile($file);
					break;
				}
				if(!is_dir($dir)) {
					mkdir($dir, \Aqua\PUBLIC_DIRECTORY_PERMISSION, true);
				}
			}
			if($charmap->emblem($id, $size, $data) && $size && $data) {
				require_once \Aqua\ROOT . '/lib/functions/imagecreatefrombmpstring.php';
				$data = @gzuncompress(pack('H*', $data));
				$img  = imagecreatefrombmpstring($data);
				if($cache_ttl > -1) {
					$old = umask(0);
					imagepng($img, $file, $settings->get('compression', 0));
					chmod($file, \Aqua\PUBLIC_FILE_PERMISSION);
					umask($old);
				}
				imagepng($img);
				imagedestroy($img);
			} else {
				if(file_exists($missing_emblem_file) && $cache_ttl > -1) {
					$old = umask(0);
					copy($missing_emblem_file, $file);
					chmod($file, \Aqua\PUBLIC_FILE_PERMISSION);
					umask($old);
				}
				$empty_emblem();
			}
			break;
		}
		if($action !== 'head' && $action !== 'body') {
			$response->setHeader('Content-Length', '0');
			break;
		}
		DB::$path            = \Aqua\ROOT . '/settings/chargen/';
		CLient::$path        = \Aqua\ROOT . '/assets/client/';
		Client::$data_ini    = 'data.ini';
		Client::$AutoExtract = true;
		$sth = $charmap->connection()->prepare("
		SELECT account_id, `class`, hair, hair_color, clothes_color, head_top, head_mid, head_bottom
		FROM {$charmap->table('char')}
		WHERE char_id = ?
		LIMIT 1
		");
		$sth->bindValue(1, $id, PDO::PARAM_INT);
		$sth->execute();
		if(!($data = $sth->fetch(PDO::FETCH_NUM))) {
			break;
		}
		$sth = $server->login->connection()->prepare("
		SELECT (sex + 0)
		FROM {$server->login->table('login')}
		WHERE account_id = ?
		LIMIT 1
		");
		$sth->bindValue(1, $data[0], PDO::PARAM_INT);
		$sth->execute();
		$param = array();
		$param['sex']         = ((int)$sth->fetchColumn(0) === 1 ? 'M' : 'F');
		$param['hair']        = (int)$data[2];
		$param['hair_color']  = (int)$data[3];
		$param['head_top']    = (int)$data[5];
		$param['head_mid']    = (int)$data[5];
		$param['head_bottom'] = (int)$data[5];
		if($param['hair'] !== 0 && (!Client::getFile(DB::get_head_path($param['hair'], $param['sex']) . '.spr') ||
		                            !Client::getFile(DB::get_head_path($param['hair'], $param['sex']) . '.act'))) {
			$param['hair'] = 0;
		}
		if($param['hair_color'] !== 0 && !Client::getFile(DB::get_head_pal_path($param['hair'], $param['sex'], $param['hair_color']))) {
			$param['hair_color'] = 0;
		}
		if($param['head_top'] !== 0 && !Client::getFile(DB::get_hat_path($param['head_top'], $param['sex']) . '.spr') ||
		   !Client::getFile(DB::get_hat_path($param['head_top'], $param['sex']) . '.act')) {
			$param['head_top'] = 0;
		}
		if($param['head_mid'] !== 0 && !Client::getFile(DB::get_hat_path($param['head_top'], $param['sex']) . '.spr') ||
		   !Client::getFile(DB::get_hat_path($param['head_top'], $param['sex']) . '.act')) {
			$param['head_mid'] = 0;
		}
		if($param['head_bottom'] !== 0 && !Client::getFile(DB::get_hat_path($param['head_top'], $param['sex']) . '.spr') ||
		   !Client::getFile(DB::get_hat_path($param['head_top'], $param['sex']) . '.act')) {
			$param['head_bottom'] = 0;
		}
		if($action === 'body') {
			$param['class'] = (int)$data[1];
			$param['clothes_color'] = (int)$data[4];
			if($param['clothes_color'] !== 0 && !Client::getFile(DB::get_body_pal_path($param['class'], $param['sex'], $param['clothes_color']))) {
				$param['clothes_color'] = 0;
			}
			$file = \Aqua\ROOT . '/tmp/chargen/body/' . md5(serialize($param)) . '.png';
			if(file_exists($file) && ($cache_ttl === 0 || (time() - filemtime($file)) < $cache_ttl)) {
				$response->setHeader('Content-Length', filesize($file));
				readfile($file);
				break;
			}
			$chargen = new \CharGen\CharacterRender;
			$chargen->param     = $param + $chargen->param;
			$chargen->direction =  $settings->get('body_direction', \CharGen\CharacterRender::DIRECTION_SOUTH);
			$chargen->action    =  $settings->get('body_action', \CharGen\CharacterRender::ACTION_IDLE);
			$img = $chargen->render();
			image_trim($img, imagecolorallocatealpha($img, 255, 255, 255, 127));
			if($cache_ttl > -1) {
				$old = umask(0);
				imagepng($img, $file, $settings->get('compression', 0));
				chmod($file, \Aqua\PUBLIC_FILE_PERMISSION);
				umask($old);
			}
			imagepng($img);
			imagedestroy($img);
		} else {
			$file = \Aqua\ROOT . '/tmp/chargen/head/' . md5(serialize($param)) . '.png';
			if(file_exists($file) && ($cache_ttl === 0 || (time() - filemtime($file)) < $cache_ttl)) {
				$response->setHeader('Content-Length', filesize($file));
				readfile($file);
				break;
			}
			$chargen = new \CharGen\CharacterHeadRender;
			$chargen->param     = $param;
			$chargen->direction =  $settings->get('head_direction', \CharGen\CharacterHeadRender::DIRECTION_SOUTH);
			$img = $chargen->render();
			image_trim($img, imagecolorallocatealpha($img, 255, 255, 255, 127));
			if($cache_ttl > -1) {
				$old = umask(0);
				imagepng($img, $file, $settings->get('compression', 0));
				chmod($file, \Aqua\PUBLIC_FILE_PERMISSION);
				umask($old);
			}
			imagepng($img);
			imagedestroy($img);
		}
		break;
	} while(0);
} catch (Exception $exception) {
	ErrorLog::logSql($exception);
	if(!headers_sent()) {
		$response
			->endCapture(false)
			->capture()
			->setHeader('Content-Length',  '0');
	}
}
$response->send();
