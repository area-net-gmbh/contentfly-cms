<?php
namespace Areanet\PIM\Classes\Types;
use Areanet\PIM\Classes\Type;


class JoinType extends Type
{
    public function getAlias()
    {
        return 'join';
    }

    public function getAnnotationFile()
    {
        return null;
    }

    public function doMatch($propertyAnnotations){

        if(!isset($propertyAnnotations['Doctrine\\ORM\\Mapping\\ManyToOne'])) {
            return false;
        }
        $annotations = $propertyAnnotations['Doctrine\\ORM\\Mapping\\ManyToOne'];

        return $annotations->targetEntity != 'Areanet\\PIM\Entity\\File\\';
    }


    public function processSchema($key, $defaultValue, $propertyAnnotations){
        $schema             = parent::processSchema($key, $defaultValue, $propertyAnnotations);
        $annotations        = $propertyAnnotations['Doctrine\\ORM\\Mapping\\ManyToOne'];

        $schema['accept']   = $annotations->targetEntity;
        $schema['multiple'] = false;

        return $schema;
    }
}