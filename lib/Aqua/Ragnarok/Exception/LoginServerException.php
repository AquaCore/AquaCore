<?php
namespace Aqua\Ragnarok\Exception;

class LoginServerException
extends \Exception
{
	const FAILED_TO_LOAD_ACCOUNTS = 0x001;
	const INVALID_GENDER = 0x00;
	const INVALID_GROUP_ID = 0x00;
}
