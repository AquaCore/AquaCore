<?php
/**
 * @var $guilds \Aqua\Ragnarok\Guild[]
 * @var $guildCount int
 * @var $paginator \Aqua\UI\Pagination
 * @var $search \Aqua\UI\Search
 * @var $page \Page\Admin\Ragnarok\Server
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
<table class="ac-table">
	<colgroup>
		<col>
		<col style="width: 45px">
		<col>
		<col>
		<col>
		<col>
		<col>
		<col>
		<col>
	</colgroup>
	<thead>
	<tr class="alt">
		<?php echo $search->renderHeader(array(
			'id'         => __('ragnarok', 'id'),
			'emblem'     => '',
			'name'       => __('ragnarok', 'guild-name'),
			'master'     => __('ragnarok', 'leader'),
			'members'    => __('ragnarok', 'members'),
			'maxmembers' => __('ragnarok', 'max-members'),
			'lvl'        => __('ragnarok', 'level'),
			'exp'        => __('ragnarok', 'experience'),
			'avg'        => __('ragnarok', 'avg-level'),
		)) ?>
	</tr>
	</thead>
	<tbody>
	<?php if(empty($guilds)) : ?>
		<tr><td colspan="9" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td></tr>
	<?php else : foreach($guilds as $guild) : ?>
		<tr>
			<td><?php echo $guild->id ?></td>
			<td><img src="<?php echo $guild->emblem() ?>"></td>
			<td><a href="<?php echo $guild->charmap->url(array(
					'action' => 'viewguild',
			        'arguments' => array( $guild->id )
				)) ?>"><?php echo htmlspecialchars($guild->name) ?></a></td>
			<td><a href="<?php echo $guild->charmap->url(array(
					'action' => 'viewchar',
			        'arguments' => array( $guild->leaderId )
				)) ?>"><?php echo htmlspecialchars($guild->leaderName) ?></a></td>
			<td><?php echo number_format($guild->memberCount) ?></td>
			<td><?php echo number_format($guild->memberLimit) ?></td>
			<td><?php echo number_format($guild->level) ?></td>
			<td><?php echo number_format($guild->experience) ?></td>
			<td><?php echo number_format($guild->averageLevel) ?></td>
		</tr>
	<?php endforeach; endif ?>
	</tbody>
	<tfoot>
	<tr>
		<td colspan="9">
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
                                             'search-results-' . ($guildCount === 1 ? 's' : 'p'),
                                             number_format($guildCount)) ?></span>
