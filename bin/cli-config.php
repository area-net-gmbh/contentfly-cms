<?php
use Doctrine\ORM\Tools\Console\ConsoleRunner;


require_once __DIR__.'/../lib/contentfly/bootstrap.php';


return ConsoleRunner::createHelperSet($app['orm.em']);