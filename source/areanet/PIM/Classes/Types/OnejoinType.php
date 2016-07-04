<?php
namespace Areanet\PIM\Classes\Types;
use Areanet\PIM\Classes\Type;


class OnejoinType extends Type
{
    public function getAlias()
    {
        return 'onejoin';
    }

    public function getAnnotationFile()
    {
        return null;
    }

    public function doMatch($propertyAnnotations){

        if(!isset($propertyAnnotations['Doctrine\\ORM\\Mapping\\OneToOne'])) {
            return false;
        }

        return true;
    }


    public function processSchema($key, $defaultValue, $propertyAnnotations){
        $schema                 = parent::processSchema($key, $defaultValue, $propertyAnnotations);
        $propertyAnnotations    = $propertyAnnotations['Doctrine\\ORM\\Mapping\\OneToOne'];


        $entityPath     = explode('\\', $propertyAnnotations->targetEntity);
        $one2Oneentity  = $entityPath[(count($entityPath) - 1)];

        $schema['dbtype']   = 'integer';
        $schema['accept']   = $one2Oneentity;
        $schema['multiple'] = false;
        $schema['tab']      = $one2Oneentity;

        $this->addTab($one2Oneentity, array('title' =>  $schema['label'], 'onejoin' => true, 'onejoin_field' => $key));
                
        return $schema;
    }
}