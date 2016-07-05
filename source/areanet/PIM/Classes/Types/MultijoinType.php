<?php
namespace Areanet\PIM\Classes\Types;
use Areanet\PIM\Classes\Type;
use Areanet\PIM\Controller\ApiController;
use Areanet\PIM\Entity\Base;
use Areanet\PIM\Entity\BaseSortable;
use Doctrine\Common\Collections\ArrayCollection;


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
        $schema['sortable'] = 'false';


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

    public function toDatabase(ApiController $controller, Base $object, $property, $value, $entityName, $schema, $user)
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

                $object->$getter()->clear();
                $query = $this->em->createQuery('DELETE FROM ' . $acceptFrom . ' e WHERE e.' . $mappedFrom . ' = ?1');
                $query->setParameter(1, $object->getId());
                $query->execute();
            } else {
                $object->$getter()->clear();
            }
        }

        if(!is_array($value) || !count($value)){
            return;
        }

        $sorting = 0;
        foreach($value as $id){
            
            $objectToJoin = $this->em->getRepository($entity)->find($id);

            if(!$objectToJoin->getIsDeleted()){


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
        }

        $object->$setter($collection);
    }



}