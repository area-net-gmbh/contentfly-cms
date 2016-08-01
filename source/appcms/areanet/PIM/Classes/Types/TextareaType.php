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
        if(isset($propertyAnnotations['Areanet\\PIM\\Classes\\Annotations\\Textarea'])) {
            return true;
        }

        if(!isset($propertyAnnotations['Doctrine\\ORM\\Mapping\\Column'])) {
            return false;
        }

        $annotation = $propertyAnnotations['Doctrine\\ORM\\Mapping\\Column'];

        return ($annotation->type == 'text');
    }

    public function processSchema($key, $defaultValue, $propertyAnnotations)
    {
        $schema = parent::processSchema($key, $defaultValue, $propertyAnnotations);

        return $schema;
    }
}