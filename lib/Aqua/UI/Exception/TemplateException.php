<?php
namespace Aqua\UI\Exception;

class TemplateException
extends \Exception
{
	const TEMPLATE_FILE_NOT_FOUND = 0x1;
	const TEMPLATE_FILE_NOT_READABLE = 0x2;
}
