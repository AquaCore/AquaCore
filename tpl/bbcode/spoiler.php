<?php
/**
 * @var $html    array
 * @var $content string
 */
$id = 'bbc-spoiler-' . uniqid(bin2hex(secure_random_bytes(16)));
?>
<div class="bbc-spoiler" id="<?php echo $id ?>">
	<div class="bbc-spoiler-title"><?php echo __('application', 'spoiler') ?></div>
	<div class="bbc-spoiler-content"><?php echo $content ?></div>
</div>
<script>
	(function () {
		'use-strict';
		var element = document.getElementById("<?php echo $id ?>");
	})();
</script>
