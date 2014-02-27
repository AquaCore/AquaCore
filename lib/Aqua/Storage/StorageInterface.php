<?php
namespace Aqua\Storage;

interface StorageInterface
{
	/**
	 * @param string $option
	 * @param mixed  $value
	 * @return mixed
	 */
	function setOption($option, $value);

	/**
	 * @param string $option
	 * @return mixed
	 */
	function getOption($option);

	/**
	 * @param string $key
	 * @return bool
	 */
	function exists($key);

	/**
	 * @param string $key
	 * @param mixed  $default
	 * @return mixed
	 */
	function fetch($key, $default = null);

	/**
	 * @param string $key
	 * @param mixed  $value
	 * @param int    $ttl
	 * @return bool
	 */
	function add($key, $value, $ttl = 0);

	/**
	 * @param string $key
	 * @param mixed  $value
	 * @param int    $ttl
	 * @return bool
	 */
	function store($key, $value, $ttl = 0);

	/**
	 * @param string $key
	 * @return bool|array
	 */
	function delete($key);
}
