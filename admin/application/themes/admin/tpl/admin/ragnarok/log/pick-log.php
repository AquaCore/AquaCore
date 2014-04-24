<?php
/**
 * @var $log       \Aqua\Ragnarok\Server\Logs\PickLog[]
 * @var $count     int
 * @var $paginator \Aqua\UI\Pagination
 * @var $search    \Aqua\UI\Search
 * @var $page      \Page\Admin\Ragnarok\Server
 */

use Aqua\Core\App;
use Aqua\UI\Sidebar;

$page->theme->template = 'sidebar-right';
$page->theme->footer->enqueueScript('cardbmp')
	->type('text/javascript')
	->src(ac_build_url(array(
		'base_dir' => \Aqua\DIR . '/tpl/scripts',
		'script'   => 'cardbmp.js'
	)));
$baseUrl = $page->charmap->url(array(
		'base_dir' => \Aqua\DIR,
		'path' => array( 'item' ),
		'action' => 'view',
		'arguments' => array( '' )
	));
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
	<colgroup>
		<col style="width: 80px">
		<col style="width: 40px">
		<col>
		<col>
		<col>
		<col>
		<col>
		<col>
		<col>
		<col>
		<col style="width: 40px">
		<col style="width: 40px">
		<col style="width: 40px">
	</colgroup>
	<thead>
	<tr class="alt">
		<?php echo $search->renderHeader(array(
				'id'     => __('ragnarok', 'id'),
				'icon'   => '',
				'date'   => __('ragnarok', 'date'),
				'map'    => __('ragnarok', 'map'),
				'char'   => __('ragnarok', 'character'),
				'type'   => __('ragnarok', 'type'),
				'item'   => __('ragnarok', 'item-id'),
				'name'   => __('ragnarok', 'item'),
				'amount' => __('ragnarok', 'amount'),
				'uniqid' => __('ragnarok', 'unique-id'),
				'cards'  => array( __('ragnarok', 'cards'), 3),
			)) ?>
	</tr>
	</thead>
	<tbody>
	<?php if(empty($log)) : ?>
		<tr><td colspan="13" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td></tr>
	<?php else : foreach($log as $pick) : ?>
		<tr>
			<td><?php echo $pick->id ?></td>
			<td><?php echo $pick->date($datetimeFormat) ?></td>
			<td><?php echo htmlspecialchars($pick->map) ?: '--' ?></td>
			<?php if($char = $pick->character()) : if($char instanceof \Aqua\Ragnarok\Character) : ?>
				<td><a href="<?php echo ac_build_url(array(
					'path' => array( 'r', $char->charmap->server->key, $char->charmap->key ),
				    'action' => 'viewchar',
				    'arguments' => array( $char->id )
				)) ?>"><?php echo htmlspecialchars($char->name) ?></a></td>
			<?php elseif($char instanceof \Aqua\Ragnarok\Mob) : ?>
				<td><a href="<?php echo $char->charmap->url(array(
					'path' => array( 'mob' ),
				    'action' => 'view',
				    'arguments' => array( $char->id )
				)) ?>"><?php echo htmlspecialchars($char->iName) ?></a></td>
			<?php endif; else : ?>
				<td><?php echo __('ragnarok', 'deleted', $pick->charId) ?></td>
			<?php endif; ?>
			<td><?php echo $pick->type() ?></td>
			<td><?php echo $pick->itemId ?></td>
			<td><?php echo $pick->item()->name() ?></td>
			<td><?php echo number_format($pick->amount) ?></td>
			<td><?php echo $pick->uniqueId ?: '--' ?></td>
			<?php
			for($i = 0; $i < 4; ++$i) {
				$pick->item()->card($i, $cardId, $enchanted);
				if($enchanted) { ?>
					<td class="ac-card-slot ac-slot-enchanted">
						<a href="<?php echo $baseUrl . $cardId ?>"><img src="<?php echo ac_item_icon($cardId) ?>"></a>
					</td>
				<?php } else if($pick->item()->slots < ($i + 1)) { ?>
					<td class="ac-card-slot ac-slot-disabled"></td>
				<?php } else if($cardId) { ?>
					<td class="ac-card-slot" ac-ro-card="<?php echo ac_item_cardbmp($cardId) ?>" title="">
						<a href="<?php echo $baseUrl . $cardId ?>"></a>
					</td>
				<?php } else { ?>
					<td class="ac-card-slot ac-slot-empty"></td>
			<?php }} ?>
		</tr>
	<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
		<tr><td colspan="13"><?php echo $paginator->render() ?></td></tr>
	</tfoot>
</table>
<span class="ac-search-result"><?php echo __('application',
                                             'search-results-' . ($count === 1 ? 's' : 'p'),
                                             number_format($count)) ?></span>
