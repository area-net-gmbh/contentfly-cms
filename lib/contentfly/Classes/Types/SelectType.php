<?php
namespace Areanet\Contentfly\Classes\Types;

use Areanet\Contentfly\Classes\Api;
use Areanet\Contentfly\Classes\Type;
use Areanet\Contentfly\Entity\Base;


class SelectType extends Type
{
    public function getPriority()
    {
        return 10;
    }
    
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

    public function processSchema($key, $defaultValue, $propertyAnnotations, $entityName){
        $schema                 = parent::processSchema($key, $defaultValue, $propertyAnnotations, $entityName);
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

    public function toDatabase(Api $api, Base $object, $property, $value, $entityName, $schema, $user, $data = null, $lang = null)
    {
        $setter = 'set'.ucfirst($property);

        if($schema[ucfirst($entityName)]['properties'][$property]['dbtype'] == 'integer'){
            $object->$setter(intval($value));
        }else{
            $object->$setter($value);
        }

    }
}
