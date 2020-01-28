<?php
namespace Areanet\Contentfly\Classes\Types;
use Areanet\Contentfly\Classes\Annotations\Datetime;
use Areanet\Contentfly\Classes\Api;
use Areanet\Contentfly\Classes\Type;
use Areanet\Contentfly\Entity\Base;


class DatetimeType extends Type
{
    public function getAlias()
    {
        return 'datetime';
    }

    public function getAnnotationFile()
    {
        return 'Datetime';
    }

    public function processSchema($key, $defaultValue, $propertyAnnotations, $entityName){
        $schema                 = parent::processSchema($key, $defaultValue, $propertyAnnotations, $entityName);
        $propertyAnnotations    = isset($propertyAnnotations['Areanet\\PIM\\Classes\\Annotations\\Datetime']) ? $propertyAnnotations['Areanet\\PIM\\Classes\\Annotations\\Datetime'] : null;

        $schema['format'] = $propertyAnnotations && $propertyAnnotations->format ? $propertyAnnotations->format : Datetime::DEFAULT_FORMAT;
        $schema['dbType'] = "datetime";

        return $schema;
    }

    public function doMatch($propertyAnnotations){

        if(!isset($propertyAnnotations['Doctrine\\ORM\\Mapping\\Column'])) {
            return false;
        }

        $annotation = $propertyAnnotations['Doctrine\\ORM\\Mapping\\Column'];

        return ($annotation->type == 'datetime');
    }

    public function fromDatabase(Base $object, $entityName, $property, $flatten = false, $level = 0, $propertiesToLoad = array())
    {
        $getter = 'get'.ucfirst($property);

        if(!$object->$getter() instanceof \DateTime){
            return null;
        }

        if ($object->$getter()->format('Y') == '-0001' || $object->$getter()->format('Y') == '0000') {
            return array(
                'LOCAL_TIME'    => null,
                'LOCAL'         => null,
                'ISO8601'       => null,
                'IMESTAMP'      => null
            );
        } else {
            return array(
                'LOCAL_TIME'    => $object->$getter()->format('d.m.Y H:i'),
                'LOCAL'         => $object->$getter()->format('d.m.Y'),
                'ISO8601'       => $object->$getter()->format(\DateTime::ISO8601),
                'TIMESTAMP'     => $object->$getter()->getTimestamp()
            );
        }
    }

    public function toDatabase(Api $api, Base $object, $property, $value, $entityName, $schema, $user, $data = null, $lang = null)
    {
       
        $setter = 'set'.ucfirst($property);

        if($value){
            if(is_array($value)){
                $keys = array_keys($value);

                $datetime = new \DateTime($value[$keys[0]]);
                $object->$setter($datetime);
            }else {
                $datetime = new \DateTime($value);
                $object->$setter($datetime);
            }
        }else{
            $object->$setter(null);
        }


    }
}
