<?php
namespace Aqua\Plugin\Exception;

class PluginManagerException
extends \Exception
{
	const IMPORT_INVALID_STRUCTURE = 1;
	const IMPORT_MISSING_DATA      = 2;
	const IMPORT_DUPLICATE_GUID    = 2;
}
