<?php
namespace Aqua\Storage;

interface NumberStorageInterface
{
	function increment($key, $step = 1, $defaultValue = 0, $ttl = 0);
	function decrement($key, $step = 1, $defaultValue = 0, $ttl = 0);
}
