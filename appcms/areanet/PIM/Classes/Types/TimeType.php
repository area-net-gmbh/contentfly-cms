<?php
namespace Areanet\PIM\Classes\Types;
use Areanet\PIM\Classes\Annotations\Time;
use Areanet\PIM\Classes\Api;
use Areanet\PIM\Classes\Type;
use Areanet\PIM\Controller\ApiController;
use Areanet\PIM\Entity\Base;


class TimeType extends Type
{
    public function getAlias()
    {
        return 'time';
    }

    public function getAnnotationFile()
    {
        return 'Time';
    }

    public function processSchema($key, $defaultValue, $propertyAnnotations, $entityName){
        $schema                 = parent::processSchema($key, $defaultValue, $propertyAnnotations, $entityName);
        $propertyAnnotations    = isset($propertyAnnotations['Areanet\\PIM\\Classes\\Annotations\\Time']) ? $propertyAnnotations['Areanet\\PIM\\Classes\\Annotations\\Time'] : null;

        $schema['format'] = $propertyAnnotations && $propertyAnnotations->format ? $propertyAnnotations->format : Time::DEFAULT_FORMAT;
        $schema['dbType'] = "time";

        return $schema;
    }

    public function doMatch($propertyAnnotations){

        if(!isset($propertyAnnotations['Doctrine\\ORM\\Mapping\\Column'])) {
            return false;
        }

        $annotation = $propertyAnnotations['Doctrine\\ORM\\Mapping\\Column'];

        return ($annotation->type == 'time');
    }

    public function fromDatabase(Base $object, $entityName, $property, $flatten = false, $level = 0, $propertiesToLoad = array())
    {
        $getter = 'get'.ucfirst($property);
        
        if(!$object->$getter() instanceof \DateTime){
            return null;
        }

        $config = $this->app['schema'][ucfirst($entityName)]['properties'][$property];
        
        return $object->$getter()->format($config['format']);
    }


    public function toDatabase(Api $api, Base $object, $property, $value, $entityName, $schema, $user, $data = null, $lang = null)
    {

        $setter = 'set'.ucfirst($property);
        $getter = 'get'.ucfirst($property);

        if($value){
            if($value instanceof \DateTime){
                $object->$setter($value);
            }else{
                $time = explode(':', $value);

                $datetime = new \DateTime();
                $datetime->setTime($time[0], $time[1]);

                $object->$setter($datetime);
            }

        }else{
            $object->$setter(null);
        }


    }
}
