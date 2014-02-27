<?php
namespace Aqua\Permission;

use Aqua\Core\User;
use Aqua\Http\Request;
use Aqua\Http\Uri;

/**
 * Class PermissionSet
 *
 * @package Aqua\Permission
 */
class PermissionSet
{
	/**
	 * @var \Aqua\Permission\Permission[]
	 */
	public $permissions = array();

	/**
	 * Add a permission to the set and returns the newly created Permission object
	 *
	 * @param string $path
	 * @return \Aqua\Permission\Permission
	 */
	public function set($path)
	{
		list($path, $action) = self::parsePath($path);
		$permissions = & $this->permissions;
		foreach($path as $page) {
			if(!isset($permissions[$page])) {
				$permissions[$page] = array();
			}
			$permissions = & $permissions[$page];
		}
		if(!is_array($action)) {
			$action = (array)$action;
		}
		$permission = new Permission;
		foreach($action as $_action) {
			if(!isset($permissions[$_action])) {
				$permissions[$_action] = & $permission;
			}
		}

		return $permission;
	}

	/**
	 * Get a Permission which applies to the given Uri
	 *
	 * @param \Aqua\Http\Uri $uri
	 * @return \Aqua\Permission\Permission|bool
	 */
	public function get(Uri $uri)
	{
		$path       = $uri->path;
		$action     = $uri->action;
		$permission = $this->permissions;
		$result     = false;
		if(($count = count($path))) {
			for($i = 0; $i < $count; ++$i) {
				$page = $path[$i];
				if(!is_array($permission)) {
					return $permission;
				}
				if(isset($permission['*'])) {
					$result = $permission['*'];
				}
				if(isset($permission[$page])) {
					$permission = & $permission[$page];
				} else {
					return $result;
				}
			}
		}
		if(isset($permission[$action])) {
			return $permission[$action];
		} else if(isset($permission['*'])) {
			return $permission['*'];
		} else {
			return $result;
		}
	}

	/**
	 * Extract the path and actions from a url path string
	 *
	 * @param string $path
	 * @return array
	 */
	public function parsePath($path)
	{
		$path = explode('action/', $path);
		if(empty($path[1])) {
			$action = '*';
		} else {
			if(preg_match('/\[([^\]]*)\]/', $path[1], $match)) {
				$action = explode('|', $match[1]);
			} else {
				$action = $path[1];
			}
		}
		$path = trim($path[0], '/');
		if(empty($path)) {
			$path = array();
		} else {
			$path = explode('/', $path);
		}

		return array( $path, $action );
	}

	/**
	 * Check whether a User has permission to access a page
	 *
	 * @param \Aqua\Core\User    $user
	 * @param \Aqua\Http\Request $request
	 * @return int
	 * @see \Aqua\Permission\Permission::STATUS_ALLOWED
	 * @see \Aqua\Permission\Permission::STATUS_DENIED
	 * @see \Aqua\Permission\Permission::STATUS_ROLE_DENIED
	 * @see \Aqua\Permission\Permission::STATUS_PERMISSION_DENIED
	 * @see \Aqua\Permission\Permission::STATUS_AUTHENTICATE
	 * @see \Aqua\Permission\Permission::STATUS_FILTERED
	 * @see \Aqua\Permission\Permission::STATUS_NO_SUITABLE_PERMISSION
	 */
	public function check(User $user, Request $request)
	{
		$permission = $this->get($request->uri);
		if(!$permission instanceof Permission) {
			return Permission::STATUS_DENIED | Permission::STATUS_NO_SUITABLE_PERMISSION;
		} else {
			return $permission->check($user);
		}
	}
}
