<?php
namespace Areanet\PIM\Classes\Types;

use Areanet\PIM\Classes\Type;


class PasswordType extends Type
{
    public function getPriority()
    {
        return 10;
    }
    
    public function getAlias()
    {
        return 'password';
    }

    public function getAnnotationFile()
    {
        return 'Password';
    }

    public function doMatch($propertyAnnotations){
        if(!isset($propertyAnnotations['Areanet\\PIM\\Classes\\Annotations\\Password'])) {
            return false;
        }

        return true;
    }

    public function processSchema($key, $defaultValue, $propertyAnnotations){
        $schema                 = parent::processSchema($key, $defaultValue, $propertyAnnotations);
        $propertyAnnotations    = $propertyAnnotations['Areanet\\PIM\\Classes\\Annotations\\Password'];

        $schema['dbtype']   = "string";

        return $schema;
    }
}