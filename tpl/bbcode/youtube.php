<?php
/**
 * @var $html    array
 * @var $content string
 */
$query = array(
	'origin' => \Aqua\URL
);
if(isset($html['attributes']['start'])) {
	$query['start'] = $html['attributes']['start'];
}
$query = http_build_query($query);
?>
<iframe class="bbc-youtube-player"
        type="text/html"
        width="640"
        height="390"
        style="width: 640px; height: 390px"
        src="https://www.youtube.com/embed/<?php echo $html['attributes']['video_id'] ?>?<?php echo $query ?>"
        frameborder="0"
        webkitallowfullscreen
        mozallowfullscreen
        allowfullscreen></iframe>
