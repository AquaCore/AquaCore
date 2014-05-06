<?php
namespace Aqua\User;

use Aqua\Core\App;
use Aqua\Event\Event;
use Aqua\SQL\Query;
use Aqua\SQL\Search;
use Aqua\UI\Tag;

class Role
{
	/**
	 * @var int
	 */
	public $id;
	/**
	 * @var string
	 */
	public $name;
	/**
	 * @var string
	 */
	public $description;
	/**
	 * @var int
	 */
	public $color;
	/**
	 * @var int
	 */
	public $background;
	/**
	 * @var bool
	 */
	public $protected = false;
	/**
	 * @var bool
	 */
	public $editable = true;
	/**
	 * @var array
	 */
	public $permission = array();

	/**
	 * @var \Aqua\User\Role[]
	 */
	public static $roles;
	/**
	 * @var array
	 */
	public static $permissions;

	const ROLE_CACHE_KEY        = 'roles';
	const PERMISSION_CACHE_KEY  = 'permissions';
	const CACHE_TTL             = 0;

	const ROLE_GUEST = 1;
	const ROLE_USER  = 2;
	const ROLE_ADMIN = 3;

	protected function __construct() { }

	/**
	 * @param array $edit
	 * @return bool
	 */
	public function update(array $edit)
	{
		$values = array();
		$update = '';
		$edit   = array_intersect_key($edit, array_flip(array(
				'name', 'description', 'color',
				'background', 'protected', 'editable'
			)));
		if(empty($edit)) {
			return false;
		}
		$edit = array_map(function ($val) { return (is_string($val) ? trim($val) : $val); }, $edit);
		if(array_key_exists('name', $edit) && $edit['name'] !== $this->name) {
			$values['name'] = $edit['name'];
			$update .= '_name = ?, ';
		}
		if(array_key_exists('description', $edit) && $edit['description'] !== $this->description) {
			$values['description'] = $edit['description'];
			if($edit['description'] === null) {
				$update .= '_description = NULL, ';
			} else {
				$update .= '_description = ?, ';
			}
		}
		if(array_key_exists('color', $edit) && $edit['color'] !== $this->color) {
			$values['color'] = $edit['color'];
			if($edit['color'] === null) {
				$update .= '_color = NULL, ';
			} else {
				$update .= '_color = ?, ';
			}
		}
		if(array_key_exists('background', $edit) && $edit['background'] !== $this->background) {
			$values['background'] = $edit['background'];
			if($edit['background'] === null) {
				$update .= '_background = NULL, ';
			} else {
				$update .= '_background = ?, ';
			}
		}
		if(array_key_exists('protected', $edit) && (bool)$edit['protected'] !== $this->protected) {
			$values['protected'] = ($edit['protected'] ? 'y' : 'n');
			$update .= '_protected = ?, ';
		}
		if(array_key_exists('editable', $edit) && (bool)$edit['editable'] !== $this->editable) {
			$values['editable'] = ($edit['editable'] ? 'y' : 'n');
			$update .= '_editable = ?, ';
		}
		if(empty($values)) {
			return false;
		}
		$update   = substr($update, 0, -2);
		$values[] = $this->id;
		$tbl      = ac_table('roles');
		$sth      = App::connection()->prepare("
		UPDATE `$tbl`
		SET $update
		WHERE id = ?
		");
		$sth->execute(array_values(array_filter($values, function($x) { return !is_null($x); })));
		if(!$sth->rowCount()) {
			return false;
		}
		array_pop($values);
		Event::fire('role.update', $feedback);
		self::rebuildRoleCache($this->id);

		return true;
	}

	/**
	 * @param string|array $permission
	 * @return bool
	 */
	public function hasPermission($permission)
	{
		if(is_array($permission)) {
			foreach($permission as $p) {
				if(!$this->hasPermission($p)) {
					return false;
				}
			}

			return true;
		}

		return isset($this->permission[$permission]);
	}

	/**
	 * @param string|array $permissions
	 * @param bool         $protected
	 * @return bool|array
	 */
	public function addPermission($permissions, $protected = false)
	{
		if(!is_array($permissions)) {
			$permissions = array( $permissions );
		}
		$protected   = ($protected ? 'y' : 'n');
		$xprotected  = ($protected ? 2 : 1 );
		$added       = array();
		$permissionList = array_flip(self::permissions());
		$permissions = array_unique($permissions);
		$sth = App::connection()->prepare(sprintf('
		REPLACE INTO `%s` (_role_id, _permission, _protected)
		VALUES (:role, :permission, :protected)
		', ac_table('role_permissions')));
		foreach($permissions as $name) {
			if(!array_key_exists($name, $permissionList) ||
			   array_key_exists($name, $this->permission) &&
			   $this->permission[$name] !== $xprotected) {
				continue;
			}
			$sth->bindValue(':role', $this->id, \PDO::PARAM_INT);
			$sth->bindValue(':permission', $permissionList[$name], \PDO::PARAM_INT);
			$sth->bindValue(':protected', $protected, \PDO::PARAM_STR);
			$sth->execute();
			$sth->closeCursor();
			$added[] = $name;
		}
		if(!count($added)) {
			return false;
		}
		self::rebuildRoleCache($this->id);
		$feedback = array( $this, $added );
		Event::fire('role.add-permission', $feedback);

		return $added;
	}

	/**
	 * @param string|array $permissions
	 * @return bool|array
	 */
	public function removePermission($permissions)
	{
		if(!is_array($permissions)) {
			$permissions = array( $permissions );
		}
		$deleted = array();
		$permissionList = array_flip(self::permissions());
		$permissions = array_unique($permissions);
		$sth = App::connection()->prepare(sprintf('
		DELETE FROM `%s`
		WHERE _role_id = :role
		AND _permission = :permission
		AND _protected = \'n\'
		', ac_table('role_permissions')));
		foreach($permissions as $name) {
			if(!array_key_exists($name, $this->permission)) {
				continue;
			}
			$sth->bindValue(':role', $this->id, \PDO::PARAM_INT);
			$sth->bindValue(':permission', $permissionList[$name], \PDO::PARAM_INT);
			if($sth->execute() && $sth->rowCount()) {
				$deleted[] = $name;
			}
			$sth->closeCursor();
		}
		if(!count($deleted)) {
			return false;
		}
		self::rebuildRoleCache($this->id);
		$feedback = array( $this, $deleted );
		Event::fire('role.remove-permission', $feedback);

		return $deleted;
	}

	/**
	 * @return array
	 */
	public function getPermissions()
	{
		return array_keys(array_filter($this->permission));
	}

	/**
	 * @param string       $str
	 * @param string       $class
	 * @return \Aqua\UI\Tag
	 */
	public function display($str, $class = '')
	{
		if($str === null) {
			$str = $this->name;
		}
		$tag = new Tag('span');
		$tag->append(htmlspecialchars($str));
		if($this->color !== null) {
			$class .= ' has-color';
			$tag->css('color', sprintf('#%06x', $this->color));
		}
		if($this->background !== null) {
			$class .= ' has-background';
			$tag->css('background-color', sprintf('#%06x', $this->background));
		}
		$tag->attr('class', ltrim($class));

		return $tag;
	}

	/**
	 * @return array
	 */
	public static function permissions()
	{
		self::$permissions !== null or self::loadPermissions();

		return self::$permissions;
	}

	/**
	 * @return array
	 */
	public static function roles()
	{
		self::$roles !== null or self::loadRoles();

		return array_keys(self::$roles);
	}

	/**
	 * @param int $id
	 * @return bool
	 */
	public static function exists($id)
	{
		self::$roles !== null or self::loadRoles();

		return ($id && isset(self::$roles[$id]));
	}

	/**
	 * @param int $id
	 * @return \Aqua\User\Role
	 */
	public static function get($id)
	{
		self::$roles !== null or self::loadRoles();

		return (self::exists($id) ? self::$roles[$id] : null);
	}

	/**
	 * @param string $name
	 * @param string $description
	 * @param int    $color
	 * @param int    $background
	 * @param array  $permissions
	 * @param bool   $protected
	 * @param bool   $editable
	 * @return \Aqua\User\Role|bool
	 */
	public static function create(
		$name,
		$description,
		$color = null,
		$background = null,
		$permissions = array(),
		$protected = false,
		$editable = true
	) {
		self::$roles !== null or self::loadRoles();
		$sth   = App::connection()->prepare(sprintf('
		INSERT INTO `%s` (_name, _description, _color, _background, _protected, _editable)
		VALUES (:name, :description, :color, :background, :protected, :editable)
		', ac_table('roles')));
		$sth->bindValue(':name', trim($name), \PDO::PARAM_STR);
		$sth->bindValue(':description', trim($description), \PDO::PARAM_LOB);
		$sth->bindValue(':protected', $protected ? 'y' : 'n', \PDO::PARAM_STR);
		$sth->bindValue(':editable', $editable ? 'y' : 'n', \PDO::PARAM_STR);
		if($color === null) {
			$sth->bindValue(':color', null, \PDO::PARAM_NULL);
		} else {
			$sth->bindValue(':color', $color, \PDO::PARAM_INT);
		}
		if($background === null) {
			$sth->bindValue(':background', null, \PDO::PARAM_NULL);
		} else {
			$sth->bindValue(':background', $background, \PDO::PARAM_INT);
		}
		$sth->execute();
		$roleId = (int)App::connection()->lastInsertId();
		$sth->closeCursor();
		if(!$roleId) {
			return false;
		}
		unset($protected);
		$sth = App::connection()->prepare(sprintf('
		INSERT INTO `%s` (_role_id, _permission, _protected)
		VALUES (:role , :permission, :protected)
		', ac_table('role_permissions')));
		$existingPermissions = self::permissions();
		foreach($permissions as $permission) {
			if(is_array($permission)) {
				$protected  = ($permission[1] ? 'y' : 'n');
				$permission = $permission[0];
			} else {
				$protected = 'n';
			}
			if(($permission = array_search($permission, $existingPermissions, true)) === false) {
				continue;
			}
			$sth->bindValue(':role', $roleId, \PDO::PARAM_INT);
			$sth->bindValue(':permission', $permission, \PDO::PARAM_INT);
			$sth->bindValue(':protected', $protected, \PDO::PARAM_STR);
			$sth->execute();
			$sth->closeCursor();
		}
		self::rebuildRoleCache($roleId);
		$role     = self::get($roleId);
		$feedback = array( $role );
		Event::fire('role.create', $feedback);

		return $role;
	}

	/**
	 * @param \Aqua\User\Role $role
	 * @return bool
	 */
	public static function delete(self $role)
	{
		if($role->protected) {
			return false;
		}

		$sth = App::connection()->prepare(sprintf('
		UPDATE `%s`
		SET _role_id = :newrole
		WHERE _role_id = :oldrole
		', ac_table('users')));
		$sth->bindValue(':newrole', self::ROLE_USER, \PDO::PARAM_INT);
		$sth->bindValue(':oldrole', $role->id, \PDO::PARAM_INT);
		$sth->execute();
		$sth->closeCursor();

		$sth = App::connection()->prepare(sprintf('
		DELETE FROM `%s`
		WHERE id = ?
		LIMIT 1
		', ac_table('roles')));
		$sth->bindValue(1, $role->id, \PDO::PARAM_INT);
		$sth->execute();

		$sth = App::connection()->prepare(sprintf('
		DELETE FROM `%s`
		WHERE _role_id = ?
		', ac_table('role_permissions')));
		$sth->bindValue(1, $role->id, \PDO::PARAM_INT);
		$sth->execute();
		$sth->closeCursor();
		$feedback = array( $role );
		Event::fire('role.delete', $feedback);
		self::rebuildRoleCache();

		return true;
	}

	public static function loadRoles()
	{
		self::$roles = App::cache()->fetch(self::ROLE_CACHE_KEY, false);
		if(self::$roles === false) {
			self::rebuildRoleCache();
		}
	}

	public static function loadPermissions()
	{
		self::$permissions = App::cache()->fetch(self::PERMISSION_CACHE_KEY, false);
		if(self::$permissions === false) {
			self::rebuildPermissionCache();
		}
	}

	/**
	 * @param \SimpleXmlElement $xml
	 * @param int               $pluginId
	 */
	public static function importPermissions(\SimpleXMLElement $xml, $pluginId = null)
	{
		$insertPermission = App::connection()->prepare(sprintf('
		INSERT IGNORE INTO `%s` (_permission, _plugin_id)
		VALUES (:name, :plugin)
		', ac_table('permissions')));
		$getPermissionId = App::connection()->prepare(sprintf('
		SELECT id FROM `%s`
		WHERE _permission = :name
		', ac_table('permissions')));
		$addToRole = App::connection()->prepare(sprintf('
		REPLACE INTO `%s` (_role_id, _permission, _protected)
		VALUE (:role, :permission, :protected)
		', ac_table('role_permissions')));
		foreach($xml->permission as $permission) {
			if(!($key = (string)$permission->attributes()->key)) {
				continue;
			}
			$insertPermission->bindValue(':name', $key, \PDO::PARAM_STR);
			if($pluginId) {
				$insertPermission->bindValue(':plugin', $pluginId, \PDO::PARAM_INT);
			} else {
				$insertPermission->bindValue(':plugin', null, \PDO::PARAM_NULL);
			}
			$insertPermission->execute();
			$permissionId = App::connection()->lastInsertId();
			if(!$permissionId) {
				$getPermissionId->bindValue(':name', $key, \PDO::PARAM_STR);
				$permissionId = (int)$getPermissionId->fetchColumn(0);
			}
			foreach($permission->role as $role) {
				$protected = (string)$role->attributes()->protected;
				$protected = ($protected && filter_var($protected, FILTER_VALIDATE_BOOLEAN));
				switch(strtolower((string)$role->attributes()->id)) {
					case 'admin':
					case 'administrator':
					case self::ROLE_ADMIN:
						$role = self::ROLE_ADMIN;
						break;
					case 'user':
					case self::ROLE_USER:
						$role = self::ROLE_USER;
						break;
					case 'guest':
					case self::ROLE_GUEST:
						$role = self::ROLE_GUEST;
						break;
					default: continue;
				}
				$addToRole->bindValue(':role', $role, \PDO::PARAM_INT);
				$addToRole->bindValue(':permission', $permissionId, \PDO::PARAM_INT);
				$addToRole->bindValue(':protected', ($protected ? 'y' : 'n'), \PDO::PARAM_STR);
				$addToRole->execute();
				$addToRole->closeCursor();
			}
		}
		self::rebuildRoleCache(array( self::ROLE_ADMIN, self::ROLE_USER, self::ROLE_GUEST ));
		self::rebuildPermissionCache();
	}

	public static function rebuildRoleCache($roleId = null)
	{
		if(!$roleId) {
			self::$roles = array();
		} else if(is_array($roleId)) {
			array_unshift($roleId, Search::SEARCH_IN);
		}
		$select = Query::select(App::connection())
			->columns(array(
				'id' => 'id',
			    'name' => '_name',
			    'color' => '_color',
			    'background' => '_background',
			    'protected' => '_protected',
			    'editable' => '_editable',
			    'description' => '_description',
			))
			->setColumnType(array(
				'id' => 'integer',
			    'color' => 'integer',
			    'background' => 'integer',
			))
			->from(ac_table('roles'));
		if($roleId) {
			$select->where(array( 'id' => $roleId ));
		}
		$select->query();
		while($select->valid()) {
			if(array_key_exists($select->get('id'), self::$roles)) {
				$role = self::$roles[$select->get('id')];
			} else {
				$role = new self;
			}
			$role->id          = $select->get('id');
			$role->name        = $select->get('name');
			$role->color       = $select->get('color');
			$role->background  = $select->get('background');
			$role->protected   = ($select->get('protected') === 'y');
			$role->editable    = ($select->get('editable') === 'y');
			$role->description = $select->get('description');
			$role->permission  = array();
			self::$roles[$role->id] = $role;
			$select->next();
		}

		$select = Query::select(App::connection())
			->columns(array(
				'id'         => 'rp._role_id',
				'permission' => 'p._permission',
				'protected'  => 'rp._protected'
			))
			->setColumnType(array( 'id' => 'interger' ))
			->from(ac_table('role_permissions'), 'rp')
			->rightJoin(ac_table('permissions'), 'rp._permission = p.id', 'p');
		if($roleId) {
			$select->where(array( 'rp._role_id' => $roleId ));
		}
		$select->query();
		while($select->valid()) {
			$roleId = $select->get('id');
			if(!array_key_exists($roleId, self::$roles)) {
				$select->next();
				continue;
			}
			if($select->get('protected') === 'y') {
				self::$roles[$roleId]->permission[$select->get('permission')] = 2;
			} else {
				self::$roles[$roleId]->permission[$select->get('permission')] = 1;
			}
			$select->next();
		}
		App::cache()->store(self::ROLE_CACHE_KEY, self::$roles, self::CACHE_TTL);
	}

	public static function rebuildPermissionCache()
	{
		self::$permissions = array();
		$select = Query::select(App::connection())
			->columns(array(
				'id'   => 'id',
				'name' => '_permission'
			))
			->setColumnType(array( 'id' => 'interger' ))
			->from(ac_table('permissions'))
			->query();
		while($select->valid()) {
			self::$permissions[$select->get('id')] = $select->get('name');
			$select->next();
		}
		App::cache()->store(self::PERMISSION_CACHE_KEY, self::$permissions, self::CACHE_TTL);
	}
}
