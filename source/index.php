<?php
ini_set("display_errors", "On");
ini_set("display_startup_errors", "On");

require_once __DIR__.'/bootstrap.php';

use Areanet\PIM\Controller;
use \Areanet\PIM\Classes\Config;

define('ROOT_DIR', __DIR__);

$app['ui.controller'] = $app->share(function() use ($app) {
    return new Controller\UiController($app);
});

$app['setup.controller'] = $app->share(function() use ($app) {
    return new Controller\SetupController($app);
});

$app->get('/setup', "setup.controller:setupAction");

require_once __DIR__.'/custom/app.php';

$app->get('/', 'ui.controller:showAction');
$app->get(Config\Adapter::getConfig()->FRONTEND_URL, 'ui.controller:showAction');


$app->mount('/api', new \Areanet\PIM\Classes\Controller\Provider\Base\ApiControllerProvider('/api'));
$app->mount('/auth', new \Areanet\PIM\Classes\Controller\Provider\Base\AuthControllerProvider('/auth'));
$app->mount('/file', new \Areanet\PIM\Classes\Controller\Provider\Base\FileControllerProvider('/file'));
$app->mount('/push', new \Areanet\PIM\Classes\Controller\Provider\Base\PushControllerProvider('/push'));

$app->run();

