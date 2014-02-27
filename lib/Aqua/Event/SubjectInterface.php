<?php
namespace Aqua\Event;

interface SubjectInterface
{
	function attach($event, \Closure $listener);
	function detach($event, \Closure $listener);
	function notify($event, &$feedback);
}
