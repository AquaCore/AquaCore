<?php
/**
 * @var $account \Aqua\User\Account
 * @var $form    \Aqua\UI\Form
 * @var $page    \Page\Admin\User
 */
?>
<form method="POST">
	<div style="position: relative; padding-bottom: 40px; float: left">
	<?php
	echo $form->submit()->css(array(
			'position' => 'absolute',
			'bottom'   => '0px',
			'right'    => '20px'
		))->render();
	?>
	<table>
		<?php if(($warning = $form->field('avatar_type')->getWarning()) ||
				 ($warning = $form->field('gravatar')->getWarning()) ||
				 ($warning = $form->field('image')->getWarning())) : ?>
		<tr><td class="ac-form-warning" colspan="3"><?php echo $warning ?></td></tr>
		<?php endif; ?>
		<tr>
			<td rowspan="3" colspan="2" style="text-align: center">
				<div class="ac-account-avatar ac-delete-wrapper">
					<img src="<?php echo $account->avatar() ?>">
					<?php if($account->avatar) : ?>
						<input type="submit" class="ac-delete-button" name="x-delete-avatar" value="">
					<?php endif; ?>
				</div>
			</td>
		</tr>
		<tr>
			<td>
				<input type="radio" name="avatar_type" value="image" id="avatar-type-image" checked="checked">
				<label for="avatar-type-image"><?php echo __('profile', 'use-custom-pic') ?></label><br>
				<?php echo $form->field('image')->render() ?>
			</td>
		</tr>
		<tr>
			<td>
				<input type="radio" name="avatar_type" value="gravatar" id="avatar-type-gravatar">
				<label for="avatar-type-gravatar"><?php echo __('profile', 'use-gravatar') ?></label><br>
				<?php echo $form->field('gravatar')->render() ?>
			</td>
		</tr>
		<?php echo $form->render(null, function($content) { return $content; }, array( 'username', 'display_name', 'email', 'birthday', 'role', 'credits', 'password' )); ?>
	</table>
	</div>
</form>
