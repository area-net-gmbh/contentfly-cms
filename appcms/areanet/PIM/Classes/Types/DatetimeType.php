<?php
namespace Areanet\PIM\Classes\Types;
use Areanet\PIM\Classes\Api;
use Areanet\PIM\Classes\Type;
use Areanet\PIM\Entity\Base;


class DatetimeType extends Type
{
    public function getAlias()
    {
        return 'datetime';
    }

    public function getAnnotationFile()
    {
        return null;
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

    public function toDatabase(Api $api, Base $object, $property, $value, $entityName, $schema, $user)
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
