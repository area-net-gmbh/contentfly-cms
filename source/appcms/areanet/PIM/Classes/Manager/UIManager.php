<?php
/**
 * Created by PhpStorm.
 * User: ms
 * Date: 19.07.16
 * Time: 17:09
 */

namespace Areanet\PIM\Classes\Manager;


use Areanet\PIM\Classes\Manager;
use Silex\Application;

class UIManager extends Manager
{

    protected $blocks         = array();
    protected $routes         = array();
    protected $jsFiles        = array();
    protected $angularModules = array();
    protected $cssFiles       = array();

    /**
     * @param String $key BLOCK_KEY
     * @param String $templatePath Template file path, relative to /custom/ui/views/
     */
    public function addBlock($key, $templatePath)
    {
        if(!isset($this->blocks[$key])){
            $this->blocks[$key] = array();
        }

        $this->blocks[$key][] = $templatePath;

    }

    /**
     * @return array
     */
    public function getBlocks()
    {
        return $this->blocks;
    }

    /**
     * @param String $route Route Name
     * @param String $templatePath Template file path, relative to /custom/ui/views/
     * @param String $controllerName Controller Name
     * @param Boolean $secure Secure controller true/false
     */
    public function addRoute($route, $templateName, $controllerName, $secure = true){
        $this->routes[$route] = array(
            'templateName'      => $templateName,
            'controllerName'    => $controllerName,
            'secure'            => $secure
        );
    }

    /**
     * @return array
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    public function addAngularModule($moduleName){
        $this->angularModules[] = $moduleName;
    }

    /**
     * @return array
     */
    public function getAngularModules()
    {
        return $this->angularModules;
    }

    /**
     * @param String $path Javascript file path, relative to /custom/ui/scripts/
     */
    public function addJSFile($path){
        $this->jsFiles[] = $path;
    }

    /**
     * @return array
     */
    public function getJSFiles()
    {
        return $this->jsFiles;
    }

    /**
     * @param String $path CSS file path, relative to /custom/ui/styles/
     * @return
     */
    public function addCSSFile($path){
        $this->cssFiles[] = $path;
    }

    /**
     * @return array
     */
    public function getCSSFiles()
    {
        return $this->cssFiles;
    }

    
}