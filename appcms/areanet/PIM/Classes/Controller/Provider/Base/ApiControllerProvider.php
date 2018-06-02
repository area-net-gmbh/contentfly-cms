<?php
namespace Areanet\PIM\Classes\Controller\Provider\Base;

use Areanet\PIM\Classes\Config;
use Areanet\PIM\Classes\Controller\Provider\BaseControllerProvider;
use Areanet\PIM\Controller\ApiController;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ApiControllerProvider extends BaseControllerProvider
{


    public function connect(Application $app)
    {
        $app['api.controller'] = function($app) {
            return new ApiController($app);
        };

        $this->setUpMiddleware($app);

        $controllers = $app['controllers_factory'];

        $checkAuth = function (Request $request, Application $app) {
            if (!$this->checkToken($request, $app)) {
                throw new AccessDeniedHttpException('Zugriff verweigert', null, 401);
            }
        };

        $controllers->post('/login',  "api.controller:loginAction");
        $controllers->post('/logout', "api.controller:logoutAction")->before($checkAuth);
        $controllers->post('/single', "api.controller:singleAction")->before($checkAuth);
        $controllers->post('/list',   "api.controller:listAction")->before($checkAuth);
        $controllers->post('/tree',   "api.controller:treeAction")->before($checkAuth);
        $controllers->post('/all',   "api.controller:allAction")->before($checkAuth);
        $controllers->post('/mail', "api.controller:mailAction")->before($checkAuth);
        $controllers->post('/delete', "api.controller:deleteAction")->before($checkAuth);
        $controllers->post('/update', "api.controller:updateAction")->before($checkAuth);
        $controllers->post('/replace', "api.controller:replaceAction")->before($checkAuth);
        $controllers->post('/multiupdate', "api.controller:multiupdateAction")->before($checkAuth);
        $controllers->post('/insert', "api.controller:insertAction")->before($checkAuth);
        $controllers->post('/query', "api.controller:queryAction")->before($checkAuth);
        $controllers->post('/count', "api.controller:countAction")->before($checkAuth);
        $controllers->get('/schema', "api.controller:schemaAction")->before($checkAuth);
        $controllers->get('/config', "api.controller:configAction");


        return $controllers;
    }


}