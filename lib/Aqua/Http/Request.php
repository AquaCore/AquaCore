<?php
namespace Aqua\Http;

use Aqua\Core\App;

class Request
{
	/**
	 * @var string
	 */
	public $method = 'GET';
	/**
	 * @var array
	 */
	public $data = array();
	/**
	 * @var array
	 */
	public $headers = array();
	/**
	 * @var array
	 */
	public $cookies = array();
	/**
	 * @var bool
	 */
	public $ajax;
	/**
	 * @var string
	 */
	public $ip;
	/**
	 * @var string
	 */
	public $ipString;
	/**
	 * @var string
	 */
	public $authUsername;
	/**
	 * @var string
	 */
	public $authPassword;
	/**
	 * @var \Aqua\Http\Uri
	 */
	public $uri;

	/**
	 * @param string $key
	 * @param mixed  $default
	 * @return mixed
	 */
	public function data($key, $default = null)
	{
		return isset($this->data[$key]) ? $this->data[$key] : $default;
	}

	/**
	 * @param string $param
	 * @param mixed  $default
	 * @return mixed
	 */
	public function getString($param, $default = '')
	{
		return (($val = $this->data($param)) !== null && is_string($val) ? $val : $default);
	}

	/**
	 * @param string   $param
	 * @param mixed    $default
	 * @param int|null $min
	 * @param int|null $max
	 * @param int      $flags
	 * @return mixed
	 */
	public function getInt($param, $default = 0, $min = null, $max = null, $flags = 0)
	{
		$options = array(
			'options' => array(),
			'flags'   => $flags
		);
		if($min !== null) {
			$options['options']['min_range'] = $min;
		}
		if($max !== null) {
			$options['options']['max_range'] = $max;
		}
		$val = $this->data($param, null);

		return filter_var($val, FILTER_VALIDATE_INT, $options) !== false ? intval($val) : $default;
	}

	/**
	 * @param string     $param
	 * @param mixed      $default
	 * @param float|null $min
	 * @param float|null $max
	 * @param int        $flags
	 * @return mixed
	 */
	public function getFloat($param, $default = 0, $min = null, $max = null, $flags = 0)
	{
		$options = array(
			'flags' => $flags
		);
		$val     = $this->data($param, null);

		return
			filter_var($val, FILTER_VALIDATE_FLOAT, $options) !== false && ($max === null || floatval($val) <= $max) &&
			($min === null || floatval($val) >= $min) ? floatval($val) : $default;
	}

	/**
	 * @param string $param
	 * @param mixed  $default
	 * @return mixed
	 */
	public function getArray($param, $default = array())
	{
		return (($val = $this->data($param)) !== null && is_array($val) ? $val : $default);
	}

	public function __clone()
	{
		$this->uri = clone $this->uri;
	}


	/**
	 * @param string $header
	 * @param mixed  $default
	 * @return mixed
	 */
	public function header($header, $default = null)
	{
		return isset($this->headers[$header]) ? $this->headers[$header] : $default;
	}

	/**
	 * @param string $cookie
	 * @param mixed  $default
	 * @return mixed
	 */
	public function cookie($cookie, $default = null)
	{
		return isset($this->cookies[$cookie]) ? $this->cookies[$cookie] : $default;
	}

	/**
	 * @return string
	 */
	public function previousUrl()
	{
		$regex = '/^(https?\:\/\/)?([^\.]+\.)?' . preg_quote(\Aqua\DOMAIN, '/') . '/i';
		if(($referrer = $this->header('HTTP_REFERRER')) && preg_match($regex, $referrer)) {
			return $referrer;
		} else {
			return \Aqua\WORKING_URL;
		}
	}

	/**
	 * @return \Aqua\Http\Request
	 */
	public static function parseGlobals()
	{
		$request           = new self;
		$request->method   = strtoupper(getenv('REQUEST_METHOD'));
		$request->cookies  = $_COOKIE;
		$request->data     = $_POST;
		if(php_sapi_name() === 'cli') {
			$request->uri = new Uri(App::registryGet('arguments', array()));
			if($sshClient = getenv('SSL_CLIENT')) {
				$request->ipString = strstr($sshClient, ' ', true);
				$request->ip       = inet_pton($request->ipString);
			} else {
				$request->ip       = inet_pton('127.0.0.1');
				$request->ipString = '127.0.0.1';
			}
		} else {
			$request->uri = new Uri($_GET);
			do {
				foreach(array('CLIENT_IP',
				              'FORWARDED_FOR',
				              'X_FORWARDED_FOR',
				              'FORWARDED_FOR_IP',
				              'FORWARDED',
				              'VIA') as $header) {
					if(!($ipAddress = getenv("HTTP_$header")) &&
					   !($ipAddress = getenv($header))) {
						continue;
					}
					$request->ip       = inet_pton($ipAddress);
					$request->ipString = $ipAddress;
					break 2;
				}
				$request->ip       = inet_pton(getenv('REMOTE_ADDR'));
				$request->ipString = getenv('REMOTE_ADDR');
			} while(0);
		}
		if(function_exists('getallheaders')) {
			$request->headers = getallheaders();
		} else {
			foreach($_SERVER as $key => $value) {
				if(substr($key, 0, 5) === 'HTTP_') {
					$request->headers[str_replace('_', '-', ucwords(strtolower(substr($key, 5))))] = $value;
				}
			}
		}
		if(strtolower(getenv('HTTP_X_REQUESTED_WITH')) === 'xmlhttprequest') {
			$request->ajax = true;
		}
		if(getenv('PHP_AUTH_USER')) {
			$request->authUsername = getenv('PHP_AUTH_USER');
			$request->authPassword = getenv('PHP_AUTH_PW') ?: null;
		} else if($request->header('Authorization') &&
		          preg_match('/Basic\s+(.*)$/i', $request->headers['Authorization'], $match)) {
			$request->authUsername = strip_tags(substr(strstr($match[0], ':'), 1));
			$request->authPassword = strip_tags(strstr($match[0], ':', true));
		}

		return $request;
	}
}
