<?php
namespace Aqua\Http;

use Aqua\Event\EventDispatcher;
use Aqua\Event\SubjectInterface;
use Aqua\Http\Exception\HttpException;
use Aqua\UI\Theme;

class Response
implements SubjectInterface
{
	/**
	 * @var int
	 */
	public $status = 200;
	/**
	 * @var array
	 */
	public $headers = array();
	/**
	 * @var array
	 */
	public $cookie = array();
	/**
	 * @var array
	 */
	public $origin = array();
	/**
	 * @var bool
	 */
	public $compress = false;
	/**
	 * @var bool
	 */
	public $capturingOutput = false;
	/**
	 * @var \Aqua\Event\EventDispatcher
	 */
	public $dispatcher;
	/**
	 * @var string
	 */
	public $output = '';

	public function __construct()
	{
		$this->dispatcher = new EventDispatcher($this);
	}

	/**
	 * @param string $header
	 * @return bool
	 */
	public function hasHeader($header)
	{
		return isset($this->headers[$header]);
	}

	/**
	 * @param string     $header
	 * @param string|int $value
	 * @param bool       $replace
	 * @return \Aqua\Http\Response
	 */
	public function setHeader($header, $value, $replace = true)
	{
		if($replace) {
			$this->headers[$header] = $value;
		} else if(!$this->hasHeader($header) || !is_array($this->headers[$header])) {
			$this->headers[$header] = array( $value );
		} else {
			$this->headers[$header][] = $value;
		}

		return $this;
	}

	/**
	 * @param string $header
	 * @return string|null
	 */
	public function getHeader($header)
	{
		return $this->hasHeader($header) ? $this->headers[$header] : null;
	}

	/**
	 * @param string $header
	 * @return \Aqua\Http\Response
	 */
	public function removeHeader($header)
	{
		unset($this->headers[$header]);

		return $this;
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function hasCookie($name)
	{
		return isset($this->cookie[$name]);
	}

	/**
	 * @param string $name
	 * @return array|null
	 */
	public function getCookie($name)
	{
		return $this->hasCookie($name) ? $this->cookie[$name] : null;
	}

	/**
	 * @param string $name
	 * @param array  $options
	 * @return \Aqua\Http\Response
	 */
	public function setCookie($name, array $options)
	{
		$this->cookie[$name] = $options + array(
				'value'     => '',
				'ttl'       => 0,
				'path'      => '/',
				'domain'    => '.' . \Aqua\DOMAIN,
				'secure'    => false,
				'http_only' => false
			);

		return $this;
	}

	/**
	 * @param string $name
	 * @return \Aqua\Http\Response
	 */
	public function removeCookie($name)
	{
		unset($this->cookie[$name]);

		return $this;
	}

	/**
	 * @param int         $timeout
	 * @param string|null $page
	 * @return \Aqua\Http\Response
	 */
	public function refresh($timeout, $page = null)
	{
		$header = "$timeout;";
		if($page) {
			$header .= "URL=\"$page\"";
		}
		$this->headers['Refresh'] = $header;

		return $this;
	}

	/**
	 * @param string $page
	 * @return \Aqua\Http\Response
	 */
	public function redirect($page)
	{
		$this->headers['Location'] = $page;

		return $this;
	}

	/**
	 * @param string $realm
	 * @return \Aqua\Http\Response
	 */
	public function authenticate($realm)
	{
		$this->headers['WWW-Authenticate'] = "Basic realm=\"$realm\"";
		$this->status                      = 401;

		return $this;
	}

	/**
	 * @param int|null $status
	 * @return \Aqua\Http\Response|int
	 */
	public function status($status = null)
	{
		if(is_int($status)) {
			$this->status = $status;

			return $this;
		}

		return $this->status;
	}

	/**
	 * @param bool $status
	 * @return \Aqua\Http\Response
	 */
	public function compression($status = true)
	{
		$this->compress = (bool)$status;
		$this->notify('compression-' . ($this->compress ? 'enable' : 'disable'));

		return $this;
	}

	/**
	 * @return \Aqua\Http\Response
	 */
	public function capture()
	{
		if(!$this->capturingOutput) {
			ob_start();
			$this->capturingOutput = true;
			$this->notify('output-capture-start');
		}

		return $this;
	}

	/**
	 * @param bool $capture
	 * @return \Aqua\Http\Response
	 */
	public function endCapture($capture = true)
	{
		if($this->capturingOutput) {
			if($capture) {
				$this->output .= ob_get_contents();
			}
			$this->capturingOutput = false;
			ob_end_clean();
			$this->notify('output-capture-end');
		}

		return $this;
	}

	/**
	 * @return \Aqua\Http\Response
	 */
	public function reset()
	{
		if($this->capturingOutput) {
			$this->capturingOutput = false;
			ob_end_clean();
		}
		$this->status   = 200;
		$this->compress = false;
		$this->headers  = array();
		$this->output   = array();

		return $this;
	}

	/**
	 * @param bool $close
	 * @return \Aqua\Http\Response
	 * @throws \Exception
	 */
	public function send($close = false)
	{
		$this->notify('send_start');
		try {
			if($this->capturingOutput) {
				$this->endCapture();
			}
			if(headers_sent()) {
				throw new HttpException(__('exception', 'headers-sent'));
			}
			if($close) {
				ob_start();
			}
			if($this->compress) {
				if(function_exists('ob_gzhandler')) {
					ob_start('ob_gzhandler');
				} else {
					ini_set('zlib.output_compression', 'On');
				}
			} else {
				ob_start();
			}
			foreach($this->cookie as $name => $options) {
				$cookie = "Set-Cookie: $name={$options['value']}";
				if($options['ttl'] != 0) {
					$cookie .= '; Expires=' . gmstrftime('%A, %d-%b-%Y %H:%M:%S GMT', (time() + $options['ttl']));
				}
				if($options['path']) {
					$cookie .= '; Path=' . $options['path'];
				}
				if($options['domain']) {
					$cookie .= '; Domain=' . $options['domain'];
				}
				if($options['secure']) {
					$cookie .= '; Secure';
				}
				if($options['http_only']) {
					$cookie .= '; HttpOnly';
				}
				header($cookie, false);
			}
			if(isset($this->headers['Expires'])) {
				$this->headers['Expires'] = gmstrftime('%a, %d %b %Y %H:%M:%S GMT', $this->headers['Expires']);
			}
			foreach($this->headers as $header => $value) {
				if(is_array($value)) {
					foreach($value as $v) {
						header("$header: $v", false);
					}
				} else {
					header("$header: $value", true);
				}
			}
			if(function_exists('http_response_code')) {
				\http_response_code($this->status);
			} else {
				header('X-PHP-Response-Code: ' . $this->status, true, $this->status);
			}
			if($this->output) {
				echo $this->output;
			}
			if($close && !defined('HHVM_VERSION')) {
				ob_end_flush();
				header('Connection: close');
				if($this->compress) {
					header('Content-Encoding: gzip');
				} else {
					header('Content-Encoding: none');
				}
				header('Content-Length: ' . ob_get_length());
				if(!$this->hasHeader('Content-Type')) {
					header('Content-Type: text/html');
				}
				ob_end_flush();
				if(ob_get_level() > 0) {
					ob_flush();
				}
				flush();
				if(function_exists('fastcgi_finish_request')) {
					@fastcgi_finish_request();
				}
			} else {
				ob_end_flush();
			}
			$this->notify('sent');

			return $this;
		} catch(\Exception $exception) {
			$this->notify('send-fail');
			throw $exception;
		}
	}

	/**
	 * @param string   $event
	 * @param callable $listener
	 * @return \Aqua\Http\Response
	 */
	public function attach($event, $listener)
	{
		$this->dispatcher->attach("response.$event", $listener);

		return $this;
	}

	/**
	 * @param string   $event
	 * @param callable $listener
	 * @return \Aqua\Http\Response
	 */
	public function detach($event, $listener)
	{
		$this->dispatcher->detach("response.$event", $listener);

		return $this;
	}

	/**
	 * @param string $event
	 * @param array  $feedback
	 * @return mixed
	 */
	public function notify($event, &$feedback = array())
	{
		return $this->dispatcher->notify("response.$event", $feedback);
	}
}
