<?php
namespace Areanet\PIM\Classes\Types;
use Areanet\PIM\Classes\Permission;
use Areanet\PIM\Classes\Type;
use Areanet\PIM\Controller\ApiController;
use Areanet\PIM\Entity\Base;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;


class JoinType extends Type
{
    public function getAlias()
    {
        return 'join';
    }

    public function getAnnotationFile()
    {
        return null;
    }

    public function doMatch($propertyAnnotations){

        if(!isset($propertyAnnotations['Doctrine\\ORM\\Mapping\\ManyToOne'])) {
            return false;
        }
        $annotations = $propertyAnnotations['Doctrine\\ORM\\Mapping\\ManyToOne'];

        return $annotations->targetEntity != 'Areanet\\PIM\Entity\\File\\';
    }


    public function processSchema($key, $defaultValue, $propertyAnnotations){
        $schema             = parent::processSchema($key, $defaultValue, $propertyAnnotations);
        $annotations        = $propertyAnnotations['Doctrine\\ORM\\Mapping\\ManyToOne'];

        $schema['accept']   = $annotations->targetEntity;
        $schema['multiple'] = false;

        return $schema;
    }

    public function toDatabase(ApiController $controller, Base $object, $property, $value, $entityName, $schema, $user)
    {
        $setter = 'set'.ucfirst($property);
        $getter = 'get'.ucfirst($property);

        $entity = $schema[ucfirst($entityName)]['properties'][$property]['accept'];

        if(!Permission::isWritable($user, $entity)){
            throw new AccessDeniedHttpException("Zugriff auf $entity verweigert.");
        }

        $objectToJoin = $this->em->getRepository($entity)->find($value);
        $object->$setter($objectToJoin);

    }
}