<?php
namespace Aqua\Plugin\Exception;

/**
 * PluginException class
 *
 * Exception caught when when enabling/disabling plugins. The message is displayed instead of a generic
 * unexpected error.
 * @package Aqua\Plugin\Exception
 * @see /Aqua/Core/User::addFlash()
 */
class PluginException
extends \Exception { }
