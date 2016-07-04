<?php
namespace Areanet\PIM\Classes\Types;
use Areanet\PIM\Classes\Type;


class DecimalType extends Type
{
    public function getAlias()
    {
        return 'decimal';
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

        return ($annotation->type == 'decimal');
    }
}