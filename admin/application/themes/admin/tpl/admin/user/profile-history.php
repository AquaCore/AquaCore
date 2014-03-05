<?php
use Aqua\Core\App;
/**
 * @var $account     \Aqua\User\Account
 * @var $history     \Aqua\Log\ProfileUpdateLog[]
 * @var $recordCount int
 * @var $paginator   \Aqua\UI\Pagination
 * @var $page        \Page\Admin\User
 */
$datetime_format = App::settings()->get('datetime_format');
?>
<table class="ac-table">
	<thead>
		<tr class="alt">
			<td><?php echo __('profile-history', 'id') ?></td>
			<td><?php echo __('profile-history', 'user') ?></td>
			<td><?php echo __('profile-history', 'ip-address') ?></td>
			<td><?php echo __('profile-history', 'field') ?></td>
			<td><?php echo __('profile-history', 'old-value') ?></td>
			<td><?php echo __('profile-history', 'new-value') ?></td>
			<td><?php echo __('profile-history', 'date') ?></td>
		</tr>
	</thead>
	<tbody>
	<?php if(empty($history)) : ?>
	<tr>
		<td colspan="7" class="ac-table-no-result">
			<?php echo __('application', 'no-search-results') ?>
		</td>
	</tr>
	<?php else : foreach($history as $r) : ?>
	<tr>
		<td><?php echo $r->id ?></td>
		<td><a href="<?php echo ac_build_url(array(
					'path' => array( 'user' ),
					'action' => 'view',
					'arguments' => array( $r->userId )
				)) ?>"><?php echo $r->account()->display() ?></a>
		</td>
		<td><?php echo $r->ipAddress ?></td>
		<td><?php echo $r->field() ?></td>
		<td><?php echo $r->type === 'password' ? '--' : htmlspecialchars($r->oldValue) ?></td>
		<td><?php echo $r->type === 'password' ? '--' : htmlspecialchars($r->newValue) ?></td>
		<td><?php echo $r->date($datetime_format) ?></td>
	</tr>
	<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="7">
				<div style="position: relative;">
					<?php echo $paginator->render() ?>
				</div>
			</td>
		</tr>
	</tfoot>
</table>
<span class="ac-search-result"><?php echo __('application',
                                             'search-results-' . ($recordCount === 1 ? 's' : 'p'),
                                             number_format($recordCount)) ?></span>
