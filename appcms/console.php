<?php
set_time_limit(0);

define('APPCMS_CONSOLE', true);

require_once __DIR__.'/bootstrap.php';

$app['console']->run();
