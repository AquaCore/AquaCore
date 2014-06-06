<?php
/**
 * @var $templates array
 * @var $templateCount int
 * @var $paginator \Aqua\UI\Pagination
 * @var $page \Page\Admin\Mail
 */

use Aqua\Plugin\Plugin;
?>
<table class="ac-table">
	<thead>
		<tr class="alt">
			<td><?php echo __('email', 'key') ?></td>
			<td><?php echo __('email', 'name') ?></td>
			<td><?php echo __('email', 'plugin') ?></td>
		</tr>
	</thead>
	<tbody>
	<?php if(empty($templates)) : ?>
		<tr><td colspan="3" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td></tr>
	<?php else : foreach($templates as $key => $data) : ?>
		<tr>
			<td><?php echo htmlspecialchars($key) ?></td>
			<td><a href="<?php echo ac_build_url(array(
					'path' => array( 'mail' ),
			        'action' => 'edit',
			        'arguments' => array( $key )
				)) ?>"><?php echo htmlspecialchars($data['name']) ?></a></td>
			<td><?php echo ($data['plugin_id'] && ($plugin = Plugin::get($data['plugin_id'])) ? htmlspecialchars($plugin->name) : '--') ?></td>
		</tr>
	<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
		<tr><td colspan="3"><?php echo $paginator->render() ?></td></tr>
	</tfoot>
</table>
<span class="ac-search-result"><?php echo __('application',
                                             'search-results-' . ($templateCount === 1 ? 's' : 'p'),
                                             number_format($templateCount)) ?></span>
