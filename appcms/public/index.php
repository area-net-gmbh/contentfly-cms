<?php
if(!file_exists(__DIR__.'/../../custom/config.php')){
    header('Location: install.php');
    exit;
}

require_once __DIR__.'/../bootstrap.php';

use Areanet\PIM\Controller;
use \Areanet\PIM\Classes\Config;
use Symfony\Component\HttpFoundation\AcceptHeader;

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
        $accept = AcceptHeader::fromString($app["request"]->headers->get('Content-Type'));
        if(!$accept->has('application/json')){
            if($app['debug']){
                die('<h1>Fehler '.$e->getCode().'</h1><h2>'.$e->getMessage().'</h2><pre>'.$e->getTraceAsString().'</pre>');
            }else {
                return $app->redirect('/');
            }
        }

        if($app['debug']){
            if($e instanceof \Areanet\PIM\Classes\Exceptions\File\FileExistsException){
                return $app->json(array("message" => $e->getMessage(), "type" => get_class($e), 'file_id' => $e->fileId, 'debug' => $e->getTrace()), $e->getCode() ? $e->getCode() : 500);
            }elseif($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                return $app->json(array("message" => $e->getMessage(), "type" => get_class($e), 'debug' => $e->getTrace()), $e->getCode() ? $e->getCode() : 404);
            }else{
                return $app->json(array("message" => $e->getMessage(), "type" => get_class($e), 'debug' => $e->getTrace()), $e->getCode() ? $e->getCode() : 500);
            }

        }else{
            if($e instanceof \Areanet\PIM\Classes\Exceptions\File\FileExistsException){
                return $app->json(array("message" => $e->getMessage(), "type" => get_class($e), 'file_id' => $e->fileId),  $e->getCode() ? $e->getCode() : 500);
            }elseif($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                return $app->json(array("message" => $e->getMessage(), "type" => get_class($e), 'debug' => $e->getTrace()), $e->getCode() ? $e->getCode() : 404);
            }else{
                return $app->json(array("message" => $e->getMessage(), "type" => get_class($e)),  $e->getCode() ? $e->getCode() : 500);
            }

        }
    }

});

if(Config\Adapter::getConfig()->APP_ALLOW_ORIGIN){

    $app->after(function (\Symfony\Component\HttpFoundation\Request $request, \Symfony\Component\HttpFoundation\Response $response) {
        $response->headers->set('Access-Control-Allow-Origin', Config\Adapter::getConfig()->APP_ALLOW_ORIGIN);
        $response->headers->set('Access-Control-Allow-Credentials', Config\Adapter::getConfig()->APP_ALLOW_CREDENTIALS);

        $response->headers->set('Access-Control-Allow-Headers', Config\Adapter::getConfig()->APP_ALLOW_HEADERS);
        $response->headers->set('Access-Control-Allow-Methods', Config\Adapter::getConfig()->APP_ALLOW_METHODS);
        $response->headers->set('Access-Control-Max-Age', Config\Adapter::getConfig()->APP_MAX_AGE);

    });

    $app->options("{anything}", function () {
        return new \Symfony\Component\HttpFoundation\JsonResponse(null, 204);
    })->assert("anything", ".*");
}

$app->get(Config\Adapter::getConfig()->FRONTEND_URL, 'ui.controller:showAction');

$app->mount('/api', new \Areanet\PIM\Classes\Controller\Provider\Base\ApiControllerProvider('/api'));
$app->mount('/auth', new \Areanet\PIM\Classes\Controller\Provider\Base\AuthControllerProvider('/auth'));
$app->mount('/file', new \Areanet\PIM\Classes\Controller\Provider\Base\FileControllerProvider('/file'));
$app->mount('/push', new \Areanet\PIM\Classes\Controller\Provider\Base\PushControllerProvider('/push'));
$app->mount('/system', new \Areanet\PIM\Classes\Controller\Provider\Base\SystemControllerProvider('/system'));

$app->run();

