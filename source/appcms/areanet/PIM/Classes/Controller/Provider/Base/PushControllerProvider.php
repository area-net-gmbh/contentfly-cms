<?php
namespace Areanet\PIM\Classes\Controller\Provider\Base;

use Areanet\PIM\Classes\Controller\Provider\BaseControllerProvider;
use Areanet\PIM\Controller\ApiController;
use Areanet\PIM\Controller\PushController;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PushControllerProvider extends BaseControllerProvider
{


    public function connect(Application $app)
    {
        $app['push.controller'] = $app->share(function() use ($app) {
            return new PushController($app);
        });

        $this->setUpMiddleware($app);


        $controllers = $app['controllers_factory'];

        $checkAuth = function (Request $request, Application $app) {
            if (!$this->checkToken($request, $app)) {
                throw new AccessDeniedHttpException('Access Denied');
            }
        };

        $controllers->post('/settoken',  "push.controller:setTokenAction")->before($checkAuth);

        return $controllers;
    }


}