<?php
/**
 * @var $html    array
 * @var $content string
 */
?>
<iframe class="bbc-vimeo-player"
        type="text/html"
        width="640"
        height="390"
        style="width: 640px; height: 390px"
        src="//player.vimeo.com/video/<?php echo $html['attributes']['video_id'] ?>"
        frameborder="0"
        webkitallowfullscreen
        mozallowfullscreen
        allowfullscreen></iframe>
