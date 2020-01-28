<?php
namespace Areanet\Contentfly\Classes\Controller\Provider\Base;

use Areanet\Contentfly\Classes\Config;
use Areanet\Contentfly\Classes\Controller\Provider\BaseControllerProvider;
use Areanet\Contentfly\Controller\ApiController;
use Areanet\Contentfly\Controller\ExportController;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ExportControllerProvider extends BaseControllerProvider
{


    public function connect(Application $app)
    {
        $app['export.controller'] = function($app) {
            $controller = Config\Adapter::getConfig()->APP_EXPORT_CONTROLLER;
            if(!class_exists($controller)){
                throw new \Exception('Export-Controller '.$controller.' konnt nicht geladen werden.',  500);
            }

            return new $controller($app);
        };

        $this->setUpMiddleware($app);

        $controllers = $app['controllers_factory'];

        $checkAuth = function (Request $request, Application $app) {
            if (!$this->checkToken($request, $app)) {
                throw new AccessDeniedHttpException('Zugriff verweigert', null, 401);
            }
        };

        foreach(Config\Adapter::getConfig()->APP_EXPORT_METHODS as $action => $label){

            $controllers->post('/'.$action,   'export.controller:'.$action.'Action')->before($checkAuth);
        }

        return $controllers;
    }


}