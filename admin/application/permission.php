<?php
use Aqua\Core\App;
use Aqua\Core\User;
use Aqua\Content\ContentType;
use Aqua\Permission\PermissionSet;
use Aqua\Permission\Permission;

$permissions = new PermissionSet;

$filterContentType = function (User $user) {
	return (App::$activeContentType instanceof ContentType && (!App::$activeContentType->permission ||
	        $user->role()->hasPermission(App::$activeContentType->permission)));
};

$permissions
	->set('admin')
	->order(Permission::ORDER_ALLOW_DENY | Permission::ORDER_PERMISSION_ROLE)
	->allowPermission('view-admin-cp');
$permissions
	->set('admin/content')
	->order(Permission::ORDER_ALLOW_DENY | Permission::ORDER_PERMISSION_ROLE)
	->allowPermission('view-admin-cp')
	->addFilter('can_edit_ctype', $filterContentType);
$permissions
	->set('admin/content/comments')
	->order(Permission::ORDER_ALLOW_DENY | Permission::ORDER_PERMISSION_ROLE)
	->allowPermission('view-admin-cp', 'edit-comments');
$permissions
	->set('admin/user/action/edit')
	->order(Permission::ORDER_ALLOW_DENY | Permission::ORDER_PERMISSION_ROLE)
	->allowPermission('view-admin-cp', 'edit-cp-user');
$permissions
	->set('admin/user/action/ban')
	->order(Permission::ORDER_ALLOW_DENY | Permission::ORDER_PERMISSION_ROLE)
	->allowPermission('view-admin-cp', 'ban-cp-user');
$permissions
	->set('admin/log')
	->order(Permission::ORDER_ALLOW_DENY | Permission::ORDER_PERMISSION_ROLE)
	->allowPermission('view-admin-cp', 'view-cp-logs');
$permissions
	->set('admin/role')
	->order(Permission::ORDER_ALLOW_DENY | Permission::ORDER_PERMISSION_ROLE)
	->allowPermission('view-admin-cp', 'manage-roles');
$permissions
	->set('admin/plugin')
	->order(Permission::ORDER_ALLOW_DENY | Permission::ORDER_PERMISSION_ROLE)
	->allowPermission('view-admin-cp', 'manage-plugins');
$permissions
	->set('admin/task')
	->order(Permission::ORDER_ALLOW_DENY | Permission::ORDER_PERMISSION_ROLE)
	->allowPermission('view-admin-cp', 'manage-tasks');
$permissions
	->set('admin/settings')
	->order(Permission::ORDER_ALLOW_DENY | Permission::ORDER_PERMISSION_ROLE)
	->allowPermission('view-admin-cp', 'edit-cp-settings');
$permissions
	->set('admin/bbcode')
	->order(Permission::ORDER_ALLOW_DENY | Permission::ORDER_PERMISSION_ROLE)
	->allowPermission('view-admin-cp', 'edit-cp-settings');
$permissions
	->set('admin/mail')
	->order(Permission::ORDER_ALLOW_DENY | Permission::ORDER_PERMISSION_ROLE)
	->allowPermission('view-admin-cp', 'edit-cp-settings');
$permissions
	->set('admin/ragnarok/action/settings')
	->order(Permission::ORDER_ALLOW_DENY | Permission::ORDER_PERMISSION_ROLE)
	->allowPermission('view-admin-cp', 'edit-server-settings');
$permissions
	->set('admin/ragnarok/server/action/[settings|rates|woe|schedule|category|editcategory|shop|item]')
	->order(Permission::ORDER_ALLOW_DENY | Permission::ORDER_PERMISSION_ROLE)
	->allowPermission('view-admin-cp', 'edit-server-settings');
$permissions
	->set('admin/ragnarok/action/[account|viewaccount]')
	->order(Permission::ORDER_ALLOW_DENY | Permission::ORDER_PERMISSION_ROLE)
	->allowPermission('view-admin-cp', 'view-server-acc')
	->allowPermission('view-admin-cp', 'edit-server-user')
	->allowPermission('view-admin-cp', 'ban-server-user');
$permissions
	->set('admin/ragnarok/action/editaccount')
	->order(Permission::ORDER_ALLOW_DENY | Permission::ORDER_PERMISSION_ROLE)
	->allowPermission('view-admin-cp', 'edit-server-user');
$permissions
	->set('admin/ragnarok/action/banaccount')
	->order(Permission::ORDER_ALLOW_DENY | Permission::ORDER_PERMISSION_ROLE)
	->allowPermission('view-admin-cp', 'ban-server-user');
$permissions
	->set('admin/ragnarok/action/[loginlog|banlog|pwlog]')
	->order(Permission::ORDER_ALLOW_DENY | Permission::ORDER_PERMISSION_ROLE)
	->allowPermission('view-admin-cp', 'view-server-logs');
$permissions
	->set('admin/ragnarok/server/action/[zenylog|shoplog|picklog|mvplog|chatlog|npclog|atcmdlog|viewshoplog]')
	->order(Permission::ORDER_ALLOW_DENY | Permission::ORDER_PERMISSION_ROLE)
	->allowPermission('view-admin-cp', 'view-server-logs');
$permissions
	->set('admin/ragnarok/server/action/[cart|inventory|gstorage]')
	->order(Permission::ORDER_ALLOW_DENY | Permission::ORDER_PERMISSION_ROLE)
	->allowPermission('view-admin-cp', 'view-user-items');
$permissions
	->set('admin/ragnarok/action/storage')
	->order(Permission::ORDER_ALLOW_DENY | Permission::ORDER_PERMISSION_ROLE)
	->allowPermission('view-admin-cp', 'view-user-items');

return $permissions;
