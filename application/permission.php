<?php
use Aqua\Core\App;
use Aqua\Core\User;
use Aqua\User\Account;
use Aqua\User\Role;
use Aqua\Permission\PermissionSet;
use Aqua\Permission\Permission;
use Aqua\Ragnarok\Server;
use Aqua\Ragnarok\Character;
use Aqua\Ragnarok\Account as RagnarokAccount;

$permissions = new PermissionSet;

$filterActiveAccount = function (User $user)
{
	return ($user->loggedIn() && $user->account->status === Account::STATUS_NORMAL);
};
$filterOwnedAccount = function (User $user)
{
	return ($user->loggedIn() && App::$activeRagnarokAccount instanceof RagnarokAccount &&
	        App::$activeRagnarokAccount->owner === $user->account->id);
};
$filterOwnedCharacter = function (User $user)
{
	return ($user->loggedIn() && App::$activeRagnarokCharacter instanceof Character &&
	        App::$activeRagnarokCharacter->account()->owner === $user->account->id);
};

$permissions
	->set('main')
	->allowAll();
$permissions
	->set('main/account')
	->order(Permission::ORDER_DENY_ALLOW | Permission::ORDER_ROLE_PERMISSION)
	->denyRole(Role::get(Role::ROLE_GUEST));
$permissions
	->set('main/account/action/[index|activate]')
	->allowAll();
$permissions
	->set('main/account/action/[login|register|recoverpw|resetpw|resetcode]')
	->order(Permission::ORDER_ALLOW_DENY | Permission::ORDER_ROLE_PERMISSION)
	->allowRole(Role::get(Role::ROLE_GUEST));
$permissions
	->set('main/donation/action/[history|transfer|transfer_history]')
	->order(Permission::ORDER_DENY_ALLOW | Permission::ORDER_ROLE_PERMISSION)
	->denyRole(Role::get(Role::ROLE_GUEST));
$permissions
	->set('main/content/action/preview')
	->order(Permission::ORDER_ALLOW_DENY | Permission::ORDER_PERMISSION_ROLE)
	->allowPermission('view-admin-cp');
if(Server::$serverCount === 0) {
	$permissions
		->set("main/ragnarok")
		->order(Permission::ORDER_DENY_ALLOW | Permission::ORDER_ROLE_PERMISSION)
		->denyAll();
} else {
	$permissions
		->set("main/ragnarok/action/[register|link|index]")
		->order(Permission::ORDER_DENY_ALLOW | Permission::ORDER_PERMISSION_ROLE)
		->allowPermission('register-account')
		->addFilter('check_status_active', $filterActiveAccount);
	$permissions
		->set("main/ragnarok/account")
		->order(Permission::ORDER_DENY_ALLOW | Permission::ORDER_ROLE_PERMISSION)
		->addFilter('check_owns_account', $filterOwnedAccount);
	$permissions
		->set("main/ragnarok/server/char")
		->order(Permission::ORDER_DENY_ALLOW | Permission::ORDER_ROLE_PERMISSION)
		->addFilter('check_owns_account', $filterOwnedCharacter);
	$permissions
		->set("main/ragnarok/server/item/action/[cart|buy]")
		->order(Permission::ORDER_DENY_ALLOW | Permission::ORDER_ROLE_PERMISSION)
		->denyRole(Role::get(Role::ROLE_GUEST));
}

return $permissions;
