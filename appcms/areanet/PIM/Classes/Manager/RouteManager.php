<?php
namespace Areanet\PIM\Classes\Manager;

use Areanet\PIM\Classes\Controller\Provider\Base\CustomControllerProvider;
use Areanet\PIM\Classes\Manager;

class RouteManager extends Manager
{
    protected $controllerProviders = array();

    /**
     * @param String $mountPath Mount-Path
     * @param String $controllerName Controller Name
     * @return CustomControllerProvider
     */
    public function mount($mountPath, $controllerName)
    {
        $controllerProvider = new CustomControllerProvider($mountPath, $controllerName);

        $this->controllerProviders[$mountPath] = $controllerProvider;

        return $controllerProvider;
    }

    public function bindRoutes(){
        foreach($this->controllerProviders as $mountPath => $controllerProvider){
            $this->app->mount($mountPath, $controllerProvider);
        }
    }
}