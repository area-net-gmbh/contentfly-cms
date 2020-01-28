<?php
namespace Areanet\Contentfly\Classes\Controller\Provider\Base;

use Areanet\Contentfly\Classes\Config;
use Areanet\Contentfly\Classes\Controller\Provider\BaseControllerProvider;
use Areanet\Contentfly\Controller\ApiController;
use Areanet\Contentfly\Controller\SystemController;
use Doctrine\DBAL\Exception\InvalidFieldNameException;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class SystemControllerProvider extends BaseControllerProvider
{


    public function connect(Application $app)
    {
        $app['system.controller'] = function($app) {
            return new SystemController($app);
        };

        $this->setUpMiddleware($app);


        $controllers = $app['controllers_factory'];

        $checkAuth = function (Request $request, Application $app) {
            try {
                if (!$this->checkToken($request, $app)) {
                    throw new AccessDeniedHttpException('Zugriff verweigert', null, 401);
                }
                if (!$app['auth.user']->getIsAdmin()) {
                    throw new AccessDeniedHttpException('Zugriff nur für Administratoren gestattet', null, 401);
                }
            }catch(InvalidFieldNameException $e){

                if($request->get('method') == 'validateORM' || $request->get('method') == 'updateDatabase'){
                    
                }else{
                    throw $e;
                }
            }
        };

        $controllers->post('/do', "system.controller:doAction")->before($checkAuth);


        return $controllers;
    }


}