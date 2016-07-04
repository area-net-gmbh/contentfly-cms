<?php
namespace Areanet\PIM\Classes\Types;
use Areanet\PIM\Classes\Type;


class MultifileType extends Type
{
    public function getAlias()
    {
        return 'multifile';
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

        return $annotations->targetEntity == 'Areanet\PIM\Entity\File';
    }


    public function processSchema($key, $defaultValue, $propertyAnnotations){
        $schema             = parent::processSchema($key, $defaultValue, $propertyAnnotations);
        $schema['multipe']  = false;
        $schema['accept']   = '*';
        $schema['dbtype']   = 'integer';

        if(isset($propertyAnnotations['Areanet\\PIM\\Classes\\Annotations\\Config'])){
            $annotations = $propertyAnnotations['Areanet\\PIM\\Classes\\Annotations\\Config'];

            if($annotations->accept){
                $schema['accept'] = $annotations->accept;
            }
        }

        return $schema;
    }
}