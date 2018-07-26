<?php
namespace Areanet\PIM\Classes\Controller\Provider\Base;

use Areanet\PIM\Classes\Config;
use Areanet\PIM\Classes\Controller\Provider\BaseControllerProvider;
use Areanet\PIM\Controller\ApiController;
use Areanet\PIM\Controller\ExportController;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ExportControllerProvider extends BaseControllerProvider
{


    public function connect(Application $app)
    {
        $app['export.controller'] = function($app) {
            return new ExportController($app);
        };

        $this->setUpMiddleware($app);

        $controllers = $app['controllers_factory'];

        $checkAuth = function (Request $request, Application $app) {
            if (!$this->checkToken($request, $app)) {
                throw new AccessDeniedHttpException('Zugriff verweigert', null, 401);
            }
        };


        $controllers->post('/csv',   "export.controller:csvAction")->before($checkAuth);
        $controllers->post('/excel',   "export.controller:excelAction")->before($checkAuth);
        $controllers->post('/xml',   "export.controller:xmlAction")->before($checkAuth);
        return $controllers;
    }


}