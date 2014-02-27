<?php
namespace Aqua\Captcha\Exception;

class ReCaptchaException
extends \Exception
{
	const FAIL_TO_OPEN_SOCKET = 0x01;
	const EXT_MISSING = 0x02;
}
