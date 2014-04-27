<?php
/**
 * @var $html    array
 * @var $content string
 */

use Aqua\Core\App;
use Aqua\Content\ContentType;
use Aqua\SQL\Query;
use Aqua\User\Account;

$comment = null;
if(isset($html['attributes']['commentid'])) {
	$select = Query::search(App::connection())
		->columns(array(
			'id'           => 'co.id',
			'user_id'      => 'co._author_id',
			'publish_date' => 'UNIX_TIMESTAMP(COALESCE(co._edit_date, co._publish_date))',
			'content_id'   => 'co._content_id',
		    'content_type' => 'c._type',
		    'slug'         => 'c._slug'
		))
		->setColumnType(array(
			'id'           => 'integer',
			'user_id'      => 'integer',
			'content_id'   => 'integer',
			'content_type' => 'integer',
			'publish_date' => 'timestamp'
		))
		->from(ac_table('comments'), 'co')
		->innerJoin(ac_table('content'), 'c._uid = co._content_id', 'c')
		->where(array( 'co.id' => $html['attributes']['commentid'] ))
		->limit(1)
		->query();
	if($select->valid()) {
		$comment = $select->current();
	}
}
?>
<blockquote class="bbc-quote">
	<div class="bbc-quote-header">
	<?php if($comment) : ?>
		<div class="bbc-quote-author">
			<?php echo __('content', 'quote-author', Account::get($comment['user_id'], 'id')->display()) ?>
		</div>
		<div class="bbc-quote-date">
			<?php echo __('content', 'quote-date', strftime(App::settings()->get('datetime_format'), $comment['publish_date'])) ?>
			<div class="bbc-quote-link">
				<a href="<?php echo ContentType::getContentType($comment['content_type'], 'id')->url(array(
						'path' => array( $comment['slug'] ),
				        'query' => array( 'root' => $comment['id'] ),
				        'hash' => 'comments'
					)) ?>"></a>
			</div>
		</div>
		<div style="clear: both"></div>
	<?php elseif(isset($html['attributes']['author']) && isset($html['attributes']['date'])) : ?>
		<div class="bbc-quote-author">
			<?php echo __('content', 'quote-author', $html['attributes']['author']) ?>
		</div>
		<div class="bbc-quote-date">
			<?php echo __('content', 'quote-date', strftime(App::settings()->get('datetime_format'), $html['attributes']['date'])) ?>
		</div>
		<div style="clear: both"></div>
	<?php else : echo __('content', 'quote'); endif; ?>
	</div>
	<div class="bbc-quote-content"><?php echo $content ?></div>
</blockquote>
