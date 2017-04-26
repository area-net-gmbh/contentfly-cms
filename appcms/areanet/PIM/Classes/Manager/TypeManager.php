<?php
namespace Areanet\PIM\Classes\Manager;

use Areanet\PIM\Classes\Manager;
use Areanet\PIM\Classes\Type;
use Silex\Application;

class TypeManager extends Manager
{
    const SYSTEM = 'system';
    const CUSTOM = 'custom';

    protected $types = array();

    public function registerType(Type $type){

        if($type->getAnnotationFile()){
            if($type instanceof Type\CustomType){
                \Doctrine\Common\Annotations\AnnotationRegistry::registerFile(ROOT_DIR.'/../custom/Classes/Annotations/'.$type->getAnnotationFile().'.php');
            }else{
                \Doctrine\Common\Annotations\AnnotationRegistry::registerFile(ROOT_DIR.'/areanet/PIM/Classes/Annotations/'.$type->getAnnotationFile().'.php');
            }
        }

        $this->types[$type->getAlias()] = $type;
    }


    public function getTypes($mode = null){

        if($mode == null){
            return $this->types;
        }

        $data = array();
        foreach($this->types as $alias => $type){
            if($mode == self::SYSTEM && $type instanceof Type\CustomType) continue;
            if($mode == self::CUSTOM && !($type instanceof Type\CustomType)) continue;
            $data[$alias] = $type;
        }

        return $data;
    }

    public function getSystemTypes(){
        return $this->getTypes(self::SYSTEM);
    }

    public function getCustomTypes(){
        return $this->getTypes(self::CUSTOM);
    }


    public function getType($alias){
        if(!isset($this->types[$alias])){
            return null;
        }

        return $this->types[$alias];
    }
}