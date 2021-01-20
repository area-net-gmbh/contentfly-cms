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
    protected $extensions     = array();
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

        if(substr($templatePath, 0, 8) != '/plugins'){
            $templatePath =  "/custom/Frontend/contentfly-ui/views/$templatePath";
        }

        $this->blocks[$key][] = $templatePath;

    }


    /**
     * @param $routeName Name der Route aus app.routes.js
     * @param null $controller
     * @param null $template
     * @param array $stateParams
     */
    public function extendRoute($routeName, $controller = null, $template = null, $stateParams = array()){
        if(empty($controller) && empty($template)) return;

        if($template){
            if(substr($template, 0, 8) != '/plugins'){
                $template =  "/custom/Frontend/contentfly-ui/views/$template";
            }
        }

        $this->extensions[$routeName] = isset($this->extensions[$routeName]) ? $this->extensions[$routeName] : array();
        $this->extensions[$routeName][] = array(
            'controller'    => $controller,
            'template'      => $template,
            'stateParams'   => $stateParams
        );
    }

    /**
     * @return array
     */
    public function getExtendedRoutes()
    {
        return $this->extensions;
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

    public function addAngularModule($moduleName, $path){

        if(substr($path, 0, 8) != '/plugins'){
            $path =  "/custom/Frontend/contentfly-ui/scripts/$path";
        }

        $this->angularModules[$moduleName] = $path;
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
        if(substr($path, 0, 8) != '/plugins'){
            $path =  "/custom/Frontend/contentfly-ui/scripts/$path";
        }

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

        if(substr($path, 0, 8) != '/plugins'){
            $path =  "/custom/Frontend/contentfly-ui/styles/$path";
        }

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