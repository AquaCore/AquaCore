<?php
namespace Aqua\User;

use Aqua\Core\App;
use Aqua\Event\Event;
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

	const CACHE_KEY  = 'roles';
	const CACHE_TTL  = 0;

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
		$sth->execute(array_values(array_filter($values)));
		if(!$sth->rowCount()) {
			return false;
		}
		array_pop($values);
		Event::fire('role.update', $feedback);
		self::rebuildCache($this->id);

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
		$added       = array();
		$permissions = array_unique($permissions);
		$table       = ac_table('permissions');
		$p_sth       = App::connection()->prepare("
		SELECT id
		FROM `$table`
		WHERE _permission = ?
		LIMIT 1
		");
		$table       = ac_table('role_permissions');
		$rp_sth      = App::connection()->prepare("
		REPLACE INTO `$table` (_role_id, _permission, _protected)
		VALUES (?, ?, ?)
		");
		foreach($permissions as $name) {
			$p_sth->bindValue(1, $name, \PDO::PARAM_STR);
			$p_sth->execute();
			if(!($id = $p_sth->fetchColumn(0))) {
				continue;
			}
			$rp_sth->bindValue(1, $this->id, \PDO::PARAM_INT);
			$rp_sth->bindValue(2, $id, \PDO::PARAM_INT);
			$rp_sth->bindValue(3, $protected, \PDO::PARAM_STR);
			$rp_sth->execute();
			$rp_sth->closeCursor();
			$added[] = $name;
		}
		if(!count($added)) {
			return false;
		}
		self::rebuildCache($this->id);
		$feedback = array( $this, $added );
		Event::fire('role.add-permission', $feedback);

		return $permissions;
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
		$deleted     = array();
		$permissions = array_unique($permissions);
		$table       = ac_table('permissions');
		$p_sth       = App::connection()->prepare("
		SELECT id
		FROM `$table`
		WHERE _permission = ?
		LIMIT 1
		");
		$table       = ac_table('role_permissions');
		$rp_sth      = App::connection()->prepare("
		DELETE FROM `$table`
		WHERE _role_id = ?
		AND _permission = ?
		AND _protected = 'n'
		");
		foreach($permissions as $name) {
			$p_sth->bindValue(1, $name, \PDO::PARAM_STR);
			$p_sth->execute();
			if(!($id = $p_sth->fetchColumn(0))) {
				continue;
			}
			$rp_sth->bindValue(1, $this->id, \PDO::PARAM_INT);
			$rp_sth->bindValue(2, $id, \PDO::PARAM_INT);
			$rp_sth->execute();
			$rp_sth->closeCursor();
			$deleted[] = $name;
		}
		if(!count($deleted)) {
			return false;
		}
		self::rebuildCache($this->id);
		$feedback = array( $this, $deleted );
		Event::fire('role.remove-permission', $feedback);

		return $permissions;
	}

	/**
	 * @return array
	 */
	public function permissions()
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
	public static function permissionList()
	{
		$tbl = ac_table('permissions');
		$sth = App::connection()->query("
		SELECT id, _permission
		FROM `$tbl`
		ORDER BY id
		");
		$permissions = array();
		while($data = $sth->fetch(\PDO::FETCH_NUM)) {
			$permissions[$data[0]] = $data[1];
		}

		return $permissions;
	}

	public static function loadRoles()
	{
		if((self::$roles = App::cache()->fetch(self::CACHE_KEY, false)) === false) {
			self::rebuildCache();
		}
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

		return self::exists($id) ? self::$roles[$id] : null;
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
		$table = ac_table('roles');
		$sth   = App::connection()->prepare("
		INSERT INTO `$table` (_name, _description, _color, _background, _protected, _editable)
		VALUES (?, ?, ?, ?, ?, ?)
		");
		$sth->bindValue(1, trim($name), \PDO::PARAM_STR);
		$sth->bindValue(2, trim($description), \PDO::PARAM_LOB);
		if($color === null) {
			$sth->bindValue(3, null, \PDO::PARAM_NULL);
		}
		else $sth->bindValue(3, $color, \PDO::PARAM_INT);
		if($background === null) $sth->bindValue(4, null, \PDO::PARAM_NULL);
		else $sth->bindValue(4, $background, \PDO::PARAM_INT);
		$sth->bindValue(5, $protected ? 'y' : 'n', \PDO::PARAM_STR);
		$sth->bindValue(6, $editable ? 'y' : 'n', \PDO::PARAM_STR);
		$sth->execute();
		$id = (int)App::connection()->lastInsertId();
		$sth->closeCursor();
		if(!$id) {
			return false;
		}
		$table                = ac_table('role_permissions');
		$sth                  = App::connection()->prepare("
		INSERT INTO `$table` (_role_id, _permission, _protected)
		VALUES (? , ?, ?)
		");
		$existing_permissions = self::permissionList();
		foreach($permissions as $perm) {
			if(is_array($perm)) {
				$_protected = ($perm[1] ? 'y' : 'n');
				$perm       = $perm[0];
			} else {
				$_protected = 'n';
			}
			if(($perm = array_search($perm, $existing_permissions, true)) === false) {
				continue;
			}
			$sth->bindValue(1, $id, \PDO::PARAM_INT);
			$sth->bindValue(2, $perm, \PDO::PARAM_INT);
			$sth->bindValue(3, $_protected, \PDO::PARAM_STR);
			$sth->execute();
			$sth->closeCursor();
		}
		self::rebuildCache($id);
		$role     = self::get($id);
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
		$table = ac_table('users');
		$sth   = App::connection()->prepare("
		UPDATE `$table`
		SET _role_id = ?
		WHERE _role_id = ?
		");
		$sth->bindValue(1, self::ROLE_USER, \PDO::PARAM_INT);
		$sth->bindValue(2, $role->id, \PDO::PARAM_INT);
		$sth->execute();
		$sth->closeCursor();
		$table = ac_table('roles');
		$sth   = App::connection()->prepare("
		DELETE FROM `$table`
		WHERE id = ?
		LIMIT 1
		");
		$sth->bindValue(1, $role->id, \PDO::PARAM_INT);
		$sth->execute();
		$table = ac_table('role_permissions');
		$sth   = App::connection()->prepare("
		DELETE FROM `$table`
		WHERE _role_id = ?
		");
		$sth->bindValue(1, $role->id, \PDO::PARAM_INT);
		$sth->execute();
		$sth->closeCursor();
		$feedback = array( $role );
		Event::fire('role.delete', $feedback);
		self::rebuildCache();

		return true;
	}

	/**
	 * @param \SimpleXmlElement $xml
	 * @param int               $plugin_id
	 */
	public static function importPermissions(\SimpleXMLElement $xml, $plugin_id = null)
	{
		$tbl               = ac_table('permissions');
		$tblx              = ac_table('role_permissions');
		$permisson_sth     =App::connection()->prepare("
		INSERT IGNORE INTO `$tbl` (_permission, _plugin_id)
		VALUES (?, ?)
		");
		$permission_id_sth = App::connection()->prepare("
		SELECT id
		FROM `$tbl`
		WHERE _permission = ?
		");
		$role_sth          = App::connection()->prepare("
		REPLACE INTO `$tblx` (_role_id, _permission, _protected)
		VALUE (?, ?, ?)
		");
		foreach($xml->permission as $perm) {
			if(!($key = (string)$perm->attributes()->key)) continue;
			$permisson_sth->bindValue(1, $key, \PDO::PARAM_STR);
			if($plugin_id) $permisson_sth->bindValue(2, $plugin_id, \PDO::PARAM_INT);
			else $permisson_sth->bindValue(2, null, \PDO::PARAM_NULL);
			$permisson_sth->execute();
			$id = App::connection()->lastInsertId();
			if(!$id) {
				$permission_id_sth->bindValue(1, $key, \PDO::PARAM_STR);
				$id = (int)$permission_id_sth->fetchColumn(0);
			}
			foreach($perm->role as $role) {
				if(!self::get((string)$role)) continue;
				$role_sth->bindValue(1, $role, \PDO::PARAM_INT);
				$role_sth->bindValue(2, $id, \PDO::PARAM_INT);
				$role_sth->bindValue(3,
					(filter_var((string)$role->attributes()->protected, FILTER_VALIDATE_BOOLEAN) ? 'y' : 'n'),
					                 \PDO::PARAM_STR);
				$role_sth->execute();
				$role_sth->closeCursor();
			}
		}
		self::rebuildCache();
	}

	public static function rebuildCache($id = null)
	{
		App::cache()->delete(self::CACHE_KEY);
		if(!$id) {
			self::$roles = array();
		}
		$table = ac_table('roles');
		$query = "
		SELECT id,
		       _name,
		       _color,
		       _background,
		       _protected,
		       _editable,
		       _description
		FROM `$table`
		";
		if($id) {
			$query .= 'WHERE id = ' . (int)$id;
		}
		$res = App::connection()->query($query)->fetchAll(\PDO::FETCH_NUM);
		foreach($res as $row) {
			$role_id = (int)$row[0];
			if(!isset(self::$roles[$role_id])) {
				$role = new self;
			} else {
				$role = self::$roles[$role_id];
			}
			$role->id          = $role_id;
			$role->name        = $row[1];
			$role->protected   = ($row[4] === 'y');
			$role->editable    = ($row[5] === 'y');
			$role->description = $row[6];
			$role->permission  = array();
			if($row[2] !== null) $role->color = (int)$row[2];
			else $role->color = null;
			if($row[3] !== null) $role->background = (int)$row[3];
			else $role->background = null;
			self::$roles[$role_id] = $role;
		}
		$role_perm_tbl = ac_table('role_permissions');
		$perm_tbl      = ac_table('permissions');
		$query         = "
		SELECT p._permission permission,
		       rp._protected  protected,
		       r.id        	 id
		FROM `$role_perm_tbl` rp
		RIGHT JOIN `$table` r
		ON rp._role_id = r.id
		RIGHT JOIN `$perm_tbl` p
		ON rp._permission = p.id
		";
		if($id) {
			$query .= 'WHERE r.id = ' . (int)$id;
		}
		$res = App::connection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);
		foreach($res as $row) {
			if(isset(self::$roles[$row['id']])) {
				self::$roles[$row['id']]->permission[$row['permission']] = ($row['protected'] === 'y' ? 2 : 1);
			}
		}
		App::cache()->store(self::CACHE_KEY, self::$roles, self::CACHE_TTL);
	}
}
