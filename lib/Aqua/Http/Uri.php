<?php
namespace Aqua\Http;

class Uri
{
	/**
	 * @var array
	 */
	public $path = array();
	/**
	 * @var string
	 */
	public $action = 'index';
	/**
	 * @var array
	 */
	public $arguments = array();
	/**
	 * @var array
	 */
	public $parameters = array();

	/**
	 * @param string $path
	 * @return \Aqua\Http\Uri
	 */
	public function parsePath($path)
	{
		$this->path      = array();
		$this->action    = 'index';
		$this->arguments = array();
		$path            = explode('/', trim($path, '/'));
		while(current($path) !== false && current($path) !== 'action') {
			if(($str = $this->sanitize(current($path)))) {
				$this->path[] = $str;
			}
			next($path);
		}
		if(current($path) === 'action' && ($action = $this->sanitize(next($path)))) {
			$this->action = $action;
			next($path);
			while(current($path) !== false) {
				$this->arguments[] = current($path);
				next($path);
			}
		}

		return $this;
	}

	/**
	 * @param string $param
	 * @param mixed  $default
	 * @return mixed
	 */
	public function get($param, $default = null)
	{
		return isset($this->parameters[$param]) ? $this->parameters[$param] : $default;
	}

	/**
	 * @param string $param
	 * @param mixed  $default
	 * @return mixed
	 */
	public function getString($param, $default = '')
	{
		return (($val = $this->get($param)) !== null && is_string($val) ? $val : $default);
	}

	/**
	 * @param string $param
	 * @param mixed  $default
	 * @param int    $min
	 * @param int    $max
	 * @param int    $flags
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
		$val = $this->get($param, null);

		return (filter_var($val, FILTER_VALIDATE_INT, $options) !== false) ? intval($val) : $default;
	}

	/**
	 * @param string $param
	 * @param mixed  $default
	 * @return mixed
	 */
	public function getArray($param, $default = array())
	{
		return (($val = $this->get($param)) !== null && is_array($val) ? $val : $default);
	}

	/**
	 * @param int   $num
	 * @param mixed $default
	 * @return mixed
	 */
	public function arg($num, $default = null)
	{
		return (isset($this->arguments[$num]) ? $this->arguments[$num] : $default);
	}

	/**
	 * @param array $options
	 * @return string
	 */
	public function url(array $options = array())
	{
		$options += array(
			'action'    => $this->action,
			'arguments' => $this->arguments,
			'query'     => $this->parameters
		);
		if(isset($options['path'])) {
			$options['path'] = array_merge($this->path, $options['path']);
		} else {
			$options['path'] = $this->path;
		}

		return ac_build_url($options);
	}

	/**
	 * @param array $options
	 * @return string
	 */
	public function path(array $options = array())
	{
		return ac_build_path(array_replace_recursive(array(
				'path'      => $this->path,
				'action'    => $this->action,
				'arguments' => $this->arguments
			), $options));
	}

	/**
	 * @param array $options
	 * @return string
	 */
	public function query(array $options = array())
	{
		return ac_build_query(array_replace_recursive(array(
				'path'      => $this->path,
				'action'    => $this->action,
				'arguments' => $this->arguments,
				'query'     => $this->parameters
			), $options));
	}

	/**
	 * @param string $str
	 * @return string
	 */
	public function sanitize($str)
	{
		return $str;
	}

	/**
	 * @return \Aqua\Http\Uri
	 */
	public static function parseCurrentRequest()
	{
		$query_parameters = $_GET;
		$uri              = new self;
		if(isset($query_parameters['path']) && !is_array($query_parameters['path'])) {
			$query_parameters['path'] = str_replace('/', \Aqua\URL_SEPARATOR, urldecode($query_parameters['path']));
			$path                     = explode('.', $query_parameters['path']);
			foreach($path as &$p) {
				if($p && $p !== 'action') {
					$uri->path[] = strtolower($p);
				}
			}
			unset($query_parameters['path']);
		}
		if(isset($query_parameters['action']) && !is_array($query_parameters['action'])) {
			$uri->action = strtolower($query_parameters['action']);
			unset($query_parameters['action']);
		}
		if(isset($query_parameters['arg']) && !is_array($query_parameters['arg'])) {
			$query_parameters['arg'] = str_replace('/', \Aqua\URL_SEPARATOR, urldecode($query_parameters['arg']));
			$uri->arguments = explode(\Aqua\URL_SEPARATOR, trim($query_parameters['arg'], \Aqua\URL_SEPARATOR . ' '));
			unset($query_parameters['arg']);
		}
		$uri->parameters += $query_parameters;

		return $uri;
	}
}
