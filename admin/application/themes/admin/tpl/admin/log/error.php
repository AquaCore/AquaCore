<?php
/**
 * @var \Aqua\Log\ErrorLog[] $errors
 * @var int                 $errorCount
 * @var \Page\Admin\Log     $page
 * @var \Aqua\UI\Search     $search
 * @var \Aqua\UI\Pagination $paginator
 */

use Aqua\Core\App;
use Aqua\UI\ScriptManager;

$page->theme->footer->enqueueScript(ScriptManager::script('aquacore.build-url'));
$datetimeFormat = App::settings()->get('datetime_format', '');
?>
<table class="ac-table ac-table-fixed">
	<colgroup>
		<col style="width: 7%">
		<col style="width: 15%">
		<col style="width: 10%">
		<col style="width: 15%">
		<col>
		<col style="width: 200px">
	</colgroup>
	<thead>
		<tr class="alt">
			<?php echo $search->renderHeader(array(
				'id'    => __('error', 'id'),
				'class' => __('error', 'class'),
				'code'  => __('error', 'code'),
				'ip'    => __('error', 'ip-address'),
				'url'   => __('error', 'url'),
				'date'  => __('error', 'date'),
			)) ?>
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
			<td><?php echo $error->date($datetimeFormat)?></td>
		</tr>
	<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
	<tr>
		<td colspan="6">
			<div style="position: relative">
				<div style="position: absolute; right: 0;">
					<?php echo $search->limit()->attr('class', 'ac-search-limit')->render() ?>
				</div>
				<?php echo $paginator->render() ?>
			</div>
		</td>
	</tr>
	</tfoot>
</table>
<span class="ac-search-result"><?php echo __('application', 'search-results-' . ($errorCount === 1 ? 's' : 'p'), number_format($errorCount)) ?></span>
