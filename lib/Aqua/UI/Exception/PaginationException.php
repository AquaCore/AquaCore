<?php
namespace Aqua\UI\Exception;

class PaginationException
extends \Exception
{
	const RENDERING_FUNCTION_NOT_CALLABLE = 1;
	const INVALID_SLIDING_STYLE           = 2;
}
