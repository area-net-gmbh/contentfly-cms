<?php
namespace Areanet\Contentfly\Classes;


use Areanet\Contentfly\Classes\Type\PluginType;
use Silex\Application;

abstract class Plugin
{
    /** @var Application */
    protected $app;

    /**
     * @var string Eindeutiger Plugin-Key
     */
    protected $key          = null;
    /**
     * @var string Namespace des Plugins, wird automatisch ermittelt
     */
    protected $namespace    = null;
    /**
     * @var mixed|null Plugin-Options, können beim Registrieren über den Plugin-Manager übergeben werden
     */
    protected $options      = null;
    /**
     * @var bool Plugin nutzt eigene Doctrine-Entitäten
     */
    private $doUseORM       = false;

    /**
     * Manager constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app, $options = null)
    {
        $classNameParts     = explode('\\', get_class($this));
        $this->key          = $classNameParts[1];
        $this->namespace    = $classNameParts[0].'\\'.$classNameParts[1];
        $this->options      = $options;
        $this->app          = $app;

        $this->initComposer();
        $this->init();
    }

    /**
     * @param string $name Name des UI-Blocks
     * @param string $path Pfad zur HTML, relativ zum Frontend-Ordner des Plugins
     */
    final protected function addBlock($name, $path){
        $this->app['uiManager']->addBlock($name, $this->getFrontendPath().$this->normalizePath($path));
    }

    /**
     * @param string $name Name des Angular Modules
     * @param string $path Pfad zur Javascript-Moduldatei, relativ zum Frontend-Ordner des Plugins
     */
    final protected function addAngularModule($name, $path){
        $this->app['uiManager']->addAngularModule($name, $this->getFrontendPath().$this->normalizePath($path));
    }

    /**
     * @param string $path Pfad zur Javascript-Datei, relativ zum Frontend-Ordner des Plugins
     */
    final protected function addJSFile($path){
        $this->app['uiManager']->addJSFile($this->getFrontendPath().$this->normalizePath($path));
    }

    /**
     * @param tring $path Pfad zur Stylesheet-Datei, relativ zum Frontend-Ordner des Plugins
     */
    final protected function addCSSFile($path){
        $this->app['uiManager']->addCSSFile($this->getFrontendPath().$this->normalizePath($path));
    }

    /**
     * @param string $route Name der Route, relativ zu 'Puginname/
     * @param string $templateName Pfad zur HTML, relativ zum Frontend-Ordner des Plugins
     * @param string $controllerName Name des Controllers (JS-Datei muss per  addJSFile eingebunden werden)
     * @param boolean $secure nur durch authentifizierten Benutzer über APPCMS-TOKEN aufrufbar
     */
    final protected function addRoute($route, $templateName, $controllerName, $secure = true){
        $this->app['uiManager']->addRoute($route, $this->getFrontendPath().$this->normalizePath($templateName), $controllerName, $secure);
    }

    /**
     * @param $routeName Name der Route aus app.routes.js
     * @param null $controller
     * @param null $template
     * @param array $stateParams
     */
    public function extendRoute($routeName, $controller = null, $template = null, $stateParams = array()){
        if(empty($controller) && empty($template)) return;

        $template = $template ? $this->getFrontendPath().$this->normalizePath($template) : null;

        $this->app['uiManager']->extendRoute($routeName, $controller, $template, $stateParams);
    }


    /**
     * @return string[]
     */
    final public function getEntities(){
        if(!$this->doUseORM){
           return array();
        }

        $entities = array();

        $entityFolder = ROOT_DIR.'/plugins/'.$this->key.'/Entity';

        foreach (new \DirectoryIterator($entityFolder) as $fileInfo) {
            if($fileInfo->isDot() || $fileInfo->getExtension() != 'php') continue;
            if(substr($fileInfo->getBasename('.php'), 0, 1) == '.') continue;

            $entities[] = $this->getNamespace().'\\Entity\\'.$fileInfo->getBasename('.php');
        }

        return $entities;
    }

    /**
     * @return string Über Symlink freigegebener Pfad zum Frontend-Ordner im Plugin
     */
    final public function getFrontendPath(){
        return '/plugins/'.$this->getKey();
    }

    /**
     * @return string
     */
    final public function getNamespace(){
        return $this->namespace;
    }

    /**
     * @return null
     */
    final public function getKey(){
        return $this->key;
    }

    /**
     * @param $path
     * @return string
     */
    private function normalizePath($path){
        return substr($path, 1, 0) == '/' ? $path : "/$path";
    }

    /**
     * Wird beim Initialisieren des Plugins aufgerufen, kann im Plugin überschrieben/angepasst werden
     */
    public function init(){

    }

    /**
     * Initialisieren der Composer-Funktion im Plugin
     */
    private function initComposer(){
        if(file_exists(ROOT_DIR.'/plugins/'.$this->key.'/vendor/autoload.php')){
            require_once ROOT_DIR.'/plugins/'.$this->key.'/vendor/autoload.php';
        }
    }

    /**
     * Initialisieren von eigenen Doctrine-Entitäten im Plugin
     */
    private function initORM(){
        $ormConfig  = $this->app['orm.em']->getConfiguration();
        if(!is_dir(ROOT_DIR.'/plugins/'.$this->key.'/Entity')){
            mkdir(ROOT_DIR.'/plugins/'.$this->key.'/Entity');
        }
        $driver     = $ormConfig->newDefaultAnnotationDriver(array(ROOT_DIR.'/plugins/'.$this->getKey().'/Entity'), false);
        $ormConfig->getMetadataDriverImpl()->addDriver($driver, $this->getNamespace().'\\Entity');
    }

    /**
     * @param PluginType $plugin Instanz des benutzerdefinierten Types
     */
    final protected function registerPluginType(PluginType $plugin){
        $this->app['typeManager']->registerPluginType($plugin, $this);
    }

    /**
     * Erstellt einen Symlink im Webroot 'appcms/public/plugins/KEY' => 'plugins/KEY/Frontend'
     */
    final protected function useFrontend(){
        //Deprecated
    }

    /**
     * Nutzung von eigenen Entitäten im Ordner 'Entity' des Plugins
     */
    final protected function useORM(){
        $this->doUseORM = true;
        $this->initORM();
    }
}