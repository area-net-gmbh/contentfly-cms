<?php
require_once __DIR__.'/bootstrap.php';

use Areanet\PIM\Controller;
use \Areanet\PIM\Classes\Config;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\Debug\ErrorHandler;
use Symfony\Component\Debug\ExceptionHandler;
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

if(Config\Adapter::getConfig()->APP_FORCE_SSL){
    if ( !(isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' ||
            $_SERVER['HTTPS'] == 1) ||
        isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
        $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'))
    {
        $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: ' . $redirect);
        exit();
    }

    header("Strict-Transport-Security:max-age=63072000");
}

header("Content-Security-Policy: ".Config\Adapter::getConfig()->APP_CS_POLICY);
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");

ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);

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


ExceptionHandler::register();
ErrorHandler::register();


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

        if(Config\Adapter::getConfig()->APP_DEBUG){
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
                return $app->json(array("message" => $e->getMessage(), "type" => get_class($e)), $e->getCode() ? $e->getCode() : 404);
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
$app->get(Config\Adapter::getConfig()->APP_INSTALLER_URL, 'install.controller:indexAction');
$app->post(Config\Adapter::getConfig()->APP_INSTALLER_URL, 'install.controller:submitAction');

$app->mount('/api', new \Areanet\PIM\Classes\Controller\Provider\Base\ApiControllerProvider('/api'));
$app->mount('/auth', new \Areanet\PIM\Classes\Controller\Provider\Base\AuthControllerProvider('/auth'));
$app->mount('/file', new \Areanet\PIM\Classes\Controller\Provider\Base\FileControllerProvider('/file'));
$app->mount('/push', new \Areanet\PIM\Classes\Controller\Provider\Base\PushControllerProvider('/push'));
$app->mount('/system', new \Areanet\PIM\Classes\Controller\Provider\Base\SystemControllerProvider('/system'));

$app->run();


