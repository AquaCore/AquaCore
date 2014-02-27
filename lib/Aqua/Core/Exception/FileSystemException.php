<?php
namespace Aqua\Core\Exception;

class FileSystemException
extends \Exception
{
	const INVALID_PERMISSION = 0x002;
	const FILE_NOT_READABLE = 0x002;
	const FILE_NOT_WRITABLE = 0x002;
	const MISSING_FILE_OR_DIRECTORY = 0x002;
}
