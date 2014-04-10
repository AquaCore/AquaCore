<?php
/**
 * @var $item \Aqua\Ragnarok\ItemData
 * @var $form \Aqua\UI\Form
 * @var $page \Page\Admin\Ragnarok\Server
 */

use Aqua\UI\ScriptManager;

$page->theme->addWordGroup('ragnarok', array( 'confirm-delete-item-s' ));
$page->theme->footer->enqueueScript(ScriptManager::script('jquery-ui'));
$page->theme->footer->enqueueScript('confirm-delete')
	->type('text/javascript')
	->append('
(function($) {
	$(".ac-delete-shop").on("click", function(e) {
		if(!confirm(AquaCore.l("ragnarok", "confirm-delete-item-s"))) {
			e.preventDefault();
			e.stopPropagation();
			return false;
		}
	});
})(jQuery);
');
?>
<form method="POST">
<table class="ac-settings-form">
	<tr>
		<td></td>
		<td style="text-align: center"><img src="<?php echo ac_item_collection($item->id) ?>"></td>
		<td style="text-align: center"><?php echo $item->description ?: __('ragnarok', 'no-desc') ?></td>
	</tr>
	<?php echo $form->render(null, false, array( 'category', 'price' )); ?>
	<tr>
		<td colspan="3">
			<?php echo $form->field('submit')->bool('ac-default-submit')->css('float', 'right')->render() ?>
			<?php echo $form->field('delete')->attr('class', 'ac-delete-shop red')->css('float', 'right')->css('margin-right', '10px')->render() ?>
		</td>
	</tr>
</table>
</form>