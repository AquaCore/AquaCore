<?php
namespace Aqua\Util;

class ImageUploader
{
	/**
	 * @var array
	 */
	public $mimeTypes = array(
		'png'   => 'image/png',
		'apng'  => 'image/png',
		'jpg'   => 'image/jpeg',
		'jpeg'  => 'image/jpeg',
		'gif'   => 'image/gif',
		'svg'   => 'image/svg+xml',
		'svgx'  => 'image/svg+xml',
	);
	/**
	 * @var int
	 */
	public $connectionTimeout = 3;
	/**
	 * @var int
	 */
	public $timeout = 5;
	/**
	 * @var int
	 */
	public $maxSize = 0;
	/**
	 * @var int
	 */
	public $maxX = 0;
	/**
	 * @var int
	 */
	public $maxY = 0;
	/**
	 * @var resource
	 */
	public $source;
	/**
	 * @var string
	 */
	public $mimeType;
	/**
	 * @var string
	 */
	public $extension;
	/**
	 * @var int
	 */
	public $size;
	/**
	 * @var int
	 */
	public $x;
	/**
	 * @var int
	 */
	public $y;
	/**
	 * @var string
	 */
	public $path;
	/**
	 * @var string
	 */
	public $content;
	/**
	 * @var bool
	 */
	public $isLocal;
	/**
	 * @var int
	 */
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
	const INVALID_ENCODING          = 11;

	public function uploadLocal($path, $name)
	{
		$extension = strtolower(ltrim(strrchr($name, '.'), '.'));
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
		$mime = strtolower($data['mime']);
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
		$source = null;
		do {
			switch($mime) {
				case 'image/png': $source = @imagecreatefrompng($path); break;
				case 'image/jpeg': $source = @imagecreatefromjpeg($path); break;
				case 'image/gif': $source = @imagecreatefromgif($path); break;
				default: break 2;
			}
			if(!is_resource($source)) {
				$this->error = self::UPLOAD_INVALID_IMAGE;
				return false;
			}
		} while(0);
		$this->source = $source;
		$this->x = $x;
		$this->y = $y;
		$this->mimeType = $mime;
		$this->extension = $extension;
		$this->path = $path;
		$this->isLocal = true;
		$this->error = self::UPLOAD_OK;
		return true;
	}

	public function uploadRemote($url)
	{
		if(!($urlParts = parse_url($url)) || !isset($urlParts['host'])) {
			$this->error = self::UPLOAD_INVALID_PATH;
			return false;
		}
		$host = $urlParts['host'];
		if(isset($urlParts['port'])) {
			$port = (int)$urlParts['port'];
		} else if(isset($urlParts['scheme']) && $urlParts['scheme'] === 'https') {
			$port = 443;
		} else {
			$port = 80;
		}
		$target = '/';
		if(isset($urlParts['path'])) $target = $urlParts['path'];
		if(isset($urlParts['query'])) $target.= '?' . $urlParts['query'];
		$acceptEncoding = array_filter(array(
			'gzip' => function_exists('gzdecode') || function_exists('gzdeflate'),
		    'deflate' => function_exists('gzdeflate') && function_exists('gzuncompress')
		));
		$request = "GET $target HTTP/1.1\r\n";
		$request.= "Host: $host\r\n";
		$request.= "Accept: image/*;q=0.9,*/*;q=0.8\r\n";
		if(!empty($acceptEncoding)) {
			$request.= sprintf("Accept-Encoding: %s\r\n", implode(',', array_keys($acceptEncoding)));
		}
		$request.= "Connection: Close\r\n\r\n";
		if($port === 443) {
			$host = "ssl://$host";
		}
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
		if(count($response) !== 2) {
			$this->error = self::UPLOAD_INVALID_IMAGE;
			return false;
		}
		if(!preg_match('/Content-Length: ([0-9]+)/m', $response[0], $match)) {
			$this->error = self::UPLOAD_INVALID_IMAGE;
			return false;
		}
		if((int)$match[1] !== strlen($response[1])) {
			$this->error = self::UPLOAD_INVALID_IMAGE;
			return false;
		}
		if(preg_match('/Content-Encoding: ([a-z]+)/m', $response[0], $match)) {
			if(!array_key_exists($match[1], $acceptEncoding)) {
				$this->error = self::INVALID_ENCODING;
				return false;
			}
			try {
				if($match[1] === 'gzip') {
					if(function_exists('gzdecode')) {
						$response[1] = gzdecode($response[1]);
					} else {
						$response[1] = gzinflate(substr($response[1], 10, -8));
					}
				} else if($match[1] === 'deflate') {
					$zlibHeader = unpack('n', substr($response[1], 0, 2));
					if($zlibHeader[1] % 31 == 0) {
						$response[1] = gzuncompress($response[1]);
					} else {
						$response[1] = gzinflate($response[1]);
					}
				}
			} catch(\Exception $exception) {
				$this->error = self::INVALID_ENCODING;
				return false;
			}
		}
		if(($this->maxSize && strlen($response[1]) > $this->maxSize)) {
			$this->error = self::UPLOAD_TOO_LARGE;
			return false;
		}
		if(!function_exists('getimagesizefromstring')) {
			$data = getimagesize('data://application/octet-stream;base64,' . base64_encode($response[1]));
		} else {
			$data = getimagesizefromstring($response[1]);
		}
		if(!isset($data['mime']) || !isset($data[0]) || !isset($data[1])) {
			$this->error = self::UPLOAD_INVALID_IMAGE;
			return false;
		}
		$mime = strtolower($data['mime']);
		$x = $data[0];
		$y = $data[1];
		if($this->maxX && $x > $this->maxX || $this->maxY && $y > $this->maxY) {
			$this->error = self::UPLOAD_INVALID_DIMENSIONS;
			return false;
		}
		$mimeTypes = array_unique($this->mimeTypes);
		foreach($mimeTypes as &$type) {
			$type = preg_quote($type, '/');
		}
		$mimeTypes = strtolower(implode('|', $mimeTypes));
		if(!in_array($mime, $this->mimeTypes) ||
		   !preg_match("/Content-Type: ($mimeTypes)/mi", $response[0], $match)) {
			$this->error = self::UPLOAD_INVALID_MIME;
			return false;
		}
		if(strtolower($match[1]) !== $mime) {
			$this->error = self::UPLOAD_MIME_MISMATCH;
			return false;
		}
		if($mime === 'image/png' || $mime === 'image/jpeg' || $mime === 'image/gif') {
			$source = @imagecreatefromstring($response[1]);
			if(!is_resource($source)) {
				$this->error = self::UPLOAD_INVALID_IMAGE;
				return false;
			}
		} else {
			$source = null;
		}
		if(!($extension = strtolower(ltrim(strrchr($url, '.'), '.'))) ||
		   !array_key_exists($extension, $this->mimeTypes) ||
		   $this->mimeTypes[$extension] !== $mime) {
			foreach($this->mimeTypes as $extension => $type) {
				if($type === $mime) {
					break;
				}
			}
		}
		$this->source = $source;
		$this->content = $response[1];
		$this->x = $x;
		$this->y = $y;
		$this->extension = $extension;
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
		$name.= '.' . $this->extension;
		if($permission === null) {
			$permission = \Aqua\PUBLIC_FILE_PERMISSION;
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
			case self::INVALID_ENCODING:
				return __('upload', 'invalid-encoding');
			default: return null;
		}
	}
}
