<?php
namespace Areanet\PIM\Classes\Types;

use Areanet\PIM\Classes\Type;


class SelectType extends Type
{
    public function getAlias()
    {
        return 'select';
    }

    public function getAnnotationFile()
    {
        return 'Select';
    }

    public function doMatch($propertyAnnotations){
        if(!isset($propertyAnnotations['Areanet\\PIM\\Classes\\Annotations\\Select'])) {
            return false;
        }

        return true;
    }

    public function processSchema($key, $defaultValue, $propertyAnnotations){
        $schema                 = parent::processSchema($key, $defaultValue, $propertyAnnotations);
        $propertyAnnotations    = $propertyAnnotations['Areanet\\PIM\\Classes\\Annotations\\Select'];

        $options = explode(',', $propertyAnnotations->options);

        $optionsData = array();
        $count = 0;
        foreach($options as $option){
            $optionSplit = explode('=', $option);
            if(count($optionSplit) == 1){
                $optionsData[] = array(
                    "id" => trim($optionSplit[0]),
                    "name" => trim($optionSplit[0])
                );
                $count++;
            }else{
                $optionsData[] = array(
                    "id" => trim($optionSplit[0]),
                    "name" => trim($optionSplit[1])
                );
            }
        }

        $schema['options'] = $optionsData;

        return $schema;
    }
}