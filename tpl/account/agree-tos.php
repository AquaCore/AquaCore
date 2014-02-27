<?php
/**
 * @var $tos \Aqua\Content\Adapter\Page
 * @var $page \Page\Main\Account
 */
?>
<form method="POST">
	<div class="ac-registration-tos">
		<div class="ac-tos-wrapper">
			<div class="ac-tos">
				<?php echo $tos->content ?>
			</div>
		</div>
		<button type="submit" class="ac-tos-agree">
			<?php echo __('registration', 'agree-tos') ?>
		</button>
		<a href="<?php echo \Aqua\URL ?>"><button type="button" class="ac-tos-disagree">
			<?php echo __('registration', 'disagree-tos') ?>
		</button></a>
		<div style="clear: both"></div>
		<input type="hidden" name="agree-tos" value="1">
	</div>
</form>
