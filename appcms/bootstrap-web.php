<?php
require_once __DIR__.'/bootstrap.php';

use Areanet\PIM\Controller;
use \Areanet\PIM\Classes\Config;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\Debug\ErrorHandler;
use Symfony\Component\HttpFoundation\Request;

$app['ui.controller'] = function() use ($app) {
    return new Controller\UiController($app);
};

$app['install.controller'] = function() use ($app) {
    return new Controller\InstallController($app);
};

$app['request'] = function()use ($app){
    return $app['request_stack'] ? $app['request_stack']->getCurrentRequest() : null;
};



header("Content-Security-Policy: ".Config\Adapter::getConfig()->APP_CS_POLICY);
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");

if(Config\Adapter::getConfig()->APP_HTTP_AUTH_USER) {
    if(!isset($_SERVER['PHP_AUTH_USER'])) {
        $authString = empty($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] : $_SERVER['HTTP_AUTHORIZATION'];
        list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':', base64_decode(substr($authString, 6)));
    }

    if (empty($_SERVER['PHP_AUTH_USER'])) {
        header('WWW-Authenticate: Basic realm="APP-CMS Authentification"');
        header('HTTP/1.0 401 Unauthorized');
        exit;
    } else {
        if ($_SERVER['PHP_AUTH_USER'] != Config\Adapter::getConfig()->APP_HTTP_AUTH_USER && $_SERVER['PHP_AUTH_PW'] != Config\Adapter::getConfig()->APP_HTTP_AUTH_PASS) {
            header('WWW-Authenticate: Basic realm="APP-CMS Authentification"');
            header('HTTP/1.0 401 Unauthorized');
            exit;
        }
    }
}

ErrorHandler::register();

$handler = Symfony\Component\Debug\ExceptionHandler::register($app['debug']);
$handler->setHandler(function ($exception) use ($app) {

    // Create an ExceptionEvent with all the informations needed.
    $event = new Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent(
        $app,
        $app['request'],
        Symfony\Component\HttpKernel\HttpKernelInterface::MASTER_REQUEST,
        $exception
    );

    // Hey Silex ! We have something for you, can you handle it with your exception handler ?
    $app['dispatcher']->dispatch(Symfony\Component\HttpKernel\KernelEvents::EXCEPTION, $event);

    // And now, just display the response ;)
    $response = $event->getResponse();
    $response->sendHeaders();
    $response->sendContent();
});


$app->error(function (\Exception $e, Request $request, $code) use($app) {

    if($e instanceof \Areanet\PIM\Classes\Exceptions\FileNotFoundException){
        return new \Symfony\Component\HttpFoundation\Response($e->getMessage(), 404, array('X-Status-Code' => 404));
    }else{
        $accept = AcceptHeader::fromString($app["request"]->headers->get('Content-Type'));

        if(!$accept->has('application/json') && !$accept->has('multipart/form-data')){
            if(Config\Adapter::getConfig()->APP_DEBUG){

                return new \Symfony\Component\HttpFoundation\Response('<h1>'.$e->getMessage().'</h1><pre>'.$e->getTraceAsString().'</pre>', 500);
            }else {
                return $app->redirect('/');
            }
        }

        $data= array();
        if($e instanceof \Areanet\PIM\Classes\Exceptions\ContentflyException){
            $data = array('message' => $e->getMessage(), 'type' => get_class($e), 'message_value' => $e->getValue(), 'status' => $e->getCode());
        }elseif($e instanceof \Areanet\PIM\Classes\Exceptions\ContentflyI18NException){
            $data = array('message' => $e->getMessage(), 'type' => get_class($e), 'message_entity' => $e->getEntity(), 'message_lang' => $e->getLang(), 'status' => $e->getCode());
        }else{
            $data = array("message" => $e->getMessage(), "type" => get_class($e), $e->getCode() ? $e->getCode() : 500);
        }

        if(Config\Adapter::getConfig()->APP_DEBUG){
            $data['debug'] = $e->getTrace();
        }

        return $app->json($data, $e->getCode() ? $e->getCode() : 500);
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
$app->get(Config\Adapter::getConfig()->APP_INSTALLER_URL, 'install.controller:indexAction');
$app->post(Config\Adapter::getConfig()->APP_INSTALLER_URL, 'install.controller:submitAction');

$app->mount('/api', new \Areanet\PIM\Classes\Controller\Provider\Base\ApiControllerProvider('/api'));
$app->mount('/export', new \Areanet\PIM\Classes\Controller\Provider\Base\ExportControllerProvider('/export'));
$app->mount('/auth', new \Areanet\PIM\Classes\Controller\Provider\Base\AuthControllerProvider('/auth'));
$app->mount('/file', new \Areanet\PIM\Classes\Controller\Provider\Base\FileControllerProvider('/file'));
$app->mount('/system', new \Areanet\PIM\Classes\Controller\Provider\Base\SystemControllerProvider('/system'));

$app->run();


