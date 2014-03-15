<?php
/**
 * @var $html    array
 * @var $content string
 */
$id = 'bbc-spoiler-' . uniqid(bin2hex(secure_random_bytes(16)));
?>
<div class="bbc-spoiler" id="<?php echo $id ?>">
	<div class="bbc-spoiler-title"><?php echo __('content', 'spoiler') ?></div>
	<div class="bbc-spoiler-content hidden"><?php echo $content ?></div>
</div>
<script>
	(function () {
		'use-strict';
		var element = document.getElementById("<?php echo $id ?>"),
			title   = element.getElementsByClassName("bbc-spoiler-title")[0],
			content = element.getElementsByClassName("bbc-spoiler-content")[0],
			pattern = /(^|\s+)hidden($|\s+)/g;
		title.onclick = function() {
			console.log(content.className, pattern.test(content.clasName));
			if(pattern.test(content.className)) {
				content.className = content.className.replace(pattern, "");
			} else {
				content.className+= " hidden";
			}
		}
	})();
</script>
