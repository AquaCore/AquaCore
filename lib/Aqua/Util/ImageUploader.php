<?php
namespace Aqua\Util;

use Aqua\Core\App;

class ImageUploader
{
	public $mimeTypes = array(
		'PNG'  => 'IMAGE/PNG',
		'JPG'  => 'IMAGE/JPEG',
		'JPEG' => 'IMAGE/JPEG',
		'GIF'  => 'IMAGE/GIF',
	);
	public $connectionTimeout = 3;
	public $timeout = 5;
	public $maxSize = 0;
	public $maxX = 0;
	public $maxY = 0;
	public $source;
	public $mimeType;
	public $size;
	public $x;
	public $y;
	public $path;
	public $content;
	public $isLocal;
	public $error;

	const UPLOAD_OK                 = 0;
	const UPLOAD_INVALID_PATH       = 1;
	const UPLOAD_TOO_LARGE          = 2;
	const UPLOAD_INVALID_EXT        = 3;
	const UPLOAD_INVALID_MIME       = 4;
	const UPLOAD_MIME_MISMATCH      = 5;
	const UPLOAD_INVALID_IMAGE      = 6;
	const UPLOAD_FAILED_TO_CONNECT  = 7;
	const UPLOAD_TIMEOUT            = 8;
	const UPLOAD_INVALID_DIMENSIONS = 9;
	const UPLOAD_FAILED_TO_SAVE     = 10;

	public function uploadLocal($path, $name)
	{
		$extension = strtoupper(ltrim(strrchr($name, '.'), '.'));
		if(!array_key_exists($extension, $this->mimeTypes)) {
			$this->error = self::UPLOAD_INVALID_EXT;
			return false;
		}
		if(!file_exists($path)) {
			$this->error = self::UPLOAD_INVALID_PATH;
			return false;
		}
		if($this->maxSize > 0 && filesize($path) > $this->maxSize) {
			$this->error = self::UPLOAD_TOO_LARGE;
			return false;
		}
		$data = getimagesize($path);
		if(!isset($data['mime']) || !isset($data[0]) || !isset($data[1])) {
			$this->error = self::UPLOAD_INVALID_IMAGE;
			return false;
		}
		$mime = strtoupper($data['mime']);
		$x = $data[0];
		$y = $data[1];
		if($this->maxX && $x > $this->maxX || $this->maxY && $y > $this->maxY) {
			$this->error = self::UPLOAD_INVALID_DIMENSIONS;
			return false;
		}
		if($this->mimeTypes[$extension] !== $mime) {
			$this->error = self::UPLOAD_MIME_MISMATCH;
			return false;
		}
		switch($mime) {
			case 'IMAGE/PNG': $source = @imagecreatefrompng($path); break;
			case 'IMAGE/JPEG': $source = @imagecreatefromjpeg($path); break;
			case 'IMAGE/GIF': $source = @imagecreatefromgif($path); break;
			default: return self::UPLOAD_INVALID_IMAGE;
		}
		if(!is_resource($source)) {
			$this->error = self::UPLOAD_INVALID_IMAGE;
			return false;
		}
		$this->source = $source;
		$this->x = $x;
		$this->y = $y;
		$this->mimeType = $mime;
		$this->path = $path;
		$this->isLocal = true;
		$this->error = self::UPLOAD_OK;
		return true;
	}

