<?php
use Aqua\Core\App;

/**
 * @var \Aqua\Log\ErrorLog[] $errors
 * @var int                 $error_count
 * @var \Page\Admin\Log     $page
 * @var \Aqua\UI\Pagination $paginator
 */

$date_format = App::settings()->get('datetime_format', '');
?>
<table class="ac-table">
	<thead>
		<tr class="alt">
			<td><?php echo __('error', 'id')?></td>
			<td><?php echo __('error', 'class')?></td>
			<td><?php echo __('error', 'code')?></td>
			<td><?php echo __('error', 'ip-address')?></td>
			<td><?php echo __('error', 'url')?></td>
			<td><?php echo __('error', 'date')?></td>
		</tr>
	</thead>
	<tbody>
	<?php if(empty($errors)) : ?>
		<tr><td colspan="6" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td></tr>
	<?php else : foreach($errors as $error) : ?>
		<tr>
			<td style="width: 70px"><a href="<?php echo ac_build_url(array(
						'path'       => array( 'log' ),
						'action'     => 'viewerror',
						'arguments'  => array( $error->id )
					)) ?>"><?php echo $error->id ?></a></td>
			<td><?php echo htmlspecialchars($error->type) ?></td>
			<td><?php echo htmlspecialchars($error->code) ?></td>
			<td><?php echo htmlspecialchars($error->ipAddress) ?></td>
			<td><a href="<?php echo $error->url ?>" target="_blank"><?php echo htmlspecialchars($error->url) ?></a></td>
			<td><?php echo $error->date($date_format)?></td>
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
<span class="ac-search-result"><?php echo __('application', 'search-results-' . ($error_count === 1 ? 's' : 'p'), number_format($error_count)) ?></span>
