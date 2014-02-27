<?php
/**
 * Created by JetBrains PhpStorm.
 * User: root
 * Date: 5/27/13
 * Time: 2:44 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Aqua\Storage\Exception;


class StorageException
extends \Exception
{
	const INVALID_STORAGE_ADAPTER = 0x001;
	const MISSING_EXTENSION = 0x002;
}