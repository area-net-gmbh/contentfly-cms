<?php
namespace Areanet\Contentfly\Classes\Manager;

use Areanet\Contentfly\Classes\Exceptions\ContentflyException;
use Areanet\Contentfly\Classes\Manager;
use Areanet\Contentfly\Classes\Messages;
use Areanet\Contentfly\Classes\Plugin;
use Areanet\Contentfly\Classes\Type;
use Silex\Application;

class TypeManager extends Manager
{

    const CUSTOM    = 'custom';
    const PLUGINS   = 'plugins';
    const SYSTEM    = 'system';

    protected $types = array();

    public function registerType(Type $type){

        if($type instanceof Type\PluginType){
            throw new ContentflyException(Messages::contentfly_general_use_plugin_register_method, get_class($type));
        }

        if($type->getAnnotationFile()){
            if($type instanceof Type\CustomType){
                \Doctrine\Common\Annotations\AnnotationRegistry::registerFile(ROOT_DIR.'/custom/Classes/Annotations/'.$type->getAnnotationFile().'.php');
            }else{
                \Doctrine\Common\Annotations\AnnotationRegistry::registerFile(ROOT_DIR.'/lib/contentfly/Classes/Annotations/'.$type->getAnnotationFile().'.php');
            }
        }

        $this->types[$type->getAlias()] = $type;
    }

    public function registerPluginType(Type\PluginType $type, Plugin $plugin){

        $type->setPluginKey($plugin->getKey());
        if($type->getAnnotationFile()) {
            \Doctrine\Common\Annotations\AnnotationRegistry::registerFile(ROOT_DIR . '/plugins/' . $plugin->getKey() . '/Annotations/' . $type->getAnnotationFile() . '.php');
        }

        $this->types[$type->getAlias()] = $type;
    }


    public function getTypes($mode = null){

        if($mode === null){
            return $this->types;
        }

        $data = array();
        foreach($this->types as $alias => $type){
            if($mode == self::SYSTEM && ($type instanceof Type\CustomType || $type instanceof Type\PluginType)) continue;
            if($mode == self::CUSTOM && !($type instanceof Type\CustomType)) continue;
            if($mode == self::PLUGINS && !($type instanceof Type\PluginType)) continue;
            $data[$alias] = $type;
        }

        return $data;
    }

    public function getCustomTypes(){
        return $this->getTypes(self::CUSTOM);
    }

    public function getSystemTypes(){
        return $this->getTypes(self::SYSTEM);
    }

    public function getPluginTypes(){
        return $this->getTypes(self::PLUGINS);
    }


    public function getType($alias){
        if(!isset($this->types[$alias])){
            return null;
        }

        return $this->types[$alias];
    }
}