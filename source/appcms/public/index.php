<?php
ini_set("display_errors", "On");
ini_set("display_startup_errors", "On");

require_once __DIR__.'/../bootstrap.php';

use Areanet\PIM\Controller;
use \Areanet\PIM\Classes\Config;



$app['ui.controller'] = $app->share(function() use ($app) {
    return new Controller\UiController($app);
});

$app['setup.controller'] = $app->share(function() use ($app) {
    return new Controller\SetupController($app);
});

$app->get('/setup', "setup.controller:setupAction");


use Symfony\Component\Debug\ErrorHandler;
use Symfony\Component\Debug\ExceptionHandler;

ExceptionHandler::register();
ErrorHandler::register();


$app->error(function (\Exception $e, $code) use($app) {
    if($e instanceof \Areanet\PIM\Classes\Exceptions\FileNotFoundException){
        return new \Symfony\Component\HttpFoundation\Response($e->getMessage(), 404, array('X-Status-Code' => 404));
    }else{

        if($app['debug']){
            if($e instanceof \Areanet\PIM\Classes\Exceptions\File\FileExistsException){
                return $app->json(array("message" => $e->getMessage(), "type" => get_class($e), 'file_id' => $e->fileId, 'debug' => $e->getTrace()), $code);
            }else{
                return $app->json(array("message" => $e->getMessage(), "type" => get_class($e), 'debug' => $e->getTrace()), $code);
            }

        }else{
            if($e instanceof \Areanet\PIM\Classes\Exceptions\File\FileExistsException){
                return $app->json(array("message" => $e->getMessage(), "type" => get_class($e), 'file_id' => $e->fileId), $code);
            }else{
                return $app->json(array("message" => $e->getMessage(), "type" => get_class($e)), $code);
            }

        }
    }

});

$app->get(Config\Adapter::getConfig()->FRONTEND_URL, 'ui.controller:showAction');

$app->mount('/api', new \Areanet\PIM\Classes\Controller\Provider\Base\ApiControllerProvider('/api'));
$app->mount('/auth', new \Areanet\PIM\Classes\Controller\Provider\Base\AuthControllerProvider('/auth'));
$app->mount('/file', new \Areanet\PIM\Classes\Controller\Provider\Base\FileControllerProvider('/file'));
$app->mount('/push', new \Areanet\PIM\Classes\Controller\Provider\Base\PushControllerProvider('/push'));

$app->run();

