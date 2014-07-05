<?php
namespace Aqua\UI;

use Aqua\UI\Exception\TemplateException;

class Template
{
	/**
	 * @var array
	 */
	public $data = array();
	/**
	 * @var array
	 */
	public $directories = array();
	/**
	 * @var int
	 */
	protected $obLevel = 0;
	/**
	 * @var string
	 */
	protected $file = null;
	/**
	 * @var string
	 */
	protected $url = null;

	/**
	 * @var array
	 */
	public static $defaultDirectories = array( 'tpl' );

	/**
	 * @param string $key
	 * @return \Aqua\UI\Template
	 */
	public function remove($key)
	{
		unset($this->data[$key]);

		return $this;
	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 * @return \Aqua\UI\Template
	 */
	public function set($key, $value)
	{
		$this->data[$key] = $value;

		return $this;
	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 * @return \Aqua\UI\Template
	 */
	public function bind($key, &$value)
	{
		$this->data[$key] = & $value;

		return $this;
	}

	/**
	 * @param string $key
	 * @param mixed  $default
	 * @return mixed
	 */
	public function &get($key, $default = null)
	{
		if(!isset($this->data[$key])) {
			$this->data[$key] = $default;
		}

		return $this->data[$key];
	}

	/**
	 * @param string $template
	 * @return string
	 * @throws \Aqua\UI\Exception\TemplateException
	 * @throws \Exception
	 */
	public function render($template)
	{
		$templates     = func_get_args();
		$templateCount = func_num_args();
		$directories   = $this->directories;
		if(empty($directories)) {
			$directories = static::$defaultDirectories;
		}
		for($i = 0; $i < $templateCount; ++$i) {
			$this->file = null;
			$template   = $templates[$i];
			foreach($directories as $directory) {
				if(file_exists(\Aqua\ROOT . "/$directory/$template.php")) {
					$this->file = \Aqua\ROOT . "/$directory/$template.php";
					$this->url  = \Aqua\URL . "/$directory";
					break;
				}
			}
			if(!$this->file) {
				continue;
			}
			ob_start();
			$this->obLevel = ob_get_level();
			try {
				unset($directories);
				unset($templates);
				unset($templateCount);
				unset($template);
				extract($this->data, EXTR_SKIP | EXTR_REFS);
				include $this->file;
				$content = ob_get_contents();
			} catch(\Exception $exception) {
				while(ob_get_level() >= $this->obLevel) {
					ob_end_clean();
				}
				throw $exception;
			}
			ob_end_clean();

			return $content;
		}
		throw new TemplateException(
			__('exception', 'missing-template', $template),
			TemplateException::TEMPLATE_FILE_NOT_FOUND
		);
	}
}
