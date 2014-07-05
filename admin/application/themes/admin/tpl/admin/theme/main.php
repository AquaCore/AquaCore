<?php
/**
 * @var $path string
 * @var $themes array
 * @var $upload \Aqua\UI\Form
 * @var $page \Page\Admin\Theme
 */

use Aqua\Core\App;
use Aqua\UI\ScriptManager;

$page->theme->addWordGroup('theme', array( 'confirm-delete' ));
$page->theme->footer->enqueueScript(ScriptManager::script('aquacore.build-url'));
$page->theme->footer->enqueueScript('theme.theme')
	->type('text/javascript')
	->append('
(function($){
	$(".ac-delete-button").on("click", function(e) {
		if(!confirm(AquaCore.l("theme", "confirm-delete"))) {
			e.preventDefault();
			e.stopPropagation();
			return false;
		}
	});
	$(".select-path").on("change", function() {
		window.location = AquaCore.buildUrl({ path: [ "theme" ], arguments: [ $(this).val() ] });
	});
})(jQuery);
');
$settings = App::settings()->get('themes')->get($path);
?>
<div class="smiley-form">
	<div class="upload">
		<form method="POST" enctype="multipart/form-data">
		<?php if($upload->field('theme')->getWarning()) : ?>
			<div class="ac-field-warning"><?php echo $upload->field('theme')->getWarning() ?></div>
		<?php endif; echo $upload->field('theme')->required(false)->render(),
						  $upload->submit()->value(__('upload', 'upload'))->render(); ?>
		<br/>
		<span class="upload-message"><?php echo __('upload', 'accepted-types', 'ZIP, TAR, TAR.GZ, TAR.BZ2') ?></span>
		</form>
	</div>
	<div class="actions">
		<select class="select-path">
			<?php foreach(App::settings()->get('themes') as $key => $val) : ?>
				<option value="<?php echo htmlspecialchars(ltrim($key, '/')) ?>" <?php if($key === $path) echo 'selected' ?>>
					<?php echo htmlspecialchars(__('theme', $key)) ?>
				</option>
			<?php endforeach; ?>
		</select>
	</div>
	<div style="clear: both"></div>
</div>
<table class="ac-theme-table">
	<tbody>
	<?php while(current($themes)) : ?>
		<tr>
		<?php for($i = 0; $i < 3; ++$i) : if(current($themes)) : $theme = current($themes); ?>
			<td class="theme <?php if($theme['baseName'] === $settings->get('theme', '')) echo 'active'; ?>">
				<form method="POST">
					<input type="hidden" name="theme" value="<?php echo htmlspecialchars($theme['baseName']) ?>">
					<div class="theme-info">
						<span class="theme-title">
							<?php echo htmlspecialchars($theme['name']); if($theme['version']) : ?>
								<span class="theme-version">
									 v<?php echo htmlspecialchars($theme['version']) ?>
								</span>
							<?php endif; ?>
						</span>
						<?php if($theme['author']) : ?>
							<span class="theme-author">
								<?php if($theme['authorUrl']) : ?>
									<a href="<?php echo htmlspecialchars($theme['authorUrl']); ?>">
										<?php echo __('theme', 'by-author', htmlspecialchars($theme['author'])) ?>
									</a>
								<?php else : echo __('theme', 'by-author', htmlspecialchars($theme['author'])); endif; ?>
							</span>
						<?php endif; ?>
					</div>
					<?php if($theme['thumb']) : ?>
					<div class="theme-bg ac-delete-wrapper has-thumb" style="background-image: url(<?php echo \Aqua\URL . $theme['thumb'] ?>)">
					<?php else : ?>
					<div class="theme-bg ac-delete-wrapper">
					<?php endif; ?>
						<div class="theme-options">
							<?php if($theme['baseName'] === $settings->get('theme', '')) : ?>
								<button class="disable"
								        type="submit"
								        name="x-disable"><?php echo __('theme', 'disable') ?></button>
								<?php if($theme['options']) : ?>
									<a href="<?php echo ac_build_url(array(
										                                 'path' => array( 'theme' ),
										                                 'action' => 'edit'
									                                 )) ?>">
										<button class="options"
										        type="button"><?php echo __('application', 'edit') ?></button>
									</a>
								<?php endif; else : ?>
								<button class="enable"
								        type="submit"
								        name="x-enable"><?php echo __('theme', 'enable') ?></button>
							<?php endif; ?>
						</div>
						<input type="submit" name="x-delete" class="ac-delete-button" value="">
					</div>
				</form>
			</td>
		<?php else : ?>
			<td></td>
		<?php endif; next($themes); endfor; ?>
		</tr>
	<?php endwhile; ?>
	</tbody>
</table>
