<?php
/**
 * @var $form \Aqua\UI\Form
 * @var $page \Page\Admin\Ragnarok\Server
 */

use Aqua\UI\ScriptManager;

$page->theme->set('wrapper', $form->buildTag());
$page->theme->addWordGroup('ragnarok-charmap', 'confirm-delete');
$page->theme->footer->enqueueScript(ScriptManager::script('jquery'));
$page->theme->footer->enqueueScript('theme.charmap-settings')
	->type('text/javascript')
	->append('
(function($){
	$(".ac-delete-server").on("click", function(e) {
		if(!confirm(AquaCore.l("ragnarok-charmap", "confirm-delete"))) {
			e.preventDefault();
			e.stopPropagation();
			return false;
		}
	});
})(jQuery);
');
?>
<table class="ac-settings-form ac-login-settings">
	<?php if($form->message) : ?>
		<tr class="ac-form-error">
			<td colspan="3">
				<div><?php echo $form->message ?></div>
			</td>
		</tr>
	<?php endif ?>
	<?php echo $form->render(null, false, array( 'name', 'key', 'timezone', 'default', 'char-host', 'char-port', 'map-host', 'map-port' )) ?>
	<tr>
		<td colspan="3">
			<div class="ac-settings-section">
				<div class="title"><?php echo __('ragnarok-charmap', 'section-settings') ?></div>
				<div class="separator"></div>
			</div>
		</td>
	</tr>
	<?php echo $form->render(null, false, array( 'renewal', 'fame', 'online-stats', 'status-timeout', 'status-cache' )) ?>
	<tr>
		<td colspan="3">
			<div class="ac-settings-section">
				<div class="title"><?php echo __('ragnarok-charmap', 'section-map-reset') ?></div>
				<div class="separator"></div>
			</div>
		</td>
	</tr>
	<?php echo $form->render(null, false, array( 'default-map', 'default-x', 'default-y', 'map-restrictions' )) ?>
	<tr>
		<td colspan="3">
			<?php echo $form->field('submit')->bool('ac-default-submit')->css('float', 'right')->render() ?>
			<?php echo $form->field('delete')->attr('class', 'ac-delete-server red')->css('float', 'right')->render() ?>
		</td>
	</tr>
</table>
