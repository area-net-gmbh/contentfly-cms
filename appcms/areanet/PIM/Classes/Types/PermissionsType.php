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

    public function fromDatabase(Base $object, $entityName, $property, $flatten = false, $level = 0, $propertiesToLoad = array())
    {
        if(!$object->$property instanceof \Doctrine\ORM\PersistentCollection){
            return null;
        }

        $config     = $this->app['schema'][ucfirst($entityName)]['properties'][$property];

        $data       = array();
        $permission = \Areanet\PIM\Entity\Permission::ALL;
        $subEntity  = null;

        if(!$this->app['auth.user']->getIsAdmin()){
            return null;
        }

        $subEntity = 'PIM\\Permission';

        if (in_array($property, $propertiesToLoad)) {
            foreach ($object->$property as $objectToLoad) {
                if($permission == \Areanet\PIM\Entity\Permission::OWN && ($objectToLoad->getUserCreated() != $this->app['auth.user'] &&  !$objectToLoad->hasUserId($this->app['auth.user']->getId()))){
                    continue;
                }

                if($permission == \Areanet\PIM\Entity\Permission::GROUP){
                    if($objectToLoad->getUserCreated() != $this->app['auth.user']){
                        $group = $this->app['auth.user']->getGroup();
                        if(!($group && $objectToLoad->hasGroupId($group->getId()))){
                            continue;
                        }
                    }
                }

                $data[] = $object->getId();
            }
        } else {
            
            foreach ($object->$property as $objectToLoad) {
                if($permission == \Areanet\PIM\Entity\Permission::OWN && ($objectToLoad->getUserCreated() != $this->app['auth.user'] && !$objectToLoad->hasUserId($this->app['auth.user']->getId()))){
                    continue;
                }

                if($permission == \Areanet\PIM\Entity\Permission::GROUP){
                    if($objectToLoad->getUserCreated() != $this->app['auth.user']){
                        $group = $this->app['auth.user']->getGroup();
                        if(!($group && $objectToLoad->hasGroupId($group->getId()))){
                            continue;
                        }
                    }
                }

                $data[] = $flatten
                    ? array("id" => $object->getId())
                    : $object->$objectToLoad($this->app, $subEntity, $flatten, $propertiesToLoad, ($level + 1), $propertiesToLoad);
            }
        }

        return $data;
    }

    public function toDatabase(ApiController $controller, Base $object, $property, $value, $entityName, $schema, $user)
    {
        $this->em->persist($object);
        $this->em->flush();
        
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
            if(!empty($config['extended'])){
                $pObject->setExtended(json_encode($config['extended']));
            }else{
                $pObject->setExtended('');
            }
            $pObject->setGroup($object);

            $this->em->persist($pObject);
        }


        $this->em->flush();
    }


}