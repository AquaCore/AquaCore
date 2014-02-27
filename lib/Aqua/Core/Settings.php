<?php
namespace Aqua\Core;

use Aqua\Core\Exception\FileSystemException;

class Settings
implements \Iterator, \ArrayAccess, \Countable
{
	/**
	 * @var mixed
	 */
	public $data = array();
	/**
	 * @var int
	 */
	public $size = 0;

	/**
	 * @param array $settings
	 */
	public function __construct(array $settings = null)
	{
		if($settings) {
			$this->_parseSettings($settings);
			$this->data = $settings;
		}
	}

	public function key()
	{
		return key($this->data);
	}

	public function current()
	{
		return current($this->data);
	}

	public function next()
	{
		next($this->data);
	}

	public function rewind()
	{
		reset($this->data);
	}

	public function valid()
	{
		return isset($this->data[key($this->data)]);
	}

	public function count()
	{
		return $this->size;
	}

	public function offsetExists($offset)
	{
		return $this->exists($offset);
	}

	public function offsetGet($offset)
	{
		return $this->get($offset);
	}

	public function offsetSet($offset, $value)
	{
		$this->set($offset, $value);
	}

	public function offsetUnset($offset)
	{
		$this->delete($offset);
	}

	/**
	 * @param \Aqua\Core\Settings|array $settings
	 * @param bool                      $override
	 * @return static
	 */
	public function merge($settings, $override = true)
	{
		if(!($settings instanceof self)) {
			if(is_array($settings)) {
				$settings = new self($settings);
			} else {
				return $this;
			}
		}
		if(!$override) {
			$this->data += $settings->data;
		} else {
			$this->data = $settings->data + $this->data;
		}

		return $this;
	}

	/**
	 * @param string $file
	 * @param bool $override
	 * @return static
	 * @throws Exception\FileSystemException
	 */
	public function import($file, $override = true)
	{
		if(!file_exists($file)) {
			throw new FileSystemException(
				__('exception', 'missing-file', $file),
				FileSystemException::MISSING_FILE_OR_DIRECTORY
			);
		}
		if(!is_readable($file)) {
			throw new FileSystemException(
				__('exception', 'file-not-readable', $file),
				FileSystemException::FILE_NOT_READABLE
			);
		}
		$settings = include $file;
		$this->_parseSettings($settings);
		$this->merge($settings, $override);

		return $this;
	}

	/**
	 * @param string $file
	 * @return static
	 * @throws Exception\FileSystemException
	 */
	public function export($file)
	{
		if(file_exists($file) && !is_writable($file)) {
			throw new FileSystemException(
				__('exception', 'file-not-writable', $file),
				FileSystemException::FILE_NOT_WRITABLE
			);
		}
		$settings = $this->dump();
		$old = umask(0);
		file_put_contents($file, $settings);
		chmod($file, \Aqua\PRIVATE_FILE_PERMISSION);
		umask($old);

		return $this;
	}

	/**
	 * @return string
	 */
	public function dump()
	{
		$settings = $this->toArray();
		$settings = "<?php\r\nreturn " . var_export($settings, true) . ";\r\n";

		return $settings;
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function exists($key)
	{
		return array_key_exists($key, $this->data);
	}

	/**
	 * @param string $key
	 * @return self|mixed
	 */
	public function get($key)
	{
		if($this->exists($key)) {
			return $this->data[$key];
		} else if(func_num_args() > 1) {
			return func_get_arg(1);
		} else {
			$settings = new self;
			$this->data[$key] = $settings;
			return $this->data[$key];
		}
	}

	/**
	 * @param string|array $key
	 * @param mixed        $value
	 * @return static
	 */
	public function set($key, $value = null)
	{
		if(!is_array($key)) {
			$key = array( $key => $value );
		}

		return $this->merge($key);
	}

	/**
	 * @param string|array $key
	 * @return static
	 */
	public function delete($key)
	{
		if(is_array($key)) {
			foreach($key as $k) {
				unset($this->data[$k]);
			}
		} else {
			unset($this->data[$key]);
		}

		return $this;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$settings = array();
		foreach($this->data as $key => $value) {
			if($value instanceof self) {
				$settings[$key] = $value->toArray();
			} else {
				$settings[$key] = $value;
			}
		}

		return $settings;
	}

	public function __toString()
	{
		return (string)$this->data;
	}

	public function __get($key)
	{
		return $this->data[$key];
	}

	public function __set($key, $value)
	{
		$this->set($key, $value);
	}

	public function __clone()
	{
		foreach($this->data as &$conf) {
			if($conf instanceof self) {
				$conf = clone $conf;
			}
		}
	}

	/**
	 * @param array $settings
	 */
	protected function _parseSettings(array &$settings)
	{
		foreach($settings as &$value) {
			if(is_array($value)) {
				$value = new self($value);
			}
		}
	}
}
