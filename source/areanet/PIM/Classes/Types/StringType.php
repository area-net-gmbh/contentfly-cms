<?php
namespace Areanet\PIM\Classes\Types;
use Areanet\PIM\Classes\Type;


class StringType extends Type
{
    public function getAlias()
    {
        return 'string';
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

        return ($annotation->type == 'string');
    }
}