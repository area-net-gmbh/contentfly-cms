<?php
namespace Areanet\PIM\Classes\Types;
use Areanet\PIM\Classes\Permission;
use Areanet\PIM\Classes\Type;
use Areanet\PIM\Controller\ApiController;
use Areanet\PIM\Entity\Base;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;


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
        

        $this->addTab($one2Oneentity, array('title' => $schema['label'], 'onejoin' => true, 'onejoin_field' => $key));

        return $schema;
    }

    public function toDatabase(ApiController $controller, Base $object, $property, $value, $entityName, $schema, $user)
    {
        $setter = 'set'.ucfirst($property);

        $joinEntity = $schema[ucfirst($entityName)]['properties'][$property]['accept'];

        if(!empty($value['id'])){
            $controller->update($joinEntity, $value['id'], $value, false, $user);
        }else{
            $joinObject = $controller->insert($joinEntity, $value, $user);

            $object->$setter($joinObject);
        }

    }
}
