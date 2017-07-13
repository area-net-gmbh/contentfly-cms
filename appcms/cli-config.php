<?php
use Doctrine\ORM\Tools\Console\ConsoleRunner;

define('APPCMS_CONSOLE', true);

require_once __DIR__.'/bootstrap.php';


return ConsoleRunner::createHelperSet($app['orm.em']);