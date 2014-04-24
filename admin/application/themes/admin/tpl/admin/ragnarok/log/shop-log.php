<?php
/**
 * @var $log       \Aqua\Ragnarok\Server\Logs\CashSHopLog[]
 * @var $count     int
 * @var $paginator \Aqua\UI\Pagination
 * @var $search    \Aqua\UI\Search
 * @var $page      \Page\Admin\Ragnarok\Server
 */

use Aqua\Core\App;
use Aqua\UI\Sidebar;

$page->theme->template = 'sidebar-right';
$datetimeFormat = App::settings()->get('datetime_format');
$sidebar = new Sidebar;
foreach($search->content as $key => $field) {
	$content = $field->render();
	if($desc = $field->getDescription()) {
		$content.= "<br/><small>$desc</small>";
	}
	$sidebar->append($key, array(array(
		'title' => $field->getLabel(),
		'content' => $content
	)));
}
$sidebar->append('submit', array('class' => 'ac-sidebar-action', array(
	'content' => '<input class="ac-sidebar-submit" type="submit" value="' . __('application', 'search') . '">'
)));
$sidebar->wrapper($search->buildTag());
$page->theme->set('sidebar', $sidebar);
?>
<table class="ac-table">
	<thead>
		<tr class="alt">
			<?php echo $search->renderHeader(array(
					'id'     => __('ragnarok', 'id'),
					'date'   => __('ragnarok', 'date'),
					'acc'    => __('ragnarok', 'account'),
					'amount' => __('ragnarok', 'items-purchased'),
					'total'  => __('ragnarok', 'subtotal')
				)) ?>
		</tr>
	</thead>
	<tbody>
	<?php if(empty($log)) : ?>
		<tr><td colspan="5" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td></tr>
	<?php else : foreach($log as $purchase) : ?>
		<tr>
			<td><a href="<?php echo $purchase->charmap->url(array(
			        'action' => 'viewshoplog',
			        'arguments' => array( $purchase->id )
				)) ?>"><?php echo $purchase->id ?></a></td>
			<td><?php echo $purchase->date($datetimeFormat) ?></td>
			<td><a href="<?php echo $purchase->charmap->server->url(array(
			    'action' => 'viewaccount',
			    'arguments' => array( $purchase->accountId )
			)) ?>"><?php echo htmlspecialchars($purchase->account()->username) ?></a></td>
			<td><?php echo number_format($purchase->amount) ?></td>
			<td><?php echo __('donation', 'credit-points', number_format($purchase->total)) ?></td>
		</tr>
	<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
		<tr><td colspan="5"><?php echo $paginator->render() ?></td></tr>
	</tfoot>
</table>
<span class="ac-search-result"><?php echo __('application',
                                             'search-results-' . ($count === 1 ? 's' : 'p'),
                                             number_format($count)) ?></span>
