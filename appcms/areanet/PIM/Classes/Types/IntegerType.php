<?php
namespace Areanet\PIM\Classes\Types;
use Areanet\PIM\Classes\Api;
use Areanet\PIM\Classes\Type;
use Areanet\PIM\Controller\ApiController;
use Areanet\PIM\Entity\Base;


class IntegerType extends Type
{
    public function getAlias()
    {
        return 'integer';
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

        return ($annotation->type == 'integer' || $annotation->type == 'bigint' || $annotation->type == 'smallint');
    }

    public function toDatabase(Api $api, Base $object, $property, $value, $entityName, $schema, $user, $data = null)
    {
        $setter = 'set'.ucfirst($property);
        if(!is_array($value) && $value !== null){
            $object->$setter($value);
        }else{
            $object->$setter(null);
        }

    }
}
