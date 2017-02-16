<?php
namespace Areanet\PIM\Classes\Types;
use Areanet\PIM\Classes\Permission;
use Areanet\PIM\Classes\Type;
use Areanet\PIM\Controller\ApiController;
use Areanet\PIM\Entity\Base;
use Areanet\PIM\Entity\BaseSortable;
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


    public function processSchema($key, $defaultValue, $propertyAnnotations){
        $schema             = parent::processSchema($key, $defaultValue, $propertyAnnotations);
        $schema['multipe']  = true;
        $schema['dbtype']   = null;
        $schema['sortable'] = false;

        $annotations        = $propertyAnnotations['Areanet\\PIM\\Classes\\Annotations\\Virtualjoin'];
        $schema['accept']   = $annotations->targetEntity;

        return $schema;
    }

    public function toDatabase(ApiController $controller, Base $object, $property, $value, $entityName, $schema, $user)
    {
        $setter = 'set'.ucfirst($property);
        $getter = 'get'.ucfirst($property);

        $entity     = $schema[ucfirst($entityName)]['properties'][$property]['accept'];

        /*if(!Permission::isWritable($user, $entity)){
            throw new AccessDeniedHttpException("Zugriff auf $entity verweigert.");
        }*/

        $data = array();

        foreach($value as $id){

            if(is_array($id)){
                if(empty($id["id"])) continue;

                $id = $id["id"];
            }

            $data[] = $id;
        }

        $object->$setter(implode(',', $data));
    }



}