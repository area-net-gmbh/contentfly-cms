<?php
namespace Areanet\Contentfly\Classes\Types;
use Areanet\Contentfly\Classes\Type;


class RteType extends Type
{
    public function getPriority()
    {
        return 10;
    }
    
    public function getAlias()
    {
        return 'rte';
    }

    public function getAnnotationFile()
    {
        return 'Rte';
    }

    public function doMatch($propertyAnnotations){
        if(!isset($propertyAnnotations['Areanet\\PIM\\Classes\\Annotations\\Rte'])) {
            return false;
        }

        return true;
    }

    public function processSchema($key, $defaultValue, $propertyAnnotations, $entityName){
        $schema                 = parent::processSchema($key, $defaultValue, $propertyAnnotations, $entityName);
        $propertyAnnotations    = $propertyAnnotations['Areanet\\PIM\\Classes\\Annotations\\Rte'];

        $schema['rteToolbar']   = $propertyAnnotations->toolbar;
        $schema['typeExtend']   = $propertyAnnotations->extend;
        $schema['dbType']       = "text";
        
        return $schema;
    }
}
