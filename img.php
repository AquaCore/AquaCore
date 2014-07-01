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

function blank()
{
	$gif = base64_decode('R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==');
	App::response()
	   ->setHeader('Content-Type', 'image/gif')
	   ->setHeader('Content-Length', strlen($gif));
	echo $gif;
}

function guildEmblem(Server\CharMap $charMap, $guildId)
{
	do {
		$settings = App::settings()->get('chargen')->get('emblem');
		$cacheTTL = $settings->get('cache_ttl', 0);
		$lastModified = time();
		$dir = \Aqua\ROOT . "/tmp/emblem/{$charMap->server->key}/{$charMap->key}";
		$file = "$dir/$guildId.png";
		if($cacheTTL > -1 && file_exists($file)) {
			$lastModified = filemtime($file);
			if($cacheTTL === 0 || (time() - $lastModified < $cacheTTL)) {
				if($ifModifiedSince = App::request()->header('If-Modified-Since', null)) {
					$ifModifiedSince = strtotime($ifModifiedSince);
				}
				App::response()
					->setHeader('Last-Modified', $lastModified)
					->setHeader('Age', time() - $lastModified);
				if($ifModifiedSince && $lastModified >= $ifModifiedSince) {
					App::response()->status(304);
					break;
				}
				App::response()->setHeader('Content-Type', 'image/png');
				readfile($file);
				break;
			}
		}
		if($charMap->emblem($guildId, $size, $data)) {
			if(!$size || !$data) {
				if(file_exists(\Aqua\ROOT . '/tmp/emblem/missing_emblem.png')) {
					App::response()->setHeader('Content-Type', 'image/png');
					readfile(\Aqua\ROOT . '/tmp/emblem/missing_emblem.png');
				} else {
					blank();
				}
				break;
			}
			require_once \Aqua\ROOT . '/lib/functions/imagecreatefrombmpstring.php';
			$data = @gzuncompress(pack('H*', $data));
			$img  = imagecreatefrombmpstring($data);
			if($cacheTTL > -1) {
				$old = umask(0);
				if(!is_dir($dir)) {
					mkdir($dir, \Aqua\PUBLIC_DIRECTORY_PERMISSION, true);
				}
				if(file_exists($file)) {
					@unlink($file);
				}
				imagepng($img, $file, $settings->get('compression', 0));
				chmod($file, \Aqua\PUBLIC_FILE_PERMISSION);
				umask($old);
			}
			imagegif($img);
			imagedestroy($img);
			App::response()
				->setHeader('Content-Type', 'image/png')
				->setHeader('Last-Modified', time())
				->setHeader('Age', 0);
			break;
		}
		App::response()->status(404);
		blank();
		return false;
	} while(0);
	if($cacheTTL > 0) {
		App::response()
			->setHeader('Cache-Control', sprintf('max-age=%d, public', $cacheTTL))
			->setHeader('Expires', $lastModified + $cacheTTL);
	} else if($cacheTTL === 0) {
		App::response()
		   ->setHeader('Cache-Control', 'max-age=31536000, public')
		   ->setHeader('Expires', strtotime('+1 year'));
	}
	return true;
}

function initCharGen()
{
	DB::$path            = \Aqua\ROOT . '/settings/chargen/';
	CLient::$path        = \Aqua\ROOT . '/assets/client/';
	Client::$data_ini    = 'data.ini';
	Client::$AutoExtract = true;
	Client::init();
}

