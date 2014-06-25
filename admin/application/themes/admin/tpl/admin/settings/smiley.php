<?php
/**
 * @var $smileys array
 * @var $form \Aqua\UI\Form
 * @var $page \Page\Admin\BBCode
 */

use Aqua\BBCode\Smiley;
use Aqua\UI\ScriptManager;

$page->theme->head->enqueueScript(ScriptManager::script('jquery-ui'));

$smileysPerRow = 8;
$count = ceil(count($smileys) / $smileysPerRow) * $smileysPerRow;
?>
<form method="POST" enctype="multipart/form-data">
<div class="smiley-form">
	<div class="upload">
		<?php if($form->field('smileys')->getWarning()) : ?>
			<div class="ac-field-warning"><?php echo $form->field('smileys')->getWarning() ?></div>
		<?php endif; ?>
		<?php echo $form->field('smileys')->required(false)->render(),
			 $form->submit()->value(__('upload', 'upload'))->render(); ?>
		<br/>
		<span class="upload-message"><?php echo __('bbcode', 'smiley-upload') ?></span>
	</div>
	<div class="actions">
		<select name="action">
			<option value="save"><?php echo __('application', 'save') ?></option>
			<option value="order"><?php echo __('application', 'save-order') ?></option>
			<option value="delete"><?php echo __('application', 'delete') ?></option>
		</select>
		<input type="submit" name="x-bulk" value="<?php echo __('application', 'apply')?>" ac-default-submit="1"/>
	</div>
	<div style="clear: both"></div>
</div>
<ul class="smiley-list">
	<?php for($i = 0; $i < $count; $i++) : if(current($smileys)) :
		$smileyId = key($smileys);
		$smiley   = current($smileys); ?>
	<li class="smiley">
		<div class="wrapper">
			<input type="hidden" name="order[]" value="<?php echo $smileyId ?>"/>
			<input class="smiley-checkbox" type="checkbox" id="smiley<?php echo $smileyId ?>" name="smileys[]" value="<?php echo $smileyId ?>"/>
			<label for="smiley<?php echo $smileyId ?>">
			<div class="image-wrapper">
				<div class="image-align">
					<img src="<?php echo \Aqua\URL . Smiley::DIRECTORY . $smiley['file'] ?>"><p/>
				</div>
			</div>
			</label>
			<div class="input-wrapper">
				<input name="smileytext[<?php echo $smileyId ?>]"
				       maxlength="32"
				       value="<?php echo htmlspecialchars($smiley['text']) ?>"/>
			</div>
		</div>
	</li>
	<?php next($smileys); else : ?>
	<li></li>
	<?php endif; endfor; ?>
</ul>
<script>
$(".smiley-list").sortable({
	cursor: "move"
})
</script>
</form>
