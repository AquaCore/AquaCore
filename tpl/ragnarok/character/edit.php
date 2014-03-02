<?php
/**
 * @var $form \Aqua\UI\Form
 * @var $page \Page\Main\Ragnarok\Server\Char
 */
?>
<form method="POST">
	<?php if(($error = $form->message)) : ?>
		<div class="ac-form-error"><?php echo $error ?></div>
	<?php endif; ?>
	<table class="ac-table">
		<thead>
		<tr>
			<td colspan="4"><?php echo __('ragnarok', 'account-info', htmlspecialchars($page->char->name))?></td>
		</tr>
		</thead>
		<tbody>
			<tr class="ac-form-warning">
				<td colspan="2"><?php echo $form->field('hide_online')->getWarning() ?></td>
				<td colspan="2"><?php echo $form->field('hide_map')->getWarning() ?></td>
			</tr>
			<tr>
				<td><b><?php echo $form->field('hide_online')->getLabel()?></b></td>
				<td style="text-align: left"><?php echo $form->field('hide_online')->option('1')->render()?></td>
				<td><b><?php echo $form->field('hide_map')->getLabel()?></b></td>
				<td style="text-align: left"><?php echo $form->field('hide_map')->option('1')->render()?></td>
			</tr>
			<tr class="ac-form-warning">
				<td colspan="2"><?php echo $form->field('hide_online')->getWarning() ?></td>
				<td colspan="2"><?php echo $form->field('hide_map')->getWarning() ?></td>
			</tr>
			<tr>
				<td><b><?php echo $form->field('hide_zeny')->getLabel()?></b></td>
				<td style="text-align: left"><?php echo $form->field('hide_zeny')->option('1')->render()?></td>
				<td colspan="2"></td>
			</tr>
		</tbody>
		<tfoot>
		<tr>
			<td colspan="4">
				<span style="float: right">
					<?php
					echo $form->field('ragnarok_edit_char')->render(),
						 $form->field('submit')->render(),
						 $form->field('reset_look')->css('margin-left', '10px')->render(),
						 $form->field('reset_pos')->css('margin-left', '10px')->render();
					?>
				</span>
			</td>
		</tr>
		</tfoot>
	</table>
</form>
