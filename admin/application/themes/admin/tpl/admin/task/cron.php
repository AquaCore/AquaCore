<?php
echo __('task', 'cron-help',
        htmlspecialchars(\Aqua\ROOT),
        ac_build_url(array( 'path' => array( 'settings' ) )),
        \Aqua\Core\App::settings()->get('cron_key'));