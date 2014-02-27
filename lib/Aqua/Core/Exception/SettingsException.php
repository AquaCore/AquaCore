<?php
namespace Aqua\Core\Exception;

class SettingsException
extends \Exception
{
	const FAILED_TO_IMPORT = 0x01;
	const FAILED_TO_EXPORT = 0x02;
}