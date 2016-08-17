<?php
namespace Custom\Classes\Types;

use Areanet\PIM\Classes\Type\CustomType;

class TestType extends CustomType
{
    public function getAlias()
    {
        return 'test';
    }

    public function getAnnotationFile()
    {
        return 'Test';
    }

    public function doMatch($propertyAnnotations){
        if(!isset($propertyAnnotations['Custom\\Classes\\Annotations\\Test'])) {
            return false;
        }

        return true;
    }

    public function processSchema($key, $defaultValue, $propertyAnnotations){
        $schema                 = parent::processSchema($key, $defaultValue, $propertyAnnotations);
        $propertyAnnotations    = $propertyAnnotations['Custom\\Classes\\Annotations\\Test'];
     
        $schema['dbtype']       = "string";

        return $schema;
    }


}