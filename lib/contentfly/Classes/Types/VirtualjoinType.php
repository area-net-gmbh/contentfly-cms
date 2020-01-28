<?php
namespace Areanet\Contentfly\Classes\Types;
use Areanet\Contentfly\Classes\Api;
use Areanet\Contentfly\Classes\Permission;
use Areanet\Contentfly\Classes\Type;
use Areanet\Contentfly\Controller\ApiController;
use Areanet\Contentfly\Entity\Base;
use Areanet\Contentfly\Entity\BaseSortable;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;


class VirtualjoinType extends Type
{
    public function getAlias()
    {
        return 'virtualjoin';
    }

    public function getPriority()
    {
        return 10;
    }

    public function getAnnotationFile()
    {
        return 'Virtualjoin';
    }

    public function doMatch($propertyAnnotations)
    {

        if(!isset($propertyAnnotations['Areanet\\PIM\\Classes\\Annotations\\Virtualjoin'])) {
            return false;
        }

        return true;
    }


    public function processSchema($key, $defaultValue, $propertyAnnotations, $entityName){
        $schema             = parent::processSchema($key, $defaultValue, $propertyAnnotations, $entityName);
        $schema['multipe']  = true;
        $schema['dbtype']   = 'string';
        $schema['sortable'] = false;

        $annotations        = $propertyAnnotations['Areanet\\PIM\\Classes\\Annotations\\Virtualjoin'];
        $schema['accept']   = $annotations->targetEntity;

        return $schema;
    }

    public function toDatabase(Api $api, Base $object, $property, $value, $entityName, $schema, $user, $data = null, $lang = null)
    {

        $setter = 'set'.ucfirst($property);

        $data = array();

        if(!is_array($value)){
            $object->$setter($value);
            return;
        }

        foreach($value as $id){

            if(is_array($id)){
                if(empty($id["id"])) continue;

                $id = $id["id"];
            }

            $data[] = $id;
        }

        $object->$setter(implode(',', $data));
    }

    public function fromDatabase(Base $object, $entityName, $property, $flatten = false, $level = 0, $propertiesToLoad = array())
    {
        $getter = 'get'.ucfirst($property);

        if(!($value = $object->$getter())){
            return null;
        }
        if(is_array($value)){
            return $value;
        }

        $items = explode(',', $value);

        $data = array();

        foreach($items as $item){
            $data[] = array(
                'id' => $item
            );
        }

        return $data;
    }
    
}
