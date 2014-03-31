<?php
use Aqua\Permission\PermissionSet;
use Aqua\Permission\Permission;

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
	->set('admin/ragnarok/action/settings')
	->order(Permission::ORDER_ALLOW_DENY | Permission::ORDER_PERMISSION_ROLE)
	->allowPermission('view-admin-cp')
	->allowPermission('edit-server-settings');
$permissions
	->set('admin/ragnarok/server/action/settings')
	->order(Permission::ORDER_ALLOW_DENY | Permission::ORDER_PERMISSION_ROLE)
	->allowPermission('view-admin-cp')
	->allowPermission('edit-server-settings');
$permissions
	->set('admin/ragnarok/action/[account|viewaccount]')
	->order(Permission::ORDER_ALLOW_DENY | Permission::ORDER_PERMISSION_ROLE)
	->allowPermission('view-admin-cp')
	->allowPermission('view-server-acc');
$permissions
	->set('admin/ragnarok/action/[account|viewaccount|editaccount]')
	->order(Permission::ORDER_ALLOW_DENY | Permission::ORDER_PERMISSION_ROLE)
	->allowPermission('view-admin-cp')
	->allowPermission('edit-server-user');
$permissions
	->set('admin/ragnarok/action/[account|viewaccount|banaccount]')
	->order(Permission::ORDER_ALLOW_DENY | Permission::ORDER_PERMISSION_ROLE)
	->allowPermission('view-admin-cp')
	->allowPermission('ban-server-user');
$permissions
	->set('admin/ragnarok/action/[loginlog|banlog|pwlog]')
	->order(Permission::ORDER_ALLOW_DENY | Permission::ORDER_PERMISSION_ROLE)
	->allowPermission('view-admin-cp')
	->allowPermission('view-server-logs');
$permissions
	->set('admin/ragnarok/server/action/[zenylog|shoplog|picklog|mvplog|chatlog|npclog|atcmdlog|viewshoplog]')
	->order(Permission::ORDER_ALLOW_DENY | Permission::ORDER_PERMISSION_ROLE)
	->allowPermission('view-admin-cp')
	->allowPermission('view-server-logs');

return $permissions;
