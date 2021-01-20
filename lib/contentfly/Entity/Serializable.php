<?php
namespace Areanet\PIM\Entity;

use Areanet\PIM\Classes\Config\Adapter;
use Areanet\PIM\Classes\Permission;
use Silex\Application;

abstract class Serializable implements \JsonSerializable{

    public function jsonSerialize()
    {
        return $this->toValueObject();
    }

    public function toValueObject(Application $app = null, $entityName = null, $flatten = false, $propertiesToLoad = array(), $level = 0, $forceLoadAll = false)
    {

        $result = new \stdClass();

        if($level > Adapter::getConfig()->DB_NESTED_LEVELS){
            $result->id = $this->getId();
            return $result;
        }

        $schema = null;
        $user   = null;

        if($app){
            $schema = $app['schema'];
        }

        if($level > 0 && !$forceLoadAll){
            $propertiesToLoad = $app['schema'][$entityName]['list'];

            if(!array_search('id', $propertiesToLoad)){
                $propertiesToLoad[0] = 'id';
            }

        }

        foreach ($this as $property => $value) {

            if(count($propertiesToLoad) && !in_array($property, $propertiesToLoad)){

                continue;
            }

            if(!$app || !isset($schema[$entityName]['properties'][$property])){
                continue;
            }
            $config = $schema[$entityName]['properties'][$property];

            $typeObject = $app['typeManager']->getType($config['type']);
            if(!$typeObject){
                throw new \Exception("toValueObject(): Unkown Type $typeObject for $property for entity $entityName", 500);
            }
            
            $result->$property = $typeObject->fromDatabase($this, $entityName, $property, $flatten, $level, $propertiesToLoad);

        }
        
        return $result;
    }
}