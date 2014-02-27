<?php
namespace Aqua\Site;

use Aqua\Event\EventDispatcher;
use Page\Common;

abstract class Page
{
	/**
	 * @var \Aqua\Site\Dispatcher
	 */
	public $dispatcher;

	/**
	 * @var \Aqua\UI\Theme
	 */
	public $theme;

	/**
	 * @var \Aqua\Http\Request
	 */
	public $request;

	/**
	 * @var \Aqua\Http\Response
	 */
	public $response;

	/**
	 * @var string
	 */
	public $title;

	/**
	 * @var string
	 */
	public $layout;

	/**
	 * @var int
	 */
	protected $_depth = 0;

	/**
	 * @var \Aqua\Event\EventDispatcher
	 */
	protected $_dispatcher;

	public final function __construct()
	{
		$this->_dispatcher = new EventDispatcher;
		call_user_func_array(array($this, 'onConstruct'), func_get_args());
	}

	public function onConstruct() {}

	public function run() { }

	public function actionExists($name)
	{
		return method_exists($this, $name . '_action');
	}

	public function action($action, $arguments)
	{
		$feedback = array( &$action, &$arguments );
		$this->notify('call_action', $feedback);
		if($this->actionExists($action)) {
			call_user_func_array(array($this, $action . '_action'), $arguments);
		} else {
			$this->onCallAction($action, $arguments);
		}
	}

	public function onCallAction($action, $parameters)
	{
		$this->error(404);
	}

	public function attach($event, \Closure $listener)
	{
		$this->_dispatcher->attach("page.$event", $listener);
	}

	public function detach($event, \Closure $listener)
	{
		$this->_dispatcher->detach("page.$event", $listener);
	}

	public function notify($event, &$feedback = null)
	{
		return $this->_dispatcher->notify("page.$event", $feedback);
	}

	protected function error($code, $title = null, $message = null)
	{
		if(!$this->dispatcher->dispatchStarted) {
			$this->dispatcher->triggerError($code, $title, $message);
		} else {
			$this->layout = 'error';
			$page = new Common();
			$page->response = &$this->response;
			$page->request = &$this->request;
			$page->title = &$this->title;
			$page->theme = &$this->theme;
			$page->layout = &$this->layout;
			$page->action('error', array($code, $title, $message));
		}
	}

	abstract public function index_action();
}
