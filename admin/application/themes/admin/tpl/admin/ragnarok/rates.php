<?php
/**
 * @var $form \Aqua\UI\Form
 * @var $page \Page\Admin\Ragnarok\Server
 */
?>
<form method="POST">
<table class="ac-settings-form">
	<?php echo $form->render(null, false, array( 'logarithmic_drops' )) ?>
	<tr>
		<td colspan="3">
			<div class="ac-settings-section">
				<div class="title"><?php echo __('ragnarok-charmap', 'section-experience') ?></div>
				<div class="separator"></div>
			</div>
		</td>
	</tr>
	<?php echo $form->render(null, false, array( 'base_exp', 'job_exp', 'mvp_exp', 'quest_exp' )) ?>
	<tr>
		<td colspan="3">
			<div class="ac-settings-section">
				<div class="title"><?php echo __('ragnarok-charmap', 'section-common-rate') ?></div>
				<div class="separator"></div>
			</div>
		</td>
	</tr>
	<?php echo $form->render(null, false, array( 'common_rate', 'common_boss', 'common_min', 'common_max' )) ?>
	<tr>
		<td colspan="3">
			<div class="ac-settings-section">
				<div class="title"><?php echo __('ragnarok-charmap', 'section-heal-rate') ?></div>
				<div class="separator"></div>
			</div>
		</td>
	</tr>
	<?php echo $form->render(null, false, array( 'heal_rate', 'heal_boss', 'heal_min', 'heal_max' )) ?>
	<tr>
		<td colspan="3">
			<div class="ac-settings-section">
				<div class="title"><?php echo __('ragnarok-charmap', 'section-equip-rate') ?></div>
				<div class="separator"></div>
			</div>
		</td>
	</tr>
	<?php echo $form->render(null, false, array( 'equip_rate', 'equip_boss', 'equip_min', 'equip_max' )) ?>
	<tr>
		<td colspan="3">
			<div class="ac-settings-section">
				<div class="title"><?php echo __('ragnarok-charmap', 'section-card-rate') ?></div>
				<div class="separator"></div>
			</div>
		</td>
	</tr>
	<?php echo $form->render(null, false, array( 'card_rate', 'card_boss', 'card_min', 'card_max' )) ?>
	<tr>
		<td colspan="3">
			<div class="ac-settings-section">
				<div class="title"><?php echo __('ragnarok-charmap', 'section-mvp-rate') ?></div>
				<div class="separator"></div>
			</div>
		</td>
	</tr>
	<?php echo $form->render(null, false, array( 'mvp_rate', 'mvp_min', 'mvp_max' )) ?>
	<tr><td colspan="3"><?php echo $form->field('submit')->attr('class', 'ac-button')->render() ?></td></tr>
</table>
</form>