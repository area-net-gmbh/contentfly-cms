<?php
namespace Areanet\PIM\Classes\Types;
use Areanet\PIM\Classes\Api;
use Areanet\PIM\Classes\Permission;
use Areanet\PIM\Classes\Type;
use Areanet\PIM\Entity\Base;
use Areanet\PIM\Entity\BaseSortable;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;


class MultijoinType extends Type
{
    public function getAlias()
    {
        return 'multijoin';
    }

    public function getAnnotationFile()
    {
        return null;
    }

    public function doMatch($propertyAnnotations){


        if(isset($propertyAnnotations['Doctrine\\ORM\\Mapping\\OneToMany']) && isset($propertyAnnotations['Areanet\\PIM\Classes\\Annotations\\ManyToMany'])){
            $annotations = $propertyAnnotations['Areanet\\PIM\Classes\\Annotations\\ManyToMany'];
            if($annotations->targetEntity != 'Areanet\PIM\Entity\File'){
                return true;
            }
        }

        if(isset($propertyAnnotations['Doctrine\\ORM\\Mapping\\ManyToMany'])){
            $annotations = $propertyAnnotations['Doctrine\\ORM\\Mapping\\ManyToMany'];
            if($annotations->targetEntity != 'Areanet\PIM\Entity\File'){
                return true;
            }
        }

        return false;
    }


    public function processSchema($key, $defaultValue, $propertyAnnotations){
        $schema             = parent::processSchema($key, $defaultValue, $propertyAnnotations);
        $schema['multipe']  = true;
        $schema['dbtype']   = null;
        $schema['sortable'] = false;


        if(isset($propertyAnnotations['Doctrine\\ORM\\Mapping\\OneToMany'])) {
            $annotations = $propertyAnnotations['Doctrine\\ORM\\Mapping\\OneToMany'];
            $schema['acceptFrom'] = $annotations->targetEntity;
            $schema['mappedFrom'] = $annotations->mappedBy;
            $schema['foreign']    = $this->em->getClassMetadata($annotations->targetEntity)->getTableName();


            $targetEntity = new $annotations->targetEntity();
            if($targetEntity instanceof BaseSortable){
                $schema['sortable'] = true;
            }

            $annotations2       = $propertyAnnotations['Areanet\\PIM\Classes\\Annotations\\ManyToMany'];
            $schema['mappedBy'] = $annotations2->mappedBy;
            $schema['accept']   = $annotations2->targetEntity;
        }

        if(isset($propertyAnnotations['Doctrine\\ORM\\Mapping\\ManyToMany'])) {
            $annotations = $propertyAnnotations['Doctrine\\ORM\\Mapping\\ManyToMany'];
            $schema['accept'] = $annotations->targetEntity;

            if(isset($propertyAnnotations['Doctrine\\ORM\\Mapping\\JoinTable'])) {
                $annotations = $propertyAnnotations['Doctrine\\ORM\\Mapping\\JoinTable'];
                $schema['foreign'] = $annotations->name;
            }
        }

        return $schema;
    }

    public function fromDatabase(Base $object, $entityName, $property, $flatten = false, $level = 0, $propertiesToLoad = array())
    {
        $getter = 'get'.ucfirst($property);

        if(!$object->$getter() instanceof \Doctrine\ORM\PersistentCollection){
            return null;
        }

        $config     = $this->app['schema'][ucfirst($entityName)]['properties'][$property];

        $data       = array();
        $permission = \Areanet\PIM\Entity\Permission::ALL;
        $subEntity  = null;

        if(isset($config['accept'])){
            $config['accept']       = str_replace(array('Custom\\Entity\\', 'Areanet\\PIM\\Entity\\'), array('', 'PIM\\'), $config['accept']);
            $subEntity              = $config['accept'];

            if (!($permission = Permission::isReadable($this->app['auth.user'], $config['accept']))) {
                return null;
            }

            if (isset($config['acceptFrom'])) {
                $config['acceptFrom']   = str_replace(array('Custom\\Entity\\', 'Areanet\\PIM\\Entity\\'), array('', 'PIM\\'), $config['acceptFrom']);
                $subEntity              = $config['acceptFrom'];

                if(!($permission = Permission::isReadable($this->app['auth.user'], $config['acceptFrom']))){
                    return null;
                }

            }
        }

        if (in_array($property, $propertiesToLoad)) {
            foreach ($object->$getter() as $objectToLoad) {
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

                $data[] = $objectToLoad->getId();
            }
        } else {


            foreach ($object->$getter() as $objectToLoad) {
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

                if($flatten){
                    $data[] = array('id' => $objectToLoad->getId());
                } else{
                    $data[] = $objectToLoad->toValueObject($this->app, $subEntity, $flatten, $propertiesToLoad, ($level + 1), $propertiesToLoad);
                }

            }
        }

        return $data;
    }

    public function toDatabase(Api $api, Base $object, $property, $value, $entityName, $schema, $user)
    {
        $setter = 'set'.ucfirst($property);
        $getter = 'get'.ucfirst($property);

        $collection = new ArrayCollection();
        $entity     = $schema[ucfirst($entityName)]['properties'][$property]['accept'];
        $mappedBy   = isset($schema[ucfirst($entityName)]['properties'][$property]['mappedBy']) ? $schema[ucfirst($entityName)]['properties'][$property]['mappedBy'] : null;
        
        
        if($object->$getter()) {
            if ($mappedBy) {
                $acceptFrom = $schema[ucfirst($entityName)]['properties'][$property]['acceptFrom'];
                $mappedFrom = $schema[ucfirst($entityName)]['properties'][$property]['mappedFrom'];

                if(!Permission::isWritable($user, $acceptFrom)){
                    throw new AccessDeniedHttpException("Zugriff auf $acceptFrom verweigert.");
                }
                                
                $object->$getter()->clear();
                
                $qb = $this->em->createQueryBuilder();
                $qb->delete($acceptFrom, 'e');
                $qb->where('e.'.$mappedFrom.' = ?1');

                $qb->setParameter(1, $object->getId());
                $qb->getQuery()->execute();
            } else {
                $object->$getter()->clear();
            }
        }

        if(!is_array($value) || !count($value)){
            return;
        }

        $sorting = 0;
        foreach($value as $id){

            if(is_array($id)){
                if(empty($id["id"])) continue;

                $id = $id["id"];
            }
            
            $objectToJoin = $this->em->getRepository($entity)->find($id);

            
            if($mappedBy){
                $isSortable     = $schema[ucfirst($entityName)]['properties'][$property]['sortable'];
                $acceptFrom     = $schema[ucfirst($entityName)]['properties'][$property]['acceptFrom'];
                $mappedFrom     = $schema[ucfirst($entityName)]['properties'][$property]['mappedFrom'];
                $mappedEntity   = new $acceptFrom();

                $mappedSetter = 'set'.ucfirst($mappedBy);
                $mappedEntity->$mappedSetter($objectToJoin);

                $mappedSetter = 'set'.ucfirst($mappedFrom);
                $mappedEntity->$mappedSetter($object);

                if($isSortable){
                    $mappedEntity->setSorting($sorting++);
                }

                $this->em->persist($mappedEntity);
                $collection->add($mappedEntity);
            }else{
                $collection->add($objectToJoin);
            }
        }

        $object->$setter($collection);

    }

}