	public function uploadRemote($url)
	{
		if(!($_url = parse_url($url)) || !isset($_url['host'])) {
			$this->error = self::UPLOAD_INVALID_PATH;
			return false;
		}
		$host = $_url['host'];
		if(isset($_url['port'])) $port = (int)$_url['port'];
		else $port = (isset($_url['scheme']) && $_url['shceme'] === 'https' ? 443 : 80);
		$target = '/';
		if(isset($_url['path'])) $target = $_url['path'];
		if(isset($_url['query'])) $target.= '?' . $_url['query'];
		$request = "GET $target HTTP/1.1\r\n";
		$request.= "Host: $host\r\n";
		$request.= "Accept: image/png, image/jpeg, image/gif\r\n";
		$request.= "Connection: Close\r\n\r\n";
		$fp = @fsockopen($host, $port, $errno, $errstr, $this->connectionTimeout);
		if(!is_resource($fp)) {
			$this->error = self::UPLOAD_FAILED_TO_CONNECT;
			return false;
		}
		fputs($fp, $request);
		$time = $this->timeout + time();
		$response = '';
		while(!($timeout = ($time < time())) && !feof($fp)) {
			$response.= fgets($fp, 2048);
		}
		fclose($fp);
		if($timeout) {
			$this->error = self::UPLOAD_TIMEOUT;
			return false;
		}
		$response = explode("\r\n\r\n", $response);
		if(count($request) !== 2) {
			$this->error = self::UPLOAD_INVALID_IMAGE;
			return false;
		}
		if(!preg_match('/Content-Length: ([0-9]+)/m', $response[0], $match)) {
			$this->error = self::UPLOAD_INVALID_IMAGE;
			return false;
		}
		$len = (int)$match[1];
		if($len !== strlen($response[1]) || ($this->maxSize && $len > $this->maxSize)) {
			$this->error = self::UPLOAD_TOO_LARGE;
			return false;
		}
		if (!function_exists('getimagesizefromstring')) {
			$data = getimagesize('data://application/octet-stream;base64,' . base64_encode($response[1]));
		} else {
			$data = getimagesizefromstring($response[1]);
		}
		if(!isset($data['mime']) || !isset($data[0]) || !isset($data[1])) {
			$this->error = self::UPLOAD_INVALID_IMAGE;
			return false;
		}
		$mime = strtoupper($data['mime']);
		$x = $data[0];
		$y = $data[1];
		if($this->maxX && $x > $this->maxX || $this->maxY && $y > $this->maxY) {
			$this->error = self::UPLOAD_INVALID_DIMENSIONS;
			return false;
		}
		if(!in_array($mime, $this->mimeTypes) || !preg_match('/Content-Type: (image\/(?:gif|png|jpeg))/m', $response[0], $match)) {
			$this->error = self::UPLOAD_INVALID_MIME;
			return false;
		}
		if(strtoupper($match[1]) !== $mime) {
			$this->error = self::UPLOAD_MIME_MISMATCH;
			return false;
		}
		$source = @imagecreatefromstring($response[1]);
		if(!is_resource($source)) {
			$this->error = self::UPLOAD_INVALID_IMAGE;
			return false;
		}
		$this->source = $source;
		$this->content = $response[1];
		$this->x = $x;
		$this->y = $y;
		$this->mimeType = $mime;
		$this->isLocal = false;
		$this->path = $url;
		$this->error = self::UPLOAD_OK;
		return true;
	}

	public function dimension($x, $y)
	{
		$this->maxX = $x;
		$this->maxY = $y;
		return $this;
	}

	public function maxSize($size)
	{
		$this->maxSize = $size;
		return $this;
	}

	/**
	 * @param string     $directory
	 * @param string|null $name
	 * @param int|null $permission
	 * @return bool|string
	 */
	public function save($directory, $name = null, $permission = null)
	{
		if($name === null) {
			$name = uniqid();
		}
		if($permission === null) {
			$permission = \Aqua\PUBLIC_FILE_PERMISSION;
		}
		switch($this->mimeType) {
			case 'IMAGE/PNG': $name.= '.png'; break;
			case 'IMAGE/JPEG': $name.= '.jpg'; break;
			case 'IMAGE/GIF': $name.= '.gif'; break;
		}
		$file = \Aqua\ROOT . "$directory/$name";
		if($this->isLocal) {
			$success = (bool)move_uploaded_file($this->path, $file);
		} else {
			$success = (bool)file_put_contents($file, $this->content);
		}
		if(!$success) {
			$this->error = self::UPLOAD_FAILED_TO_SAVE;
		} else {
			chmod($file, $permission);
		}
		return ($success ? "$directory/$name" : false);
	}

	/**
	 * @return string|null
	 */
	public function errorStr()
	{
		switch($this->error) {
			case self::UPLOAD_INVALID_PATH:
				return __('upload', ($this->isLocal ? 'invalid-file' : 'invalid-url'));
			case self::UPLOAD_TOO_LARGE:
				return __('upload', 'file-too-large');
			case self::UPLOAD_INVALID_EXT:
				return __('upload', 'invalid-extension');
			case self::UPLOAD_INVALID_MIME:
				return __('upload', 'invalid-mime-type');
			case self::UPLOAD_INVALID_IMAGE:
				return __('upload', 'not-an-image');
			case self::UPLOAD_MIME_MISMATCH:
				return __('upload', 'ext-mime-mismatch');
			case self::UPLOAD_FAILED_TO_CONNECT:
				return __('upload', 'failed-to-connect');
			case self::UPLOAD_TIMEOUT:
				return __('upload', 'connection-timeout');
			case self::UPLOAD_INVALID_DIMENSIONS:
				return __('upload', 'image-too-large', $this->maxX, $this->maxY);
			case self::UPLOAD_FAILED_TO_SAVE:
				return __('upload', 'failed-to-move');
			default: return null;
		}
	}
}
