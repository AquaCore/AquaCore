<?php
namespace Aqua\Permission;

use Aqua\Core\App;
use Aqua\Core\User;
use Aqua\Event\EventDispatcher;
use Aqua\Event\SubjectInterface;
use Aqua\Http\Request;
use Aqua\User\Role;

class Permission
implements SubjectInterface
{
	/**
	 * @var int
	 */
	public $options;
	/**
	 * @var array
	 */
	public $roles = array( 'allow' => array(), 'deny' => array() );
	/**
	 * @var array
	 */
	public $permissions = array( 'allow' => array(), 'deny' => array() );
	/**
	 * @var callable[]
	 */
	public $filter = array();
	/**
	 * @var string
	 */
	public $realm = 'AquaCore';
	/**
	 * @var array
	 */
	public $credentials = array();
	/**
	 * @var \Aqua\Event\EventDispatcher
	 */
	public $dispatcher;

	const SESSION_KEY = '__AquaCore_Http_Auth';
	/**
	 * Evaluate allowed groups first
	 */
	const ORDER_ALLOW_DENY = 1;
	/**
	 * Evaluate denied groups first
	 */
	const ORDER_DENY_ALLOW = 2;
	/**
	 * Evaluate roles before permissions
	 */
	const ORDER_ROLE_PERMISSION = 4;
	/**
	 * Evaluate permissions before roles
	 */
	const ORDER_PERMISSION_ROLE         = 8;

	const DENY_ALL                      = 16;
	const ALLOW_ALL                     = 32;

	const STATUS_ALLOWED                = 1;
	const STATUS_DENIED                 = 2;
	/**
     * Addition info on denied status:
	 * The user's role is in the list of denied roles
	 */
	const STATUS_ROLE_DENIED            = 4;
	/**
     * Addition info on denied status:
	 * The user doesn't have a permission or set of permissions required to access the page
	 */
	const STATUS_PERMISSION_DENIED      = 8;
	/**
     * Addition info on denied status:
	 * Authentication is required
	 */
	const STATUS_AUTHENTICATE           = 16;
	/**
     * Addition info on denied status:
	 * Filtered by a user defined function
	 */
	const STATUS_FILTERED               = 32;
	/**
     * Addition info on denied status:
	 * No permission applies to the page
	 */
	const STATUS_NO_SUITABLE_PERMISSION = 64;

	public function __construct()
	{
		$this->dispatcher = new EventDispatcher();
	}

	/**
	 * Set the permission's evaluation order
	 *
	 * @param int $order
	 * @return \Aqua\Permission\Permission
	 * @see \Aqua\Permission\Permission::ORDER_ALLOW_DENY
	 * @see \Aqua\Permission\Permission::ORDER_DENY_ALLOW
	 * @see \Aqua\Permission\Permission::ORDER_ROLE_PERMISSION
	 * @see \Aqua\Permission\Permission::ORDER_PERMISSION_ROLE
	 */
	public function order($order)
	{
		$this->options &= ~(self::ORDER_ALLOW_DENY | self::ORDER_DENY_ALLOW |
                            self::ORDER_PERMISSION_ROLE | self::ORDER_ROLE_PERMISSION);
		$this->options |= $order;

		return $this;
	}

	/**
     * Skip all rules and allow access from anyone
     *
	 * @return \Aqua\Permission\Permission
	 * @see \Aqua\Permission\Permission::ALLOW_ALL
	 */
	public function allowAll()
	{
		$this->options &= ~self::DENY_ALL;
		$this->options |= self::ALLOW_ALL;

		return $this;
	}

	/**
     * Skip all rules and deny access from anyone
     *
	 * @return \Aqua\Permission\Permission
	 * @see \Aqua\Permission\Permission::DENY_ALL
	 */
	public function denyAll()
	{
		$this->options &= ~self::ALLOW_ALL;
		$this->options |= self::DENY_ALL;

		return $this;
	}

	/**
     * Add login credentials for Http authentication
     *
	 * @param string $username
	 * @param string $password
	 * @return \Aqua\Permission\Permission
	 */
	public function auth($username, $password = null)
	{
		$this->credentials[] = array( $username, $password );

		return $this;
	}

	/**
     * Set the Http authentication realm
     *
	 * @param string $realm
	 * @return \Aqua\Permission\Permission
	 */
	public function realm($realm)
	{
		$this->realm = $realm;

		return $this;
	}

	/**
	 * @param \Aqua\User\Role $role
	 * @return \Aqua\Permission\Permission
	 */
	public function allowRole(Role $role)
	{
		$this->roles['allow'][] = $role->id;

		return $this;
	}

	/**
	 * @param \Aqua\User\Role $role
	 * @return \Aqua\Permission\Permission
	 */
	public function denyRole(Role $role)
	{
		$this->roles['deny'][] = $role->id;

		return $this;
	}

	/**
	 * @param string $permission
	 * @return \Aqua\Permission\Permission
	 */
	public function allowPermission($permission)
	{
		$this->permissions['allow'][] = $permission;

		return $this;
	}

	/**
	 * @param string $permission
	 * @return \Aqua\Permission\Permission
	 */
	public function denyPermission($permission)
	{
		$this->permissions['deny'][] = $permission;

		return $this;
	}

	/**
	 * @param string   $name
	 * @param callable $function
	 * @return \Aqua\Permission\Permission
	 */
	public function addFilter($name, $function)
	{
		if(is_callable($function)) {
			$this->filter[$name] = $function;
		}

		return $this;
	}

	/**
	 * @param string $name
	 * @return \Aqua\Permission\Permission
	 */
	public function removeFilter($name)
	{
		unset($this->filter[$name]);

		return $this;
	}

	/**
	 * Check whether a User has permission to access a page.
	 *
	 * @param \Aqua\Core\User $user
	 * @return int
	 * @see \Aqua\Permission\Permission::STATUS_ALLOWED
	 * @see \Aqua\Permission\Permission::STATUS_DENIED
	 * @see \Aqua\Permission\Permission::STATUS_ROLE_DENIED
	 * @see \Aqua\Permission\Permission::STATUS_PERMISSION_DENIED
	 * @see \Aqua\Permission\Permission::STATUS_AUTHENTICATE
	 * @see \Aqua\Permission\Permission::STATUS_FILTERED
	 */
	public function check(User $user)
	{
		if($this->options & self::ALLOW_ALL) {
			return self::STATUS_ALLOWED;
		} else if($this->options & self::DENY_ALL) {
			return self::STATUS_DENIED;
		}
		$auth = $user->session->get(self::SESSION_KEY, array());
		if(!empty($this->credentials) && !isset($auth[$this->realm])) {
			$authorized = false;
			do {
				list($username, $password) = current($this->credentials);
				if($user->request->authUsername === $username &&
				   (!$password || $user->request->authPassword === $password)
				) {
					$authorized = true;
					break;
				}
			} while(next($this->credentials) !== false);
			if(!$authorized) {
				return self::STATUS_DENIED | self::STATUS_AUTHENTICATE;
			} else {
				$auth[$this->realm] = true;
				$user->session->set(self::SESSION_KEY, $auth);
			}
			reset($this->credentials);
		}
		foreach($this->filter as $function) {
			if($function($user) === false) {
				return self::STATUS_DENIED | self::STATUS_FILTERED;
			}
		}
		if($this->options & self::ORDER_ROLE_PERMISSION) {
			if($this->checkRoles($user->role())) {
				return $this->checkPermissions($user->role()) ?
					self::STATUS_ALLOWED : self::STATUS_DENIED | self::STATUS_PERMISSION_DENIED;
			} else {
				return self::STATUS_DENIED | self::STATUS_ROLE_DENIED;
			}
		} else {
			if($this->checkPermissions($user->role())) {
				return $this->checkRoles($user->role()) ?
					self::STATUS_ALLOWED : self::STATUS_DENIED | self::STATUS_ROLE_DENIED;
			} else {
				return self::STATUS_DENIED | self::STATUS_PERMISSION_DENIED;
			}
		}
	}

	/**
	 * Checks if a role is in the list of allowed/denied roles
	 *
	 * @param \Aqua\User\Role $role
	 * @return bool
	 */
	public function checkRoles(Role $role)
	{
		if($this->options & self::ORDER_ALLOW_DENY && !empty($this->roles['allow'])) {
			return in_array($role->id, $this->roles['allow']);
		} else if($this->options & self::ORDER_DENY_ALLOW && !empty($this->roles['deny'])) {
			return !in_array($role->id, $this->roles['deny']);
		}

		return true;
	}

	/**
	 * Check whether a role has all necessary permissions
	 *
	 * @param \Aqua\User\Role $role
	 * @return bool
	 */
	public function checkPermissions(Role $role)
	{
		$permissions = $role->permissions();
		if($this->options & self::ORDER_DENY_ALLOW) {
			return (!empty($this->permissions['deny']) &&
			        count(array_intersect($this->permissions['deny'], $permissions)) ===
			        count($this->permissions['deny']) ? false :
				(!empty($this->permissions['allow']) &&
				 count(array_intersect($this->permissions['allow'], $permissions)) !==
				 count($this->permissions['allow']) ? false : true));
		} else {
			return (!empty($this->permissions['allow']) &&
			        count(array_intersect($this->permissions['allow'], $permissions)) !==
			        count($this->permissions['allow']) ? false :
				(!empty($this->permissions['deny']) &&
				 count(array_intersect($this->permissions['deny'], $permissions)) ===
				 count($this->permissions['deny']) ? false : true));
		}
	}

	public function attach($event, \Closure $listener)
	{
		$this->dispatcher->attach($event, $listener);

		return $this;
	}

	public function detach($event, \Closure $listener)
	{
		$this->dispatcher->detach($event, $listener);

		return $this;
	}

	public function notify($event, &$feedback = array())
	{
		return $this->dispatcher->notify($event, $feedback);
	}
}
