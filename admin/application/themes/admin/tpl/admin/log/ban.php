<?php
use Aqua\Core\App;
use Aqua\Log\BanLog;

/**
 * @var \Aqua\Log\BanLog[]  $ban
 * @var int                 $ban_count
 * @var \Page\Admin\Log     $page
 * @var \Aqua\UI\Pagination $paginator
 */

$date_format = App::settings()->get('datetime_format', '');
?>
<table class="ac-table">
	<thead>
	<tr class="alt">
		<td><?php echo __('profile', 'id')?></td>
		<td><?php echo __('profile', 'user')?></td>
		<td><?php echo __('profile', 'banned-by')?></td>
		<td><?php echo __('profile', 'ban-type')?></td>
		<td><?php echo __('profile', 'ban-date')?></td>
		<td><?php echo __('profile', 'unban-date')?></td>
	</tr>
	</thead>
	<tbody>
	<?php if(empty($ban)) : ?>
		<tr><td colspan="6" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td></tr>
	<?php else : foreach($ban as $b) : ?>
		<tr>
			<td style="width: 100px"><?php echo $b->id ?></td>
			<td>
				<a href="<?php echo ac_build_url(array(
						'path' => array( 'user' ),
						'action' => 'view',
						'arguments' => array( $b->bannedId )
					)) ?>">
					<?php echo $b->bannedAccount()->display() ?>
					</a>
			</td>
			<td>
				<a href="<?php echo ac_build_url(array(
						'path' => array( 'user' ),
						'action' => 'view',
						'arguments' => array( $b->userId )
					)) ?>">
					<?php echo $b->account()->display() ?>
					</a>
			</td>
			<td><?php echo $b->type() ?></td>
			<td><?php echo $b->banDate($date_format) ?></td>
			<td><?php echo ($b->unbanDate ? $b->unbanDate($date_format) : '--'); ?></td>
		</tr>
		<?php if(!empty($b->reason)) : ?>
		<tr>
			<td style="text-align: justify"><b><?php echo __('profile', ($b->type === BanLog::TYPE_UNBAN ? 'unban-' : 'ban-') . 'reason') ?>:</b></td>
			<td colspan="5" class="ac-ban-reason" style="text-align: justify"><?php echo htmlspecialchars($b->reason) ?></td>
		</tr>
		<?php endif; ?>
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
<span class="ac-search-result"><?php echo __('application', 'search-results-' . ($ban_count === 1 ? 's' : 'p'), number_format($ban_count)) ?></span>
