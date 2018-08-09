<?php
namespace Areanet\PIM\Classes;


use Silex\Application;

abstract class Plugin
{
    /** @var Application */
    protected $app;

    protected $key          = null;
    protected $namespace    = null;
    protected $options      = null;
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

    final public function getEntities(){
        if(!$this->doUseORM){
           return array();
        }

        $entities = array();

        $entityFolder = ROOT_DIR.'/../plugins/'.$this->key.'/Entity';

        foreach (new \DirectoryIterator($entityFolder) as $fileInfo) {
            if($fileInfo->isDot() || $fileInfo->getExtension() != 'php') continue;
            if(substr($fileInfo->getBasename('.php'), 0, 1) == '.') continue;

            $entities[] = $this->getNamespace().'\\Entity\\'.$fileInfo->getBasename('.php');
        }

        return $entities;
    }

    final public function getFrontendPath(){
        return '/plugins/'.$this->getKey();
    }

    final public function getNamespace(){
        return $this->namespace;
    }

    final public function getKey(){
        return $this->key;
    }

    public function init(){

    }

    private function initComposer(){
        if(file_exists(ROOT_DIR.'/../plugins/'.$this->key.'/vendor/autoload.php')){
            require_once ROOT_DIR.'/../plugins/'.$this->key.'/vendor/autoload.php';
        }
    }

    private function initORM(){
        $ormConfig  = $this->app['orm.em']->getConfiguration();
        if(!is_dir(ROOT_DIR.'/../plugins/'.$this->key.'/Entity')){
            mkdir(ROOT_DIR.'/../plugins/'.$this->key.'/Entity');
        }
        $driver     = $ormConfig->newDefaultAnnotationDriver(array(ROOT_DIR.'/../plugins/'.$this->getKey().'/Entity'), false);
        $ormConfig->getMetadataDriverImpl()->addDriver($driver, $this->getNamespace().'\\Entity');
    }

    final protected function useFrontend(){
        $helper = new Helper();
        if(!is_dir(ROOT_DIR.'/../plugins/'.$this->key.'/Frontend')){
            mkdir(ROOT_DIR.'/../plugins/'.$this->key.'/Frontend');
        }
        $helper->createSymlink(ROOT_DIR.'/public/plugins/', $this->getKey(), '../../../plugins/'.$this->getKey().'/Frontend');
    }

    final protected function useORM(){
        $this->doUseORM = true;
        $this->initORM();
    }
}