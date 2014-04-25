<?php
namespace Aqua\Event;

interface SubjectInterface
{
	function attach($event, $listener);
	function detach($event, $listener);
	function notify($event, &$feedback);
}
