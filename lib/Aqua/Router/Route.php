<?php
namespace Aqua\Router;

use Aqua\Event\EventDispatcher;
use Aqua\Event\SubjectInterface;
use Aqua\Http\Request;

class Route
implements SubjectInterface
{
	/**
	 * @var array
	 */
	public $vars = array();
	/**
	 * @var string
	 */
	public $url;
	/**
	 * @var string
	 */
	public $target;
	/**
	 * @var string
	 */
	public $pattern;
	/**
	 * @var callable
	 */
	public $parser;
	/**
	 * @var \Aqua\Event\EventDispatcher
	 */
	public $dispatcher;

	public function __construct()
	{
		$this->dispatcher = new EventDispatcher;
		$this->parser      = array( $this, 'parseUrl' );
	}

	/**
	 * @param string $url
	 * @param string $target
	 * @return \Aqua\Router\Route
	 */
	public function map($url, $target)
	{
		$regex         = preg_replace_callback(
			'/(?:\/:([A-Za-z0-9]+)?)(?:\[(!)?([|\/\-\_A-Za-z0-9]+)\])?/',
			array( $this, 'urlRegex' ),
			$url
		);
		$regex         = str_replace('/*', '(?:/(?P<path>.*))?', $regex);
		$regex         = addcslashes($regex, '/');
		$this->pattern = '/^' . $regex . '$/is';
		$this->target  = $target;

		return $this;
	}

	/**
	 * @param callable $function
	 * @return \Aqua\Router\Route
	 */
	public function parser(\Closure $function)
	{
		$this->parser = $function;

		return $this;
	}

	/**
	 * @param string             $url
	 * @param \Aqua\Http\Request $request
	 * @return bool
	 */
	public function parse($url, Request $request)
	{
		if(!preg_match($this->pattern, $url, $match)) {
			return false;
		}
		$feedback = array( &$match, &$request );
		$this->notify('match', $feedback);
		if(call_user_func($this->parser, $request, $this->target, $match)) {
			$this->notify('parse_ok', $feedback);

			return true;
		} else {
			$this->notify('parse_fail', $feedback);

			return false;
		}
	}

	/**
	 * @param \Aqua\Http\Request $request
	 * @param string             $target
	 * @param array              $match
	 * @return bool
	 */
	public function parseUrl(Request $request, $target, array $match)
	{
		do {
			if(is_int(key($match))) {
				continue;
			}
			$key    = ':' . key($match);
			$value  = current($match);
			$target = str_replace($key, $value, $target);
		} while(next($match) !== false);
		$request->uri->parsePath($target);

		return true;
	}

	/**
	 * @param array $match
	 * @return string
	 */
	public function urlRegex($match)
	{
		if(isset($match[2]) && $match[2]) {
			$regex = '(?!/';
		} else {
			$regex = '(?:/';
		}
		if(isset($match[3])) {
			$match[3] = '(?:' . $match[3] . ')';
		} else {
			$match[3] = '(?:[^/]*)';
		}
		if(!$match[1]) {
			$regex .= $match[3];
		} else {
			$this->vars[] = $match[1];
			$regex .= '(?P<' . $match[1] . '>' . $match[3] . ')';
		}
		$regex .= ')';

		return $regex;
	}

	public function attach($event, \Closure $listener)
	{
		$this->dispatcher->attach("route.$event", $listener);

		return $this;
	}

	public function detach($event, \Closure $listener)
	{
		$this->dispatcher->detach("route.$event", $listener);

		return $this;
	}

	public function notify($event, &$feedback = array())
	{
		return $this->dispatcher->notify("route.$event", $feedback);
	}
}
