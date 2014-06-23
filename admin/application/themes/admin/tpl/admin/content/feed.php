<?php
/**
 * @var $form \Aqua\UI\Form
 * @var $page \Page\Admin\Content
 */

?>
<form method="POST" autocomplete="off" target="_self" enctype="multipart/form-data">
<table class="ac-settings-form">
	<?php echo $form->render(null, false, array( 'title', 'description', 'category', 'copyright', 'limit', 'length', 'ttl' )) ?>
	<?php if($page->contentType->filter('FeedFilter')->getOption('icon')) : ?>
		<tr>
			<td colspan="3">
				<div class="ac-captcha-font-preview ac-delete-wrapper">
					<div style="padding: 5px 0">
						<img src="<?php echo \Aqua\URL . $page->contentType->filter('FeedFilter')->getOption('icon') ?>">
					</div>
					<input type="submit" name="x-delete-icon" class="ac-delete-button" value="">
				</div>
			</td>
		</tr>
	<?php endif; ?>
	<?php echo $form->render(null, false, array( 'icon' )) ?>
	<?php if($page->contentType->filter('FeedFilter')->getOption('image')) : ?>
		<tr>
			<td colspan="3">
				<div class="ac-captcha-font-preview ac-delete-wrapper">
					<img src="<?php echo \Aqua\URL . $page->contentType->filter('FeedFilter')->getOption('image') ?>">
					<input type="submit" name="x-delete-image" class="ac-delete-button" value="">
				</div>
			</td>
		</tr>
	<?php endif; ?>
	<?php echo $form->render(null, false, array( 'img' )) ?>
	<tr><td colspan="3"><button class="ac-button" type="submit" ac-default-submit="1"><?php echo __('application', 'submit') ?></button></td></tr>
</table>
</form>
