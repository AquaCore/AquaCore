<?php
/**
 * @var $characters      \Aqua\Ragnarok\Character[]
 * @var $character_count int
 * @var $paginator       \Aqua\UI\Pagination
 * @var $search          \Aqua\UI\Search
 * @var $page            \Page\Admin\Ragnarok\Server
 */

use Aqua\UI\Sidebar;
use Aqua\UI\ScriptManager;

$page->theme->footer->enqueueScript(ScriptManager::script('aquacore.build-url'));
$page->theme->template = 'sidebar-right';
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
<table class="ac-table" style="width: 100%">
	<colgroup>
		<col style="width: 16px">
		<col style="width: 80px">
		<col style="width: 90px">
		<col>
		<col>
		<col>
		<col style="width: 90px">
		<col style="width: 90px">
		<col>
		<col style="width: 110px">
		<col style="width: 24px">
		<col>
	</colgroup>
	<thead>
		<tr class="alt">
			<?php echo $search->renderHeader(array(
				'online' => '',
			    'id'     => __('ragnarok', 'id'),
			    'head'   => '',
			    'name'   => __('ragnarok', 'name'),
			    'acc'    => __('ragnarok', 'account'),
			    'class'  => __('ragnarok', 'class'),
			    'blv'    => __('ragnarok', 'base-level'),
			    'jlv'    => __('ragnarok', 'job-level'),
			    'zeny'   => __('ragnarok', 'zeny'),
			    'map'    => __('ragnarok', 'map'),
			    'guild'  => array( __('ragnarok', 'guild'), 2 )
			    )) ?>
		</tr>
	</thead>
	<tbody>
	<?php if(empty($characters)) : ?>
		<tr><td colspan="12" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td></tr>
	<?php else : foreach($characters as $char) : ?>
		<tr>
			<td></td>
			<td><?php echo $char->id ?></td>
			<td><img src="<?php echo ac_char_head($char); ?>"></td>
			<td><a href="<?php echo $char->charmap->url(array(
			            'action' => 'viewchar',
			            'arguments' => array( $char->id )
					)) ?>"><?php echo htmlspecialchars($char->name) ?></a></td>
			<td><a href="<?php echo $char->charmap->server->url(array(
			            'action' => 'viewaccount',
			            'arguments' => array( $char->accountId )
					)) ?>"><?php echo htmlspecialchars($char->account()->username) ?></a></td>
			<td><?php echo htmlspecialchars($char->job()) ?></td>
			<td><?php echo number_format($char->baseLevel) ?></td>
			<td><?php echo number_format($char->jobLevel) ?></td>
			<td><?php echo number_format($char->zeny) ?></td>
			<td><?php echo htmlspecialchars($char->lastMap) ?></td>
			<?php if($char->guildId) : ?>
				<td><img src="<?php echo ac_guild_emblem($char->charmap->server->key, $char->charmap->key, $char->guildId) ?>"></td>
				<td><?php echo htmlspecialchars($char->guildName) ?></td>
			<?php else : ?>
				<td colspan="2"></td>
			<?php endif; ?>
		</tr>
	<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="12">
				<div style="position: relative">
					<div style="position: absolute; right: 0;">
						<form method="GET">
						<?php echo $search->limit()->attr('class', 'ac-search-limit')->render() ?>
						</form>
					</div>
					<?php echo $paginator->render() ?>
				</div>
			</td>
		</tr>
	</tfoot>
</table>
<span class="ac-search-result"><?php echo __('application',
                                             'search-results-' . ($character_count === 1 ? 's' : 'p'),
                                             number_format($character_count)) ?></span>
