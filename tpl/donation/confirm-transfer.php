<?php
/**
 * @var $target \Aqua\User\Account
 * @var $amount int
 * @var $form   \Aqua\UI\Form
 * @var $page   \Page\Main\Donate
 */
ob_start(); ?>
	<div class="ac-confirm-transfer">
		<div class="ac-transfer-target">
			<?php echo $target->display() ?>
			<div class="ac-user-avatar"><img src="<?php echo $target->avatar() ?>"></div>
		</div>
		<div class="ac-transfer-amount"><?php echo __('donation', 'send-credits', __('donation', 'credit-points', number_format($amount)), $target->display()) ?></div>
	</div>
<?php
$form->field('submit')->attr('class', 'ac-button');
$form->prepend(ob_get_contents());
ob_clean();
echo $form->render();