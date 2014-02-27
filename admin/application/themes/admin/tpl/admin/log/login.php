<?php
use Aqua\Core\App;
use Aqua\Log\LoginLog;

/**
 * @var \Aqua\Log\LoginLog[] $login
 * @var int                  $login_count
 * @var \Page\Admin\Log      $page
 * @var \Aqua\UI\Pagination  $paginator
 */

$date_format = App::settings()->get('datetime_format', '');
?>
<table class="ac-table">
	<thead>
	<tr class="alt">
		<td><?php echo __('login-log', 'username')?></td>
		<td><?php echo __('login-log', 'logged-as')?></td>
		<td><?php echo __('login-log', 'ip-address')?></td>
		<td><?php echo __('login-log', 'type')?></td>
		<td><?php echo __('login-log', 'status')?></td>
		<td><?php echo __('login-log', 'date')?></td>
	</tr>
	</thead>
	<tbody>
	<?php if(empty($login)) : ?>
		<tr><td colspan="6" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td></tr>
	<?php else : foreach($login as $l) : ?>
		<tr>
			<td><?php echo ($l->username && $l->loginType === LoginLog::TYPE_NORMAL ? htmlspecialchars($l->username) : '--') ?></td>
			<?php if($l->userId) : ?>
				<td>
					<a href="<?php echo ac_build_url(array(
							'path'      => array( 'user' ),
							'action'    => 'view',
							'arguments' => array( $l->userId )
						)) ?>">
						<?php echo $l->account()->display() ?>
					</a>
				</td>
			<?php else : ?>
				<td>--</td>
			<?php endif; ?>
			<td><?php echo htmlspecialchars($l->ipAddress) ?></td>
			<td><?php echo $l->loginType() ?></td>
			<td class="ac-login-status ac-login-<?php echo ($l->status === LoginLog::STATUS_OK ? 'ok' : 'fail') ?>"><?php echo $l->status() ?></td>
			<td><?php echo $l->date($date_format)?></td>
		</tr>
	<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
	<tr>
		<td colspan="6" style="text-align: center">
			<?php echo $paginator->render()?>
		</td>
	</tr>
	</tfoot>
</table>
<span class="ac-search-result"><?php echo __('application', 'search-results-' . ($login_count === 1 ? 's' : 'p'), number_format($login_count)) ?></span>
