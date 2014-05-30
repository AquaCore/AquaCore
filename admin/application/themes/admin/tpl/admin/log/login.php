<?php
/**
 * @var \Aqua\Log\LoginLog[] $login
 * @var int                  $loginCount
 * @var \Page\Admin\Log      $page
 * @var \Aqua\UI\Search      $search
 * @var \Aqua\UI\Pagination  $paginator
 */

use Aqua\Core\App;
use Aqua\Log\LoginLog;
use Aqua\UI\Sidebar;
use Aqua\UI\ScriptManager;

$search->field('status')->css('height', '4.5em');

$page->theme->template = 'sidebar-right';
$page->theme->footer->enqueueScript(ScriptManager::script('aquacore.build-url'));
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
$datetimeFormat = App::settings()->get('datetime_format', '');
?>
<table class="ac-table">
	<thead>
	<tr class="alt">
		<?php echo $search->renderHeader(array(
			'uname'  => __('login-log', 'username'),
		    'user'   => __('login-log', 'logged-as'),
		    'ip'     => __('login-log', 'ip-address'),
		    'type'   => __('login-log', 'type'),
		    'status' => __('login-log', 'status'),
		    'date'   => __('login-log', 'date')
		)); ?>
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
			<td><?php echo $l->date($datetimeFormat)?></td>
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
	</tr>	</tfoot>
</table>
<span class="ac-search-result"><?php echo __('application', 'search-results-' . ($loginCount === 1 ? 's' : 'p'), number_format($loginCount)) ?></span>
