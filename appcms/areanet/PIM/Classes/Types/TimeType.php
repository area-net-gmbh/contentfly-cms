<?php
namespace Areanet\PIM\Classes\Types;
use Areanet\PIM\Classes\Annotations\Time;
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

    public function processSchema($key, $defaultValue, $propertyAnnotations){
        $schema                 = parent::processSchema($key, $defaultValue, $propertyAnnotations);
        $propertyAnnotations    = $propertyAnnotations['Areanet\\PIM\\Classes\\Annotations\\Time'];

        $schema['format'] = $propertyAnnotations->format ? $propertyAnnotations->format : Time::DEFAULT_FORMAT;
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

    public function toDatabase(ApiController $controller, Base $object, $property, $value, $entityName, $schema, $user)
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