<?php
/**
 * @var $account           \Aqua\User\Account
 * @var $ragnarok_accounts \Aqua\Ragnarok\Account[]
 * @var $profile_history   \Aqua\Log\ProfileUpdateLog[]
 * @var $donation_history  \Aqua\Log\PayPalLog[]
 * @var $page              \Page\Admin\User
 */

use Aqua\Core\App;
use Aqua\Core\L10n;
use Aqua\User\Role;
use Aqua\User\Account;
use Aqua\UI\ScriptManager;

$datetime_format = App::settings()->get('datetime_format', '');
$roles = array();
foreach(Role::$roles as $id => $role) {
	$roles[$id] = $role->name;
}
$page->theme
	->addWordGroup('profile', array(
		'ban',
		'unban',
		'ban-account',
		'unban-account',
		'ban-accounts',
		'unban-accounts',
		'edit-account-admin',
		'edit-account'
	))
	->addSettings('baseUrl', \Aqua\URL)
	->addSettings('defaultAvatar', \Aqua\URL . '/uploads/avatar/avatar.png')
	->addSettings('bannedStatusID', array(
		Account::STATUS_BANNED,
		Account::STATUS_SUSPENDED
	))
	->addSettings('accountInfo', array(
		'displayName' => $account->displayName,
		'status'      => $account->status,
		'banned'      => $account->isBanned()
	));
$page->theme->footer->enqueueScript(ScriptManager::script('aquacore.ajax-form'));
$page->theme->footer->enqueueScript('theme.form-functions')
	->type('text/javascript')
	->src($page->theme->url . '/scripts/ajax-form-functions.js');
$page->theme->footer->enqueueScript('theme.view-user')
	->type('text/javascript')
	->src($page->theme->url . '/scripts/view-user.js');
$edit_profile_url = ac_build_url(array(
		'path'      => array( 'user' ),
		'action'    => 'edit',
		'arguments' => array( $account->id )
	));
$ban_user_url = ac_build_url(array(
		'path'      => array( 'user' ),
		'action'    => 'ban',
		'arguments' => array( $account->id )
	));
?>
<table class="ac-table ac-account-info-table">
	<thead>
		<tr>
			<td colspan="6" style="text-align: left">
				<?php echo __('profile', 'account-info', htmlspecialchars($account->username)) ?>
			</td>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td rowspan="6" class="ac-account-avatar">
				<img src="<?php echo $account->avatar() ?>" ac-field="avatar">
			</td>
		</tr>
		<tr>
			<td class="ac-account-info-title"><?php echo __('profile', 'id') ?></td>
			<td><?php echo $account->id ?></td>
			<td class="ac-account-info-title"><?php echo __('profile', 'username') ?></td>
			<td colspan="2" ac-field="username"><?php echo htmlspecialchars($account->username) ?></td>
		</tr>
		<tr>
			<td class="ac-account-info-title"><?php echo __('profile', 'display-name') ?></td>
			<td ac-field="display"><?php echo $account->display() ?></td>
			<td class="ac-account-info-title"><?php echo __('profile', 'email') ?></td>
			<td colspan="2" ac-field="email"><?php echo htmlspecialchars($account->email) ?></td>
		</tr>
		<tr>
			<td class="ac-account-info-title"><?php echo __('profile', 'role') ?></td>
			<td ac-field="role_name"><?php echo htmlspecialchars($account->role()->name) ?></td>
			<td class="ac-account-info-title"><?php echo __('profile', 'status') ?></td>
			<td colspan="2" class="ac-account-status"><?php echo $account->status() ?></td>
		</tr>
		<tr>
			<td class="ac-account-info-title"><?php echo __('profile', 'registration-date') ?></td>
			<td><?php echo $account->registrationDate($datetime_format) ?></td>
			<td class="ac-account-info-title"><?php echo __('profile', 'birthday') ?></td>
			<td colspan="2" ac-field="formatted_birthday">
				<?php echo $account->birthDate(App::settings()->get('date_format', '')) ?>
			</td>
		</tr>
		<tr>
			<td class="ac-account-info-title"><?php echo __('donation', 'credits') ?></td>
			<td>
				<?php echo __('donation',
				              'credit-points',
				              '<span ac-field="credits">' . number_format($account->credits) . '</span>') ?>
			</td>
			<td class="ac-account-info-title">
				<span class="ac-ban-field ac-unban-date-label" <?php if(!$account->unbanDate) echo 'style="display: none"'; ?>>
					<?php echo __('account', 'unban-date') ?>
				</span>
			</td>
			<td colspan="2">
				<span class="ac-ban-field ac-unban-date">
					<?php
					if($account->unbanDate) {
						echo $account->unbanDate(App::settings()->get('datetime_format', ''));
					}
					?>
				</span>
			</td>
		</tr>
		<tr class="ac-table-header">
			<td colspan="6" style="text-align: left"><?php echo __('profile', 'ragnarok-accounts') ?></td>
		</tr>
		<tr class="ac-table-header alt">
			<td><?php echo __('ragnarok-account', 'username') ?></td>
			<td><?php echo __('ragnarok-account', 'sex') ?></td>
			<td><?php echo __('ragnarok-account', 'group') ?></td>
			<td><?php echo __('ragnarok-account', 'state') ?></td>
			<td><?php echo __('ragnarok-account', 'last-login') ?></td>
			<td><?php echo __('ragnarok-account', 'server') ?></td>
		</tr>
	<?php if(empty($ragnarok_accounts)) : ?>
		<tr>
			<td colspan="6" class="ac-table-no-result">
				<?php echo __('application', 'no-search-results') ?>
			</td>
		</tr>
	<?php else : foreach($ragnarok_accounts as $acc) : ?>
		<tr>
			<td><a href="<?php echo ac_build_url(array(
				                                     'path'      => array( 'ro', $acc->server->key ),
				                                     'action'    => 'view_account',
				                                     'arguments' => array( $acc->id )
			                                     )) ?>"><?php echo htmlspecialchars($acc->username) ?></a></td>
			<td><?php echo $acc->gender() ?></td>
			<td><?php echo $acc->groupName() ?>
				<small>(<?php echo $acc->groupId ?>)</small>
			</td>
			<td><?php echo $acc->state() ?></td>
			<td><?php echo $acc->lastLogin($datetime_format) ?></td>
			<td><a href="<?php echo ac_build_url(array(
				                                     'path' => array( 'ro', $acc->server->key )
			                                     )) ?>"><?php echo htmlspecialchars($acc->server->name) ?></a></td>
		</tr>
