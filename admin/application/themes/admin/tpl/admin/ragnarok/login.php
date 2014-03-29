<?php
use Aqua\UI\Dashboard;
use Aqua\Event\Event;
use Aqua\Ragnarok\Server\Login;
use Aqua\User\Role;
/**
 * @var $page   \Page\Admin\Ragnarok
 */
?>
<table class="ac-table">
	<thead>
		<tr class="alt">
			<td><?php echo __('ragnarok-server', 'name') ?></td>
			<td><?php echo __('ragnarok-server', 'base-exp') ?></td>
			<td><?php echo __('ragnarok-server', 'job-exp') ?></td>
			<td><?php echo __('ragnarok-server', 'characters') ?></td>
			<td><?php echo __('ragnarok-server', 'online') ?></td>
		</tr>
	</thead>
	<tbody>
	<?php if(!$page->server->charmapCount) : ?>
		<tr><td colspan="4" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td></tr>
	<?php else : foreach($page->server->charmap as $charmap) : ?>
		<tr>
			<td><a href="<?php echo ac_build_url(array(
				'path' => array( 'r', $charmap->server->key, $charmap->key )
			)) ?>"><?php echo htmlspecialchars($charmap->name) ?></a></td>
			<td><?php echo $charmap->getOption('rate.base-exp', 100) / 100 ?>x</td>
			<td><?php echo $charmap->getOption('rate.job-exp', 100) / 100 ?>x</td>
			<td><?php echo $charmap->fetchCache('char_count') ?></td>
			<td><?php echo $charmap->fetchCache('online') ?></td>
		</tr>
	<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="5">
				<a href="<?php echo ac_build_url(array(
					'path' => array( 'r', $page->server->key, 'server' )
				))?>">
				<button type="button" class="ac-button"><?php echo __('ragnarok-server', 'new-server') ?></button>
				</a>
			</td>
		</tr>
	</tfoot>
</table>