<?php
use Aqua\Permission\PermissionSet;
use Aqua\User\Role;
use Aqua\Permission\Permission;
use Aqua\Ragnarok\Ragnarok;
use Aqua\Core\User;

$permissions = new PermissionSet;

$permissions
	->set('admin')
	->order(Permission::ORDER_ALLOW_DENY | Permission::ORDER_PERMISSION_ROLE)
	->allowPermission('view-admin-cp');
$permissions
	->set('admin/news')
	->order(Permission::ORDER_ALLOW_DENY | Permission::ORDER_PERMISSION_ROLE)
	->allowPermission('view-admin-cp')
	->allowPermission('publish-posts');
$permissions
	->set('admin/user/action/edit')
	->order(Permission::ORDER_ALLOW_DENY | Permission::ORDER_PERMISSION_ROLE)
	->allowPermission('view-admin-cp')
	->allowPermission('edit-cp-user');
$permissions
	->set('admin/user/action/ban')
	->order(Permission::ORDER_ALLOW_DENY | Permission::ORDER_PERMISSION_ROLE)
	->allowPermission('view-admin-cp')
	->allowPermission('ban-cp-user');
$permissions
	->set('admin/role')
	->order(Permission::ORDER_ALLOW_DENY | Permission::ORDER_PERMISSION_ROLE)
	->allowPermission('view-admin-cp')
	->allowPermission('manage-roles');
$permissions
	->set('admin/settings')
	->order(Permission::ORDER_ALLOW_DENY | Permission::ORDER_PERMISSION_ROLE)
	->allowPermission('view-admin-cp')
	->allowPermission('edit-cp-settings');
$permissions
	->set('admin/ragnarok/action/[edit|index]')
	->order(Permission::ORDER_ALLOW_DENY | Permission::ORDER_PERMISSION_ROLE)
	->allowPermission('view-admin-cp')
	->allowPermission('edit-cp-settings');
$permissions
	->set('admin/ragnarok/action/[login_log|ban_log|password_log]')
	->order(Permission::ORDER_ALLOW_DENY | Permission::ORDER_PERMISSION_ROLE)
	->allowPermission('view-admin-cp')
	->allowPermission('edit-cp-settings');

return $permissions;
