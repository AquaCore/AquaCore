<?php
namespace Aqua\Autoloader;

class ClassMap
{
	/**
	 * @var array
	 */
	public $directories = array();
	/**
	 * @var string
	 */
	public $namespace;
	/**
	 * @var int
	 */
	public $case = 0;
	/**
	 * @var array
	 */
	public $extensions = array( 'php' );

	const CASE_LOWER = 1;
	const CASE_UPPER = 2;

	/**
	 * @param string $namespace
	 */
	public function __construct($namespace)
	{
		$this->namespace = strtolower($namespace);
	}

	/**
	 * @param string $directory
	 * @param bool   $prepend
	 * @return \Aqua\Autoloader\ClassMap
	 */
	public function addDirectory($directory, $prepend = false)
	{
		$directory = rtrim($directory, DIRECTORY_SEPARATOR);
		if($prepend) {
			$this->directories[] = $directory;
		} else {
			array_unshift($this->directories, $directory);
		}

		return $this;
	}

	/**
	 * @param string $directory
	 * @return \Aqua\Autoloader\ClassMap
	 */
	public function removeDirectory($directory)
	{
		$directory = rtrim($directory, DIRECTORY_SEPARATOR);
		if($key = array_search($directory, $this->directories)) {
			unset($this->directories[$key]);
		}

		return $this;
	}

	/**
	 * @param string $class
	 * @return bool|string
	 */
	public function findFile($class)
	{
		$path = str_replace('\\', DIRECTORY_SEPARATOR, strstr($class, '\\'));
		if($this->case === self::CASE_LOWER) {
			$path = strtolower($path);
		} else {
			if($this->case === self::CASE_UPPER) {
				$path = strtoupper($path);
			}
		}
		foreach($this->extensions as $extension) {
			foreach($this->directories as $directory) {
				$file = $directory . $path . '.' . $extension;
				if(file_exists($file)) {
					return $file;
				}
			}
		}

		return false;
	}
}
