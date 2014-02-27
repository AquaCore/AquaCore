<?php
namespace Aqua\Session\Exception;

class SessionException
extends \Exception
{
	const FAILED_TO_UPDATE_SESSION = 1;
	const UNABLE_TO_OPEN = 2;
	const UNABLE_TO_CLOSE = 3;
	const COLLISION = 4;
	const INVALID_SESSION_STATE = 5;
}
