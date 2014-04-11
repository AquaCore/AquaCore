<?php
/**
 * @var $schedule array
 * @var $form     \Aqua\UI\Form
 * @var $page     \Page\Admin\Ragnarok\Server
 */

use Aqua\UI\ScriptManager;

$page->theme->addWordGroup('ragnarok-charmap', array( 'confirm-delete-schedule-s' ));
$page->theme->footer->enqueueScript(ScriptManager::script('jquery-ui'));
$page->theme->footer->enqueueScript('confirm-delete')
                    ->type('text/javascript')
                    ->append('
(function($) {
	$(".ac-delete-shop").on("click", function(e) {
		if(!confirm(AquaCore.l("ragnarok-charmap", "confirm-delete-schedule-s"))) {
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
		<?php echo $form->render(null, false, array( 'name', 'castles', 'starttime', 'startday', 'endtime', 'endday' )); ?>
		<tr>
			<td colspan="3">
				<?php echo $form->field('submit')->bool('ac-default-submit')->css('float', 'right')->render() ?>
				<?php echo $form->field('delete')->attr('class', 'ac-delete-shop red')->css('float', 'right')->css('margin-right', '10px')->render() ?>
			</td>
		</tr>
	</table>
</form>
