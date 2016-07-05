<?php
namespace Areanet\PIM\Classes\Types;
use Areanet\PIM\Classes\Type;
use Areanet\PIM\Controller\ApiController;
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

    public function toDatabase(ApiController $controller, Base $object, $property, $value, $entityName, $schema)
    {
        $setter = 'set'.ucfirst($property);
        $getter = 'get'.ucfirst($property);

        if(strtoupper($value) == 'INC'){
            $oldValue = $object->$getter();
            $oldValue++;
            $object->$setter($oldValue);
        }elseif(strtoupper($value) == 'DEC'){
            $oldValue = $object->$getter();
            $oldValue--;
            $object->$setter($oldValue);
        }else{
            $object->$setter(intval($value));
        }

    }
}