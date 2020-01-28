<?php
namespace Areanet\Contentfly\Classes\Controller\Provider\Base;

use Areanet\Contentfly\Classes\Controller\Provider\BaseControllerProvider;
use Areanet\Contentfly\Controller\ApiController;
use Areanet\Contentfly\Controller\AuthController;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AuthControllerProvider extends BaseControllerProvider
{


    public function connect(Application $app)
    {
        $app['auth.controller'] = function($app) {
            return new AuthController($app);
        };

        $this->setUpMiddleware($app);


        $controllers = $app['controllers_factory'];

        $checkAuth = function (Request $request, Application $app) {
            if (!$this->checkToken($request, $app)) {
                throw new AccessDeniedHttpException('Access Denied');
            }
        };

        $controllers->post('/login',  "auth.controller:loginAction");
        $controllers->get('/logout', "auth.controller:logoutAction")->before($checkAuth);

        return $controllers;
    }


}