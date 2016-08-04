<?php
namespace Areanet\PIM\Classes\Types;
use Areanet\PIM\Classes\Type;
use Areanet\PIM\Controller\ApiController;
use Areanet\PIM\Entity\Base;
use Areanet\PIM\Entity\Permission;
use Doctrine\Common\Collections\ArrayCollection;


class PermissionsType extends Type
{
    public function getPriority()
    {
        return 10;
    }

    public function getAlias()
    {
        return 'permissions';
    }

    public function getAnnotationFile()
    {
        return 'Permissions';
    }

    public function doMatch($propertyAnnotations){
        if(!isset($propertyAnnotations['Areanet\\PIM\\Classes\\Annotations\\Permissions'])) {
            return false;
        }

        return true;
    }

    public function processSchema($key, $defaultValue, $propertyAnnotations){
        $schema                 = parent::processSchema($key, $defaultValue, $propertyAnnotations);
        $propertyAnnotations    = $propertyAnnotations['Areanet\\PIM\\Classes\\Annotations\\Permissions'];

        $schema['dbtype']       = "integer";

        return $schema;
    }

    public function toDatabase(ApiController $controller, Base $object, $property, $value, $entityName, $schema, $user)
    {

        $query = $this->em->createQuery('DELETE FROM Areanet\PIM\\Entity\\Permission e WHERE e.group = ?1');
        $query->setParameter(1, $object);
        $query->execute();

        $pObject = new Permission();
        $pObject->setEntityName('PIM\\Tag');
        $pObject->setReadable(2);
        $pObject->setWritable(2);
        $pObject->setDeletable(2);
        $pObject->setGroup($object);

        $this->em->persist($pObject);

        foreach($value as $config){

            $pObject = new Permission();
            $pObject->setEntityName($config['name']);
            $pObject->setReadable($config['readable']);
            $pObject->setWritable($config['writable']);
            $pObject->setDeletable($config['deletable']);
            $pObject->setGroup($object);

            $this->em->persist($pObject);
        }

        $this->em->flush();

    }


}