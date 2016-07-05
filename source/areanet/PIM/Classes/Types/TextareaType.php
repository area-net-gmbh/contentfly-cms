<?php
namespace Areanet\PIM\Classes\Types;
use Areanet\PIM\Classes\Type;


class TextareaType extends Type
{
    public function getAlias()
    {
        return 'textarea';
    }

    public function getAnnotationFile()
    {
        return 'Textarea';
    }

    public function doMatch($propertyAnnotations){
        if(!isset($propertyAnnotations['Areanet\\PIM\\Classes\\Annotations\\Textarea'])) {
            return false;
        }

        return true;
    }

    public function processSchema($key, $defaultValue, $propertyAnnotations)
    {
        $schema = parent::processSchema($key, $defaultValue, $propertyAnnotations);
        $propertyAnnotations = $propertyAnnotations['Areanet\\PIM\\Classes\\Annotations\\Textarea'];

        return $schema;
    }
}