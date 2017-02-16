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

    public function toDatabase(ApiController $controller, Base $object, $property, $value, $entityName, $schema, $user)
    {
       
        $setter = 'set'.ucfirst($property);
        $getter = 'get'.ucfirst($property);

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