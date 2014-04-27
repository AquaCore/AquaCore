<?php
use Aqua\Core\App;
use Aqua\UI\ScriptManager;
use Aqua\UI\StyleManager;

/**
 * @var $content      \Aqua\Content\ContentData
 * @var $comments     \Aqua\Content\Filter\CommentFilter\Comment[]
 * @var $commentCount int
 * @var $paginator    \Aqua\UI\Pagination
 * @var $page         \Aqua\Site\Page
 */
if($content->forged || App::user()->role()->hasPermission('comment')) {
	$page->theme->head->enqueueLink(StyleManager::style('bbcode'));
	$page->theme->footer->enqueueScript(ScriptManager::script('ckeditor'));
	$page->theme->footer->enqueueScript(ScriptManager::script('aquacore.build-url'));
	$page->theme->footer->enqueueScript(ScriptManager::script('number-format'));
	$page->theme->footer->enqueueScript('tpl.comments')
		->type('text/javascript')
		->src(ac_build_url(array(
			'base_dir' => \Aqua\DIR . '/tpl/scripts',
			'script' => 'comments.js'
		)));
	$ratings = $content->commentRatings(App::user()->account);
	$page->theme
		->addWordGroup('comment', array( 'cancel', 'reply', 'comment-anonymously', 'report', 'report-placeholder' ))
		->addSettings('contentData', array(
			'ID' => $content->uid,
		    'cType' => $content->contentType->id,
		    'allowAnonymous' => (bool)$content->meta->get('comment-anonymously')
		))
		->addSettings('commentRatings', $ratings)
		->addSettings('base64Url', base64_encode(App::request()->uri->url()))
		->addSettings('ckeComments', include \Aqua\ROOT . '/settings/ckeditor.php');
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
	<?php if($content->contentType->hasFilter('ArchiveFilter') && $content->isArchived()) : ?>
		<div class="ac-table-no-result">
			<?php echo __('comment', 'archived') ?>
		</div>
	<?php elseif(!$user->loggedIn()) : ?>
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
				<textarea name="content" id="cke-comment"></textarea>
				<?php if($content->meta->get('comment-anonymously')) : ?>
					<input type="checkbox" name="anonymous" value="1" id="anon-comment">
					<label for="anon-comment"><?php echo __('comment', 'comment-anonymously') ?></label>
				<?php endif; ?>
				<input type="submit" value="<?php echo __('application', 'submit') ?>" class="ac-button">
				<div style="clear: both"></div>
			</form>
		</div>
	<?php endif; ?>
	<?php if(empty($comments)) : ?>
		<div class="ac-table-no-result"><?php echo __('comment', 'no-comments-found') ?></div>
	<?php else : ?>
		<?php if($page->request->uri->getInt('root')) : ?>
			<div class="ac-comments-all">
				<a href="<?php echo App::request()->uri->url(array(
					'query' => array( 'root' => null ) + App::request()->uri->parameters,
				    'hash'  => 'comments'
				)) ?>">
					<?php echo __('comment', 'view-all-comments') ?>
				</a>
			</div>
		<?php endif; ?>
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
