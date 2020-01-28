<?php
namespace Areanet\PIM\Classes\Types;
use Areanet\PIM\Classes\Annotations\Time;
use Areanet\PIM\Classes\Type;
use Areanet\PIM\Controller\ApiController;
use Areanet\PIM\Entity\Base;


class EntitySelectorType extends Type
{
    public function getAlias()
    {
        return 'entityselector';
    }

    public function getAnnotationFile()
    {
        return 'EntitySelector';
    }

    public function doMatch($propertyAnnotations){
        if(!isset($propertyAnnotations['Areanet\\PIM\\Classes\\Annotations\\EntitySelector'])) {
            return false;
        }

        return true;
    }
    
}
