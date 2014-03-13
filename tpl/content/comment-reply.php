<?php
use Aqua\UI\ScriptManager;
/**
 * @var $form    \Aqua\UI\Form
 * @var $content \Aqua\Content\ContentData
 * @var $comment null|\Aqua\Content\Filter\CommentFilter\Comment
 * @var $page    \Page\Main\Comment
 */

$page->theme->footer->enqueueScript(ScriptManager::script('ckeditor'));
$page->theme->footer->enqueueScript(ScriptManager::script('aquacore.build-url'));
$page->theme->footer->enqueueScript(ScriptManager::script('number-format'));
$page->theme->footer->enqueueScript('tpl.comments')
	->type('text/javascript')
	->src(ac_build_url(array(
		'base_dir' => \Aqua\DIR . '/tpl/scripts',
		'script' => 'comments.js'
	)));
$smileys = include \Aqua\ROOT . '/settings/smiley.php';
$page->theme
	->addSettings('ckeComments',array(
		'font_sizes'            => 'Georgia/Georgia, serif;' .
		                           'Palatino/"Palatino Linotype", "Book Antiqua", Palatino, serif;' .
		                           'Times New Roman/"Times New Roman", Times, serif;' .
		                           'Arial/Arial, Helvetica, sans-serif;' .
		                           'Helvetica/Helvetica, sans-serif;' .
		                           'Arial Black/"Arial Black", Gadget, sans-serif;' .
		                           'Comic Sans MS/"Comic Sans MS", cursive, sans-serif;' .
		                           'Impact/Impact, Charcoal, sans-serif;' .
		                           'Lucida Sans/"Lucida Sans Unicode", "Lucida Grande", sans-serif;' .
		                           'Tahoma/Tahoma, Geneva, sans-serif;' .
		                           'Trebuchet MS/"Trebuchet MS", Helvetica, sans-serif;' .
		                           'Verdana/Verdana, Geneva, sans-serif;' .
		                           'Courier New/"Courier New", Courier, monospace;' .
		                           'Lucida Console/"Lucida Console", Monaco, monospace;',
		'fontSize_sizes'        => '25%;50%;75%;100%;125%;175%;200%;225%;275%;300%;',
		'basicEntities'         => false,
		'entities'              => false,
		'fillEmptyBlocks'       => false,
		'forcePasteAsPlainText' => true,
		'smiley_path'           => \Aqua\URL . '/uploads/smiley/',
		'smiley_descriptions'   => array_keys($smileys),
		'smiley_images'         => array_values($smileys),
		'removePlugins'         => 'autogrow,pagination',
		'extraPlugins'          => 'bbcode',
		'bbCodeTags'            => 'b,s,u,i,' .
		                           'sub,sup,' .
		                           'url,email,img,' .
		                           'color,background,' .
		                           'size,font,' .
		                           'indent,center,right,justify' .
		                           'hide,spoiler,acronym,list',
		'height'                => 100,
		'enterMode'             => 2,
		'allowedContent'        => false,
		'toolbar'               => array(
			array(
				'name'  => 'editing',
				'items' => array(
					'Cut', 'Copy',
					'-',
					'addPage',
					'-',
					'Find', 'Replace', 'SelectAll',
					'-',
					'Undo', 'Redo'
				)
			),
			array(
				'name'  => 'clipboard',
				'items' => array( 'Paste', 'PasteText', 'PasteFromWord' )
			),
			array(
				'name'  => 'insert',
				'items' => array(
					'Link', 'Unlink',
					'-',
					'Smiley', 'Image',
				)
			),
			array(
				'name'  => 'view',
				'items' => array( 'Maximize', '-', 'Source' )
			),
			'/',
			array(
				'name'  => 'basicstyles',
				'items' => array(
					'Bold', 'Italic', 'Underline', 'Strike',
					'-',
					'Subscript', 'Superscript',
					'-',
					'RemoveFormat'
				)
			),
			array(
				'name'  => 'blocks',
				'items' => array(
					'NumberedList', 'BulletedList',
					'-',
					'Outdent', 'Indent',
					'-',
					'Spoiler',
					'-',
					'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock',
				)
			),
			array(
				'name'  => 'color',
				'items' => array( 'TextColor', 'BGColor' )
			),
			array(
				'name'  => 'format',
				'items' => array( 'Font' )
			),
			array(
				'name'  => 'format',
				'items' => array( 'FontSize' )
			)
		)
	));
?>
<div class="ac-comments ac-comments-action">
<?php
if(!empty($comment)) {
	$tpl = new \Aqua\UI\Template;
	$tpl->set('content', $content)
		->set('comment', $comment)
		->set('level', 0)
		->set('actions', false)
		->set('page', $page);
	echo $tpl->render('content/comment');
}
?>
	<div class="ac-comment-submit">
		<form method="POST">
			<textarea name="content" id="cke-comment"></textarea>
			<?php if($content->getMeta('comment-anonymously')) : ?>
				<input type="checkbox" name="anonymous" value="1" id="anon-comment">
				<label for="anon-comment"><?php echo __('comment', 'comment-anonymously') ?></label>
			<?php endif; ?>
			<input type="submit" value="<?php echo __('application', 'submit') ?>" class="ac-button">
			<div style="clear: both"></div>
		</form>
	</div>
</div>
