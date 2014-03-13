<?php
use Aqua\Core\App;
use Aqua\UI\ScriptManager;

/**
 * @var $content      \Aqua\Content\ContentData
 * @var $comments     \Aqua\Content\Filter\CommentFilter\Comment[]
 * @var $commentCount int
 * @var $paginator    \Aqua\UI\Pagination
 * @var $page         \Aqua\Site\Page
 */
if($content->forged || App::user()->role()->hasPermission('comment')) {
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
	$ratings = $content->commentRatings(App::user()->account);
	$page->theme
		->addWordGroup('comment', array( 'cancel', 'reply', 'comment-anonymously' ))
		->addSettings('contentData', array(
			'ID' => $content->uid,
		    'cType' => $content->contentType->id,
		    'allowAnonymous' => (bool)$content->getMeta('comment-anonymously')
		))
		->addSettings('commentRatings', $ratings)
		->addSettings('base64Url', base64_encode(App::request()->uri->url()))
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
} else {
	$ratings = array();
}
$user = \Aqua\Core\App::user();
$tpl = new \Aqua\UI\Template;
?>
<div class="ac-comments">
	<span class="ac-comment-count">
		<?php echo __('comment',
		              'comment-count-' . ($commentCount === 1 ? 's' : 'p'),
		              number_format($commentCount)) ?>
	</span>
	<?php if(!$user->loggedIn()) : ?>
		<div class="ac-comment-login">
			<a href="<?php echo ac_build_url(array( 'path' => array( 'account' ), 'action' => 'login' )) ?>">
				<?php echo __('comment', 'login-to-comment') ?>
			</a>
		</div>
	<?php elseif(App::user()->role()->hasPermission('comment')) : ?>
		<div class="ac-comment-submit">
			<form method="POST" action="<?php echo ac_build_url(array(
				'path' => array( 'comment' ),
			    'action' => 'reply',
			    'arguments' => array( $content->contentType->key, $content->uid ),
			    'query' => array( 'return' => $page->theme->jsSettings['base64Url'] )
			)) ?>">
				<textarea id="cke-comment"></textarea>
				<?php if($content->getMeta('comment-anonymously')) : ?>
					<input type="checkbox" name="anonymous" value="1" id="anon-comment">
					<label for="anon-comment"><?php echo __('comment', 'comment-anonymously') ?></label>
				<?php endif; ?>
				<input type="submit" value="<?php echo __('application', 'submit') ?>" class="ac-button">
			</form>
		</div>
	<?php endif; ?>
	<?php if(empty($comments)) : ?>
		<div class="ac-table-no-result"><?php echo __('comment', 'no-comments-found') ?></div>
	<?php else : ?>
		<div id="comments">
		<?php
		foreach($comments as $comment) {
			$tpl->set('page', $page)
			    ->set('ratings', $ratings)
			    ->set('level', 0)
			    ->set('actions', true)
			    ->set('content', $content)
				->set('comment', $comment);
			echo $tpl->render('content/comment');
		}
		?>
		</div>
	<?php endif; ?>
	<div class="ac-comment-pagination"><?php echo $paginator->render() ?></div>
</div>
