<?php
namespace Areanet\Contentfly\Classes\Types;
use Areanet\Contentfly\Classes\Annotations\Time;
use Areanet\Contentfly\Classes\Type;
use Areanet\Contentfly\Controller\ApiController;
use Areanet\Contentfly\Entity\Base;


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