<?php endforeach; endif; ?>
<?php if(App::user()->role()->hasPermission('view-cp-logs')) : ?>
	<tr class="ac-table-header">
		<td colspan="6" style="text-align: left">
			<?php echo __('profile-history', 'profile-history') ?>
			<a style="float: right"
			   href="<?php echo ac_build_url(array(
				                                 'path'      => array( 'user' ),
				                                 'action'    => 'profile_history',
				                                 'arguments' => array( $account->id )
			                                 )) ?>"><?php echo __('application', 'more') ?></a>
		</td>
	</tr>
	<tr class="ac-table-header alt">
		<td><?php echo __('profile-history', 'id') ?></td>
		<td><?php echo __('profile-history', 'ip-address') ?></td>
		<td><?php echo __('profile-history', 'field') ?></td>
		<td><?php echo __('profile-history', 'old-value') ?></td>
		<td><?php echo __('profile-history', 'new-value') ?></td>
		<td><?php echo __('profile-history', 'date') ?></td>
	</tr>
	<?php if(empty($profile_history)) : ?>
		<tr>
			<td colspan="6" class="ac-table-no-result">
				<?php echo __('application', 'no-search-results') ?>
			</td>
		</tr>
	<?php else : foreach($profile_history as $log) : ?>
		<tr>
			<td>#<?php echo $log->id ?></td>
			<td><?php echo $log->ipAddress ?></td>
			<td><?php echo $log->field() ?></td>
			<td><?php echo htmlspecialchars($log->oldValue) ?></td>
			<td><?php echo htmlspecialchars($log->newValue) ?></td>
			<td><?php echo $log->date($datetime_format) ?></td>
		</tr>
	<?php endforeach; endif; ?>
	<tr class="ac-table-header">
		<td colspan="6" style="text-align: left">
			<?php echo __('donation', 'donation-history') ?>
			<a style="float: right"
			   href="<?php echo ac_build_url(array(
				                                 'path'      => array( 'user' ),
				                                 'action'    => 'donation_history',
				                                 'arguments' => array( $account->id )
			                                 )) ?>"><?php echo __('application', 'more') ?></a>
		</td>
	</tr>
	<tr class="ac-table-header alt">
		<td><?php echo __('donation', 'deposited') ?></td>
		<td><?php echo __('donation', 'gross') ?></td>
		<td><?php echo __('donation', 'credits') ?></td>
		<td><?php echo __('donation', 'txn-type') ?></td>
		<td><?php echo __('donation', 'process-date') ?></td>
		<td><?php echo __('donation', 'payment-date') ?></td>
	</tr>
	<?php if(empty($donation_history)) : ?>
		<tr>
			<td colspan="6" class="ac-table-no-result">
				<?php echo __('application', 'no-search-results') ?>
			</td>
		</tr>
	<?php else : foreach($donation_history as $log) : ?>
		<tr>
			<td><?php echo number_format($log->deposited, 2) ?>
				<small><?php echo $log->currency ?></small>
			</td>
			<td><?php echo number_format($log->gross, 2) ?>
				<small><?php echo $log->currency ?></small>
			</td>
			<td><?php echo __('donation', 'credit-points', number_format($log->credits)) ?></td>
			<td><?php echo $log->transactionType() ?></td>
			<td><?php echo $log->processDate($datetime_format) ?></td>
			<td><?php echo $log->paymentDate($datetime_format) ?></td>
		</tr>
	<?php endforeach; endif; endif; ?>
	</tbody>
	<tfoot>
	<tr>
		<td colspan="6">
			<?php if(App::user()->role()->hasPermission('edit-cp-user')) : ?>
				<div class="ac-settings ac-edit-user ac-script" style="display: none">
					<form method="POST" enctype="multipart/form-data" action="<?php echo $edit_profile_url ?>"
					      id="edit-user">
						<table>
							<tr class="ac-form-warning">
								<td colspan="2">
									<div></div>
								</td>
							</tr>
							<tr>
								<td style="text-align: center">
									<div class="ac-account-avatar ac-delete-wrapper">
										<img src="<?php echo $account->avatar() ?>" ac-field="avatar">
										<input type="submit" class="ac-delete-button" name="x-delete-avatar"
										       value="" <?php if(!$account->avatar) {
											echo 'style="display:none"';
										} ?>>
									</div>
								</td>
								<td class="ac-form-tag">
									<input type="radio" name="avatar_type" value="image" id="ac-edit-use-avatar"
									       checked="checked">
									<label for="ac-edit-use-avatar" style="font-size: .85em">
										<?php echo __('profile', 'use-custom-pic') ?>
									</label>
									<input type="file" name="image" accept="image/gif, image/png, image/jpeg">
									<hr>
									<input type="radio" name="avatar_type" value="gravatar" id="ac-edit-use-gravatar">
									<label for="ac-edit-use-gravatar" style="font-size: .85em">
										<?php echo __('profile', 'use-gravatar') ?>
									</label>
									<input type="text" name="gravatar">
								</td>
							</tr>
							<tr class="ac-form-warning">
								<td colspan="2">
									<div></div>
								</td>
							</tr>
							<tr class="ac-form-field">
								<td class="ac-form-label"><?php echo __('profile', 'username') ?></td>
								<td class="ac-form-tag"><input type="text" name="username"
								                               value="<?php echo htmlspecialchars($account->username) ?>">
								</td>
							</tr>
							<tr class="ac-form-warning">
								<td colspan="2">
									<div></div>
								</td>
							</tr>
							<tr class="ac-form-field">
								<td class="ac-form-label"><?php echo __('profile', 'display-name') ?></td>
								<td class="ac-form-tag"><input type="text" name="display_name"
								                               value="<?php echo htmlspecialchars($account->displayName) ?>">
								</td>
							</tr>
							<tr class="ac-form-warning">
								<td colspan="2">
									<div></div>
								</td>
							</tr>
							<tr class="ac-form-field">
								<td class="ac-form-label"><?php echo __('profile', 'email') ?></td>
								<td class="ac-form-tag"><input type="email" name="email"
								                               value="<?php echo htmlspecialchars($account->email) ?>"></td>
							</tr>
							<tr class="ac-form-warning">
								<td colspan="2">
									<div></div>
								</td>
							</tr>
							<tr class="ac-form-field">
								<td class="ac-form-label"><?php echo __('profile', 'birthday') ?></td>
								<td class="ac-form-tag"><input type="date" name="birthday"
								                               value="<?php echo date('Y-m-d', $account->birthDate) ?>">
								</td>
							</tr>
							<?php if($account->id !== 1) : ?>
								<tr class="ac-form-warning">
									<td colspan="2">
										<div></div>
									</td>
								</tr>
								<tr class="ac-form-field">
									<td class="ac-form-label"><?php echo __('profile', 'role') ?></td>
									<td class="ac-form-tag">
										<select name="role">
											<?php foreach(Role::$roles as $id => $role) : if($id !== Role::ROLE_GUEST) : ?>
												<option value="<?php echo $id ?>" <?php if($account->roleId === $id) {
													echo "selected=\"selected\"";
												} ?>>
													<?php echo htmlspecialchars($role->name) ?>
												</option>
											<?php endif; endforeach; ?>
										</select>
									</td>
								</tr>
							<?php endif; ?>
							<tr class="ac-form-warning">
								<td colspan="2">
									<div></div>
								</td>
							</tr>
							<tr class="ac-form-field">
								<td class="ac-form-label"><?php echo __('donation', 'credits') ?></td>
								<td class="ac-form-tag"><input type="number" name="credits" min="0"
								                               value="<?php echo $account->credits ?>"></td>
							</tr>
							<?php if($account->id !== 1 || App::user()->account->id === 1) : ?>
								<tr class="ac-form-warning">
									<td colspan="2">
										<div></div>
									</td>
								</tr>
								<tr class="ac-form-field">
									<td class="ac-form-label"><?php echo __('profile', 'password') ?></td>
									<td class="ac-form-tag"><input type="password" name="password"></td>
								</tr>
							<?php endif; ?>
							<tr class="ac-form-field">
								<td class="ac-form-tag" colspan="2" style="text-align: right;">
									<div class="ac-form-response"></div>
									<input type="submit"
									       value="<?php echo __('application', 'submit') ?>"
									       ac-default-submit>
								</td>
							</tr>
						</table>
					</form>
				</div>
				<a href="<?php echo $edit_profile_url ?>">
					<button type="button" class="ac-button ac-edit-user-button">
						<?php echo __('profile', 'edit-account') ?>
					</button>
				</a>
			<?php endif; ?>
			<?php if($account->id !== 1 && App::user()->role()->hasPermission('ban-cp-user')) : ?>
				<div class="ac-settings ac-ban-user" style="display: none">
					<form method="POST" action="<?php echo $ban_user_url ?>" id="ban-user">
						<table>
							<tr class="ac-form-warning ac-ban-ro-accounts">
								<td colspan="2"></td>
							</tr>
							<tr class="ac-form-field ac-ban-ro-accounts">
								<td class="ac-form-label"><?php echo __('profile', $account->isBanned() ? 'unban-accounts' : 'ban-accounts') ?></td>
								<td class="ac-form-tag"><input type="checkbox" name="ban_accounts" value="1"></td>
							</tr>
							<tr class="ac-form-warning ac-ban-field" <?php if($account->isBanned()) echo 'style="display: none"' ?>>
								<td colspan="2"></td>
							</tr>
							<tr class="ac-form-field ac-ban-field" <?php if($account->isBanned()) echo 'style="display: none"' ?>>
								<td class="ac-form-label"><?php echo __('profile', 'unban-date') ?></td>
								<td class="ac-form-tag"><input type="text" name="unban_time" placeholder="YYYY-MM-DD hh:mm:ss"></td>
							</tr>
							<tr class="ac-form-description ac-ban-field" <?php if($account->isBanned()) echo 'style="display: none"' ?>>
								<td colspan="2"><?php echo __('profile', 'unban-time-desc') ?></td>
							</tr>
							<tr class="ac-form-warning">
								<td colspan="2"></td>
							</tr>
							<tr class="ac-form-field">
								<td class="ac-form-tag" colspan="2">
									<textarea rows="6"
											 name="reason"
											 placeholder="<?php echo __('profile', ($account->isBanned() ? 'unban-reason' : 'ban-reason')) ?>"
									></textarea>
								</td>
							</tr>
							<tr class="ac-form-field">
								<td class="ac-form-tag" colspan="2" style="text-align: right;">
									<div class="ac-form-response"></div>
									<input type="submit" value="<?php echo __('application', 'submit') ?>">
								</td>
							</tr>
						</table>
					</form>
				</div>
				<a href="<?php echo $ban_user_url ?>">
					<button type="button" class="ac-button ac-ban-user-button" style="margin-right: 15px">
						<?php echo __('profile', ($account->isBanned() ? 'unban' : 'ban')); ?>
					</button>
				</a>
			<?php endif; ?>
		</td>
	</tr>
	</tfoot>
</table>
