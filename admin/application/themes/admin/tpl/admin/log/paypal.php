<?php
/**
 * @var \Aqua\Log\PayPalLog[] $txn
 * @var int                   $txnCount
 * @var \Page\Admin\Log       $page
 * @var \Aqua\UI\Search       $search
 * @var \Aqua\UI\Pagination   $paginator
 */

use Aqua\Core\App;
use Aqua\User\Role;
use Aqua\UI\Sidebar;
use Aqua\UI\ScriptManager;

$page->theme->template = 'sidebar-right';
$page->theme->footer->enqueueScript(ScriptManager::script('aquacore.build-url'));
$datetimeFormat = App::settings()->get('datetime_format');
$guest = Role::get(Role::ROLE_GUEST)->display(null, 'ac-guest');
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
				'id'          => __('donation', 'id'),
			    'user'        => __('donation', 'user'),
			    'deposited'   => __('donation', 'deposited'),
			    'gross'       => __('donation', 'gross'),
			    'credits'     => __('donation', 'credits'),
			    'type'        => __('donation', 'txn-type'),
			    'email'       => __('donation', 'payer-email'),
			    'processdate' => __('donation', 'process-date'),
			    'paydate'     => __('donation', 'payment-date'),
			)) ?>
		</tr>
	</thead>
	<tbody>
	<?php if(empty($txn)) : ?>
		<tr><td colspan="9" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td></tr>
	<?php else : foreach($txn as $t) : ?>
	<tr>
		<td><a href="<?php echo ac_build_url(array(
					'path' => array( 'log' ),
					'action' => 'viewpaypal',
					'arguments' => array( $t->id )
				)) ?>"><?php echo $t->id ?></a></td>
		<?php if($t->userId) : ?>
		<td><a href="<?php echo ac_build_url(array(
					'path' => array( 'user' ),
					'action' => 'view',
					'arguments' => array( $t->userId )
				)) ?>"><?php echo $t->account()->display() ?></a></td>
		<?php else : ?>
		<td><?php echo $guest ?></td>
		<?php endif; ?>
		<td><?php echo number_format($t->deposited, 2) ?> <small><?php echo $t->currency ?></small></td>
		<td><?php echo number_format($t->gross, 2) ?> <small><?php echo $t->currency ?></small></td>
		<td><?php echo __('donation', 'credit-points', number_format($t->credits)) ?></td>
		<td><?php echo $t->transactionType() ?></td>
		<td><?php echo htmlspecialchars($t->payerEmail) ?></td>
		<td><?php echo $t->processDate($datetimeFormat) ?></td>
		<td><?php echo $t->paymentDate($datetimeFormat) ?></td>
	</tr>
	<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
	<tr>
		<td colspan="9">
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
<span class="ac-search-result"><?php echo __('application', 'search-results-' . ($txnCount === 1 ? 's' : 'p'), number_format($txnCount)) ?></span>
