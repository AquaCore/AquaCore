<?php
namespace Aqua\Core\Exception;

class CoreException
extends \Exception
{
	const MISSING_EXTENSION   = 0x001;
	const MISSING_FILE        = 0x002;
	const INVALID_TIMEZONE    = 0x003;
	const INVALID_LOCALE      = 0x003;
	const INVALID_SEARCH_FLAG = 0x004;
}