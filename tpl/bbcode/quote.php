<?php

/**
 * @var $html    array
 * @var $content string
 */
?>use Aqua\Core\App;
<blockquote class="bbc-quote">
	<?php if(isset($html['attributes']['author']) && isset($html['attributes']['date'])) : ?>
		<div class="bbc-quote-header">
			<?php echo __('bbcode',
			              'quote',
			              $html['attributes']['author'],
			              strftime(App::settings()->get('datetime_format'), $html['attributes']['date'])); ?>
		</div>
	<?php endif; ?>
	<div class="bbc-quote-content"><?php echo $content ?></div>
</blockquote>
