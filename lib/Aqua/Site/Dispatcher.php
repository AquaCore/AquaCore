<?php
namespace Aqua\Site;

use Aqua\Core\User;
use Aqua\Event\EventDispatcher;
use Aqua\Event\SubjectInterface;
use Aqua\Http\Response;
use Aqua\Permission\Permission;
use Aqua\Permission\PermissionSet;
use Aqua\Router\Router;
use Aqua\UI\Theme;
use Page\Common;

class Dispatcher
implements SubjectInterface
{
	/**
	 * @var \Aqua\Router\Router
	 */
	public $router;
	/**
	 * @var \Aqua\Permission\PermissionSet
	 */
	public $permissions;
	/**
	 * @var \Aqua\Site\Page[]
	 */
	public $pages = array();
	/**
	 * @var bool
	 */
	public $dispatchStarted = false;
	/**
	 * @var \SplPriorityQueue[]
	 */
	public $extend = array();
	/**
	 * @var \Aqua\Event\EventDispatcher
	 */
	protected $_dispatcher;

	/**
	 * @var array
	 */
	private $_currentData = null;
	private $_error;

	public function __construct(Router $router, PermissionSet $permissions)
	{
		$this->router = $router;
		$this->permissions = $permissions;
		$this->_dispatcher = new EventDispatcher;
	}

	public function dispatch(User $user, Response $response)
	{
		$request = clone $user->request;
		$theme   = new Theme();
		$this->_error = false;
		$this->_currentData = array(
			'request'    => &$request,
			'response'   => &$response,
			'theme'      => &$theme,
			'layout'   => 'layout',
			'page_title' => null,
		);
		$this->router->route($request);
		$pages  = $request->uri->path;
		$class  = 'page';
		do {
			$page = current($pages);
			$class.= "\\$page";
			if(class_exists($class) && is_subclass_of($class, 'Aqua\\Site\\Page')) {
				$this->push(new $class);
			} else {
				$this->triggerError(404);
				break;
			}
		} while(next($pages) !== false && !$this->_error);
		$this->notify('dispatch-start');
		$status = $this->permissions->check($user, $request);
		if($status & Permission::STATUS_AUTHENTICATE) {
			$this->triggerError(401);
		} else if($status & Permission::STATUS_DENIED) {
			$this->triggerError(403);
		}
		while(($page = current($this->pages)) !== false) {
			$page->run();
			next($this->pages);
		}
		$this->dispatchStarted = true;
		ob_start();
		$_page = end($this->pages);
		$action = $request->uri->action;
		$arguments = $request->uri->arguments;
		$feedback = array( &$_page, &$action, &$arguments );
		if($this->notify('render-start', $feedback) !== false) {
			$path = implode('/', $request->uri->path);
			if(isset($this->extend[$path])) {
				$this->extend[$path]->setExtractFlags(\SplPriorityQueue::EXTR_BOTH);
				foreach($this->extend[$path] as $data) {
					$_page->extend($data['data'], $data['priority']);
				}
			}
			$_page->action($action, $arguments);
		}
		$content = ob_get_contents();
		$feedback[] = &$content;
		$this->notify('render-end', $feedback);
		ob_end_clean();
		$theme->set('user', $user);       // Partial: flash
		$content = $theme->render($this->_currentData['layout'], $this->_currentData['page_title'], $content);
		$this->pages = array();
		$this->_currentData = null;
		$this->dispatchStarted = false;
		return $content;
	}

	public function push(Page $page)
	{
		if(!$this->_currentData) {
			throw new \Exception;
		}
		$page->dispatcher = &$this;
		$page->theme      = &$this->_currentData['theme'];
		$page->request    = &$this->_currentData['request'];
		$page->response   = &$this->_currentData['response'];
		$page->layout     = &$this->_currentData['layout'];
		$page->title      = &$this->_currentData['page_title'];
		$feedback         = array( &$page );
		$this->pages[]    = $page;
		$this->notify('add_page', $feedback);
		return $this;
	}

	public function extend($page, $className, $priority = 0)
	{
		$page = strtolower(trim($page, '/'));
		if(!isset($this->extend[$page])) {
			$this->extend[$page] = new \SplPriorityQueue;
		}
		$this->extend[$page]->insert($className, $priority);
		return $this;
	}

	public function triggerError($code, $title = null, $message = null)
	{
		if(!$this->_currentData) {
			throw new \Exception;
		}
		$this->_currentData['request']->uri->action = 'error';
		$this->_currentData['request']->uri->arguments = array( $code, $title, $message );
		$this->_currentData['layout'] = 'error';
		$this->push(new Common);
		$this->_error = true;
		return $this;
	}

	public function attach($event, $listener)
	{
		$this->_dispatcher->attach("dispatcher.$event", $listener);
		return $this;
	}

	public function detach($event, $listener)
	{
		$this->_dispatcher->detach("dispatcher.$event", $listener);
		return $this;
	}

	public function notify($event, &$feedback = array())
	{
		return $this->_dispatcher->notify("dispatcher.$event", $feedback);
	}
}
