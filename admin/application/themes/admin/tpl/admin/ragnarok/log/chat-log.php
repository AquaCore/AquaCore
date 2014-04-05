<?php
/**
 * @var $log       \Aqua\Ragnarok\Server\Logs\ChatLog[]
 * @var $count     int
 * @var $paginator \Aqua\UI\Pagination
 * @var $page      \Page\Admin\Ragnarok\Server
 */

use Aqua\Core\App;

$datetimeFormat = App::settings()->get('datetime_format');
?>
<table class="ac-table">
	<thead>
	<tr class="alt">
		<td><?php echo __('ragnarok', 'id') ?></td>
		<td><?php echo __('ragnarok', 'date') ?></td>
		<td><?php echo __('ragnarok', 'type') ?></td>
		<td><?php echo __('ragnarok', 'type-id') ?></td>
		<td><?php echo __('ragnarok', 'account') ?></td>
		<td><?php echo __('ragnarok', 'character') ?></td>
		<td><?php echo __('ragnarok', 'receiver') ?></td>
		<td><?php echo __('ragnarok', 'map') ?></td>
		<td><?php echo __('ragnarok', 'x-coord') ?></td>
		<td><?php echo __('ragnarok', 'y-coord') ?></td>
	</tr>
	</thead>
	<tbody>
	<?php if(empty($log)) : ?>
		<tr><td colspan="10" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td></tr>
	<?php else : foreach($log as $chat) : ?>
		<tr>
			<td><?php echo $chat->id ?></td>
			<td><?php echo $chat->date($datetimeFormat) ?></td>
			<td><?php echo $chat->type() ?></td>
			<td><?php echo $chat->typeId ?: '--' ?></td>
			<td><a href="<?php echo ac_build_url(array(
					'path' => array( 'r', $page->server->key ),
			        'action' => 'viewaccount',
			        'arguments' => array( $chat->srcAccountId )
				)) ?>"><?php echo htmlspecialchars($chat->account()->username) ?></a></td>
			<?php if($char = $chat->source()) : ?>
				<td><a href="<?php echo ac_build_url(array(
					'path' => array( 'r', $char->charmap->server->key, $char->charmap->key ),
				    'action' => 'viewchar',
				    'arguments' => array( $char->id )
				)) ?>"><?php echo htmlspecialchars($char->name) ?></a></td>
			<?php else : ?>
				<td><?php echo __('ragnarok', 'deleted', $chat->srcCharId) ?></td>
			<?php endif ?>
			<td><?php echo htmlspecialchars($chat->dstName) ?: '--' ?></td>
			<td><?php echo htmlspecialchars($chat->map) ?: '--' ?></td>
			<td><?php echo $chat->x ?: '--' ?></td>
			<td><?php echo $chat->y ?: '--' ?></td>
		</tr>
		<tr>
			<td colspan="10"><?php echo htmlspecialchars($chat->message) ?></td>
		</tr>
	<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
	<tr><td colspan="10"><?php echo $paginator->render() ?></td></tr>
	</tfoot>
</table>
<span class="ac-search-result"><?php echo __('application',
                                             'search-results-' . ($count === 1 ? 's' : 'p'),
                                             number_format($count)) ?></span>
