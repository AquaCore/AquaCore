<?php
namespace Aqua\Router;

use Aqua\Http\Request;

class Router
{
	/**
	 * @var \Aqua\Router\Route[]
	 */
	public $routes = array();

	/**
	 * @param string $name
	 * @return bool
	 */
	public function exists($name)
	{
		return isset($this->routes[$name]);
	}

	/**
	 * @param string $name
	 * @return \Aqua\Router\Route
	 */
	public function get($name)
	{
		return ($this->exists($name) ? $this->routes[$name] : $this->add($name));
	}

	/**
	 * @param string $name
	 * @param bool   $prepend
	 * @return \Aqua\Router\Route
	 */
	public function add($name, $prepend = true)
	{
		if($prepend) {
			$this->routes = array( $name => new Route ) + $this->routes;
		} else {
			$this->routes[$name] = new Route;
		}

		return $this->routes[$name];
	}

	/**
	 * @param string $name
	 * @return \Aqua\Router\Router
	 */
	public function remove($name)
	{
		unset($this->routes[$name]);

		return $this;
	}

	/**
	 * @param \Aqua\Http\Request $request
	 */
	public function route(Request $request)
	{
		$routes = $this->routes;
		$uri    = urldecode(ac_build_query(array(
				'url_rewrite' => true,
				'path'        => $request->uri->path,
				'arguments'   => $request->uri->arguments,
				'action'      => $request->uri->action,
			))) . '/';
		do {
			if(current($routes)->parse($uri, $request)) {
				break;
			}
		} while(next($routes) !== false);
	}
}
