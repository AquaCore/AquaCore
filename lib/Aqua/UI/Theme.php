<?php
namespace Aqua\UI;

use Aqua\Core\App;
use Aqua\Core\L10n;
use Aqua\UI\Theme\Footer;
use Aqua\UI\Theme\Head;

class Theme
extends Template
{
	/**
	 * @var string
	 */
	public $url;
	/**
	 * @var string
	 */
	public $directory;
	/**
	 * @var
	 */
	public $template;
	/**
	 * @var null
	 */
	public $theme = null;
	/**
	 * @var array
	 */
	public $jsLang = array();
	/**
	 * @var array
	 */
	public $jsSettings = array();
	/**
	 * @var \Aqua\UI\Theme\Head
	 */
	public $head;
	/**
	 * @var \Aqua\UI\Theme\Footer
	 */
	public $footer;
	/**
	 * @var \Aqua\UI\Tag\Script
	 */
	public $script;
	/**
	 * @var string
	 */
	public $bodyClass = '';

	/**
	 * @param string|null $name
	 */
	public function __construct($name = null)
	{
		$this->head   = new Head;
		$this->footer = new Footer;
		$this->script = $this->head->enqueueScript('aquacore.data')->type('text/javascript');
		$this->footer->enqueueScript(ScriptManager::script('aquacore.aquacore'));
		$this->head->bindStylesheet('aquacore.stylesheet', App::$styleSheet);
		$thm = & $this;
		$this->head->attach('render', function () use (&$thm) {
				foreach($thm->head->script as $key => $script) {
					unset($thm->footer->script[$key]);
					if($script instanceof ScriptManager) {
						foreach($script->extra as $key) {
							unset($thm->footer->script[$key]);
						}
					}
				}
				foreach($thm->head->link as $key => $link) {
					unset($thm->footer->link[$key]);
				}
			});
		$this->set('head', $this->head);
		$this->set('footer', $this->footer);
		$this->bind('body_class', $this->bodyClass);
		$this->url       = \Aqua\WORKING_URL . "/application/themes/$name";
		$this->directory = \Aqua\ROOT;
		if(\Aqua\WORKING_DIR) {
			$this->directory .= '/' . \Aqua\WORKING_DIR;
		}
		$this->directory .= "/application/themes/$name";
		if(is_dir($this->directory . '/tpl')) {
			array_unshift(Template::$directories, ltrim(\Aqua\WORKING_DIR . "/application/themes/$name/tpl", '/'));
		}
		if(file_exists($this->directory . '/functions.php')) {
			include $this->directory . '/functions.php';
		}
	}

	public function reset()
	{
		$this->jsLang = array();
		$this->jsSettings = array();
		$this->data = array();
		$this->set('head', $this->head);
		$this->set('footer', $this->footer);
		$this->bind('body_class', $this->bodyClass);
		$this->template = null;
		return $this;
	}

	/**
	 * @param $class
	 * @return $this
	 */
	public function addBodyClass($class)
	{
		$this->bodyClass .= "$class ";

		return $this;
	}

	/**
	 * @param string|array $namespace
	 * @param string|array $keys
	 * @param string       $str
	 * @return \Aqua\UI\Theme
	 */
	public function addWordGroup($namespace, $keys = null, $str = null)
	{
		if($keys) {
			if(is_array($keys)) {
				foreach($keys as $key) {
					$this->jsLang[$namespace][$key] = __($namespace, $key);
				}
			} else if($str) {
				$this->jsLang[$namespace][$keys] = $str;
			} else {
				$this->jsLang[$namespace][$keys] = __($namespace, $keys);
			}
		} else {
			$this->jsLang += array( $namespace => L10n::getDefault()->getNamespace($namespace) );
		}

		return $this;
	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 * @return \Aqua\UI\Theme
	 */
	public function addSettings($key, $value)
	{
		$this->jsSettings[$key] = $value;

		return $this;
	}

	/**
	 * @param string $title
	 * @param string $content
	 * @return string
	 */
	public function renderTemplate($title, $content)
	{
		$template = strtolower($this->template);
		foreach(array( $template, 'default' ) as $name) {
			$file = $this->directory . "/templates/$name.php";
			if(file_exists($file)) {
				$this->addBodyClass("ac-template-$name");
				extract($this->data, EXTR_SKIP | EXTR_REFS);
				ob_start();
				include $file;
				$content = ob_get_contents();
				ob_end_clean();

				return $content;
			}
		}

		return $title . $content;
	}

	/**
	 * @param string $template
	 * @param string $title
	 * @param string $content
	 * @return string
	 */
	public function render($template, $title = null, $content = null)
	{
		$content = $this->renderTemplate($title, $content);
		$tz      = new \DateTimeZone(date_default_timezone_get());
		$now     = new \DateTime("now", $tz);
		$lang    = L10n::getDefault();
		$json    = array(
			'URL'         => \Aqua\URL,
			'URI'         => array(
				'path' => App::request()->uri->path,
				'action' => App::request()->uri->action,
				'arguments' => App::request()->uri->arguments,
				'query' => App::request()->uri->parameters,
			),
			'TIME_OFFSET' => $tz->getOffset($now),
			'REWRITE'     => \Aqua\REWRITE,
			'BASE_DIR'    => App::settings()->get('base_dir'),
			'DIR'         => \Aqua\WORKING_DIR,
			'SCRIPT_NAME' => \Aqua\SCRIPT_NAME,
			'USER_ID'     => (App::user()->loggedIn() ? App::user()->account->id : null),
			'settings'    => $this->jsSettings
		);
		if($lang) {
			$json['language'] = array(
				'words'     => $this->jsLang,
				'direction' => $lang->direction,
				'code'      => $lang->code,
				'name'      => $lang->name
			);
		}
		$json = json_encode($json);
		$this->script->content = array("
var AquaCore = AquaCore || {};
(function() {
	function extend(obj1, obj2) {
		for(var k in obj1) {
			if(obj1.hasOwnProperty(k)) {
				if(typeof obj1[k] !== \"object\" || (Object.prototype.toString.call(obj1[k]) === \"[object Array]\")) {
					obj2[k] = obj1[k];
				} else {
					obj2[k] = obj2[k] || {};
					extend(obj1[k], obj2[k]);
				}
			}
		}
	}
	extend($json, AquaCore);
})();
");
		unset($json, $tz, $now, $lang);
		extract($this->data, EXTR_SKIP | EXTR_REFS);
		$__url  = $this->url;
		$__file = $this->directory . "/$template.php";
		if($this->directory && (file_exists($__file) || ($__file = $this->directory . "/layout.php") && file_exists($__file))) {
			ob_start();
			include $__file;
			$content = ob_get_contents();
			ob_end_clean();

			return $content;
		} else {
			return $content;
		}
	}
}