function charLook(Server\CharMap $charMap, $charId)
{
	$sth = $charMap->connection()->prepare("
	SELECT account_id, `class`, hair, hair_color, clothes_color, head_top, head_mid, head_bottom
	FROM {$charMap->table('char')}
	WHERE char_id = ?
	LIMIT 1
	");
	$sth->bindValue(1, $charId, PDO::PARAM_INT);
	$sth->execute();
	if(!($data = $sth->fetch(PDO::FETCH_NUM))) {
		return false;
	}
	$sth = $charMap->server->login->connection()->prepare("
	SELECT (sex + 0)
	FROM {$charMap->server->login->table('login')}
	WHERE account_id = ?
	LIMIT 1
	");
	$sth->bindValue(1, $data[0], PDO::PARAM_INT);
	$sth->execute();
	$look = array();
	$look['sex']         = ((int)$sth->fetchColumn(0) === 1 ? 'M' : 'F');
	$look['hair']        = (int)$data[2];
	$look['hair_color']  = (int)$data[3];
	$look['head_top']    = (int)$data[5];
	$look['head_mid']    = (int)$data[5];
	$look['head_bottom'] = (int)$data[5];

	if($look['hair'] !== 0 && (!Client::getFile(DB::get_head_path($look['hair'], $look['sex']) . '.spr') ||
	                            !Client::getFile(DB::get_head_path($look['hair'], $look['sex']) . '.act'))) {
		$look['hair'] = 0;
	}
	if($look['hair_color'] !== 0 && !Client::getFile(DB::get_head_pal_path($look['hair'], $look['sex'], $look['hair_color']))) {
		$look['hair_color'] = 0;
	}
	if($look['head_top'] !== 0 && !Client::getFile(DB::get_hat_path($look['head_top'], $look['sex']) . '.spr') ||
	   !Client::getFile(DB::get_hat_path($look['head_top'], $look['sex']) . '.act')) {
		$look['head_top'] = 0;
	}
	if($look['head_mid'] !== 0 && !Client::getFile(DB::get_hat_path($look['head_top'], $look['sex']) . '.spr') ||
	   !Client::getFile(DB::get_hat_path($look['head_top'], $look['sex']) . '.act')) {
		$look['head_mid'] = 0;
	}
	if($look['head_bottom'] !== 0 && !Client::getFile(DB::get_hat_path($look['head_top'], $look['sex']) . '.spr') ||
	   !Client::getFile(DB::get_hat_path($look['head_top'], $look['sex']) . '.act')) {
		$look['head_bottom'] = 0;
	}

	$hash = md5(serialize($look));
	$look['hash'] = $hash;

	return $look;
}

function charHead(Server\CharMap $charMap, $charId)
{
	initCharGen();
	if($look = charLook($charMap, $charId)) {
		App::response()->setHeader('Etag', "\"{$look['hash']}\"");
		$settings = App::settings()->get('chargen')->get('sprite');
		$cacheTTL = $settings->get('cache_ttl', 0);
		if($cacheTTL > 0) {
			App::response()->setHeader('Cache-Control', sprintf('max-age=%d, public', $cacheTTL));
		} else if($cacheTTL === 0) {
			App::response()->setHeader('Cache-Control', 'max-age=31536000, public');
		}
		if(App::request()->header('If-None-Match') === "\"{$look['hash']}\"") {
			App::response()->status(304);
			return true;
		}
		$file = \Aqua\ROOT . "/tmp/chargen/head/{$look['hash']}.png";
		App::response()->setHeader('Content-Type', 'image/png');
		if(file_exists($file)) {
			readfile($file);
			return true;
		}
		$chargen = new \CharGen\CharacterHeadRender;
		$chargen->param     = $look;
		$chargen->direction = $settings->get('head_direction', \CharGen\CharacterHeadRender::DIRECTION_SOUTH);
		$img                = $chargen->render();
		image_trim($img, imagecolorallocatealpha($img, 255, 255, 255, 127));
		if($cacheTTL > -1) {
			$old = umask(0);
			imagepng($img, $file, $settings->get('compression', 0));
			chmod($file, \Aqua\PUBLIC_FILE_PERMISSION);
			umask($old);
		}
		imagepng($img);
		imagedestroy($img);
		return true;
	} else {
		App::response()->status(404);
		blank();
		return false;
	}
}

function charBody(Server\CharMap $charMap, $charId)
{
	initCharGen();
	if($look = charLook($charMap, $charId)) {
		App::response()->setHeader('Etag', "\"{$look['hash']}\"");
		$settings = App::settings()->get('chargen')->get('sprite');
		$cacheTTL = $settings->get('cache_ttl', 0);
		if($cacheTTL > 0) {
			App::response()->setHeader('Cache-Control', sprintf('max-age=%d, public', $cacheTTL));
		} else if($cacheTTL === 0) {
			App::response()->setHeader('Cache-Control', 'max-age=31536000, public');
		}
		if(App::request()->header('If-None-Match') === "\"{$look['hash']}\"") {
			App::response()->status(304);
			return true;
		}
		$file = \Aqua\ROOT . "/tmp/chargen/body/{$look['hash']}.png";
		App::response()->setHeader('Content-Type', 'image/png');
		if(file_exists($file)) {
			readfile($file);
			return true;
		}
		$chargen = new \CharGen\CharacterRender;
		$chargen->param     = $look + $chargen->param;
		$chargen->direction = $settings->get('body_direction', \CharGen\CharacterRender::DIRECTION_SOUTH);
		$chargen->action    = $settings->get('body_action', \CharGen\CharacterRender::ACTION_IDLE);
		$img                = $chargen->render();
		image_trim($img, imagecolorallocatealpha($img, 255, 255, 255, 127));
		if($cacheTTL > -1) {
			$old = umask(0);
			imagepng($img, $file, $settings->get('compression', 0));
			chmod($file, \Aqua\PUBLIC_FILE_PERMISSION);
			umask($old);
		}
		imagepng($img);
		imagedestroy($img);
		return true;
	} else {
		App::response()->status(404);
		blank();
		return false;
	}
}

try {
	$uri = App::request()->uri;
	$response->setHeader('Content-Type', 'image/png');
	Server::init();
	$fn = null;
	switch($uri->getString('x')) {
		case 'guild': $fn = 'guildEmblem'; break;
		case 'head': $fn = 'charHead'; break;
		case 'body': $fn = 'charBody'; break;
	}
	if($fn) {
		$server  = $uri->getString('s', false);
		$charMap = $uri->getString('c', false);
		$id      = $uri->getString('i', false, 1);
		if($server) $server = Server::get($server);
		if($charMap && $server) $charMap = $server->charmap($charMap);
		if($server && $charMap && $id) {
			call_user_func($fn, $charMap, $id);
		}
	}
} catch (Exception $exception) {
	ErrorLog::logSql($exception);
	if(!headers_sent()) {
		$response->reset()->status(500);
		blank();
	}
}
$response->send();
