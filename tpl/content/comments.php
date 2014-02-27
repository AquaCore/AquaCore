<?php
use Aqua\Core\App;
use Aqua\UI\ScriptManager;

/**
 * @var $content         \Aqua\Content\ContentData
 * @var $comments        \Aqua\Content\Filter\CommentFilter\Comment[]
 * @var $comment_count   int
 * @var $paginator       \Aqua\UI\Pagination
 * @var $form            \Aqua\UI\Form
 * @var $page            \Aqua\Site\Page
 */
$datetime_format = \Aqua\Core\App::settings()->get('datetime_format');
if($content->forged || App::user()->role()->hasPermission('comment')) {
	$page->theme->footer->enqueueScript(ScriptManager::script('ckeditor'));
	$page->theme->footer->enqueueScript(ScriptManager::script('aquacore.build-url'));
	$page->theme->footer->enqueueScript(ScriptManager::script('number-format'));
	$page->theme->footer->enqueueScript('theme.content-comments')
		->type('text/javascript')
		->append('
	(function($) {
		var loading;
		$(".ac-comment-upvote, .ac-comment-downvote").on("click", function() {
			var wrapper, id, ctype, weight, self = this;
			wrapper = $(this).closest(".ac-comment");
			if(loading || !wrapper.length) return;
			id = wrapper.attr("ac-comment-id");
			ctype = wrapper.attr("ac-comment-ctype");
			weight = $(this).is(".ac-comment-upvote") ? 1 : -1;
			if(AquaCore.settings["commentRatings"][id] === weight) weight = 0;
			loading = true;
			$.ajax({
				dataType: "json",
				cache: false,
				url: AquaCore.buildUrl({
					script: "ratecomment.php",
					query: {
						"ctype": ctype,
						"comment" : id,
						"weight": weight
					}
				}),
				success: function(data) {
					if(data.success) {
						var parent = $(self).parent();
						$(".ac-comment-upvote, .ac-comment-downvote", parent).removeClass("active");
						$(".ac-comment-points", parent).text(data.rating.format());
						AquaCore.settings["commentRatings"][id] = data.rating;
						if(data.rating !== 0) $(self).addClass("active");
					}
				},
				complete: function() {
					loading = false;
				}
			});
		});
		CKEDITOR.replace("cke-comment", AquaCore.settings.ckeComments);
	})(jQuery);
	');
	$smileys = include \Aqua\ROOT . '/settings/smiley.php';
	$ratings = $content->commentRatings(App::user()->account);
	$page->theme
		->addSettings('commentRatings', $ratings)
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
				                         'hide,acronym,quote,list',
				'height'                => 150,
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
				          'Blockquote',
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
?>
<div class="ac-comments">
	<span class="ac-comment-count">
		<?php echo __('comment',
		              'comment-count-' . ($comment_count === 1 ? 's' : 'p'),
		              number_format($comment_count)) ?>
	</span>
	<?php if(!$user->loggedIn()) : ?>
		<div class="ac-comment-login">
			<a href="<?php echo ac_build_url(array( 'path' => array( 'account' ), 'action' => 'login' )) ?>">
				<?php echo __('comment', 'login-to-comment') ?>
			</a>
		</div>
	<?php elseif(App::user()->role()->hasPermission('comment')) : ?>
		<div class="ac-comment-submit">
			<?php
			$form->field('content')->attr('id', 'cke-comment');
			echo $form->render();
			?>
		</div>
	<?php endif; ?>
	<table class="ac-comments-table">
		<?php if($comment_count === 0) : ?>
			<tr>
				<td colspan="2"><?php echo __('comment', 'no-comments-found') ?></td>
			</tr>
		<?php else : foreach($comments as $comment) : ?>
			<tr class="ac-comment <?php if($comment->anonymous) { echo 'ac-comment-anonymous'; }
			else if($comment->authorId === $content->authorId) { echo 'ac-comment-op'; } ?>"
			    ac-comment-id="<?php echo $comment->id ?>"
			    ac-comment-ctype="<?php echo $comment->contentType->id ?>">
				<td class="ac-comment-info">
					<div class="ac-comment-info-header">
						<div clas="ac-comment-date">
							<?php echo $comment->publishDate($datetime_format) ?>
						</div>
						<div class="ac-comment-author">
							<?php echo $comment->authorDisplay() ?>
						</div>
					</div>
					<div class="ac-comment-avatar">
						<img src="<?php echo $comment->authorAvatar() ?>">
					</div>
				</td>
				<td class="ac-comment-body">
					<?php if($content->contentType->filter('CommentFilter')->getOption('rating', false)) : ?>
						<div class="ac-comment-rating">
							<?php if(App::user()->role()->hasPermission('rate')) : ?>
							<a class="ac-comment-upvote <?php if(isset($ratings[$comment->id]) &&
							                                     $ratings[$comment->id] === 1) {
									echo 'active';
								} ?>"></a>
							<a class="ac-comment-downvote <?php if(isset($ratings[$comment->id]) &&
							                                       $ratings[$comment->id] === -1) {
									echo 'active';
								} ?>"></a>
							<?php endif; ?>
							<div class="ac-comment-points"><?php echo number_format($comment->rating) ?></div>
						</div>
					<?php endif; ?>
					<div class="ac-comment-content"><?php echo $comment->html ?></div>
					<?php if($comment->editDate && $comment->lastEditorId) : ?>
						<div class="ac-comment-edited">
							<?php echo __('comment',
							              'edited-by',
							              $comment->lastEditorDisplay(),
							              $comment->editDate($datetime_format)); ?>
						</div>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; endif; ?>
	</table>
	<div class="ac-comment-pagination"><?php echo $paginator->render() ?></div>
</div>
