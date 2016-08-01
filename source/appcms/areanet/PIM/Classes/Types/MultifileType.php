<?php
namespace Areanet\PIM\Classes\Types;
use Areanet\PIM\Classes\Type;
use Areanet\PIM\Controller\ApiController;
use Areanet\PIM\Entity\Base;
use Areanet\PIM\Entity\BaseSortable;
use Doctrine\Common\Collections\ArrayCollection;


class MultifileType extends Type
{
    public function getAlias()
    {
        return 'multifile';
    }

    public function getAnnotationFile()
    {
        return null;
    }

    public function doMatch($propertyAnnotations){


        if(isset($propertyAnnotations['Doctrine\\ORM\\Mapping\\OneToMany']) && isset($propertyAnnotations['Areanet\\PIM\Classes\\Annotations\\ManyToMany'])){
            $annotations = $propertyAnnotations['Areanet\\PIM\Classes\\Annotations\\ManyToMany'];
            if($annotations->targetEntity == 'Areanet\PIM\Entity\File'){
                return true;
            }
        }

        if(isset($propertyAnnotations['Doctrine\\ORM\\Mapping\\ManyToMany'])){
            $annotations = $propertyAnnotations['Doctrine\\ORM\\Mapping\\ManyToMany'];
            if($annotations->targetEntity == 'Areanet\PIM\Entity\File'){
                return true;
            }
        }

        return false;
    }


    public function processSchema($key, $defaultValue, $propertyAnnotations){
        $schema             = parent::processSchema($key, $defaultValue, $propertyAnnotations);
        $schema['multipe']  = true;
        $schema['dbtype']   = null;
        $schema['accept']   = '*';
        $schema['sortable'] = 'false';

        if(isset($propertyAnnotations['Areanet\\PIM\\Classes\\Annotations\\Config'])){
            $annotations = $propertyAnnotations['Areanet\\PIM\\Classes\\Annotations\\Config'];

            if($annotations->accept){
                $schema['accept'] = $annotations->accept;
            }
        }

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
            
        }

        if(isset($propertyAnnotations['Doctrine\\ORM\\Mapping\\ManyToMany'])) {
            $annotations = $propertyAnnotations['Doctrine\\ORM\\Mapping\\ManyToMany'];
        }

        return $schema;
    }

    public function toDatabase(ApiController $controller, Base $object, $property, $value, $entityName, $schema, $user)
    {
        $setter = 'set'.ucfirst($property);
        $getter = 'get'.ucfirst($property);

        $collection = new ArrayCollection();

        if($schema[ucfirst($entityName)]['properties'][$property]['sortable']){
            $acceptFrom = $schema[ucfirst($entityName)]['properties'][$property]['acceptFrom'];
            $mappedFrom = $schema[ucfirst($entityName)]['properties'][$property]['mappedFrom'];
            $mappedBy   = $schema[ucfirst($entityName)]['properties'][$property]['mappedBy'];

            if($object->$getter()) {
                $object->$getter()->clear();
                $query = $this->em->createQuery('DELETE FROM ' . $acceptFrom . ' e WHERE e.' . $mappedFrom . ' = ?1');
                $query->setParameter(1, $object->getId());
                $query->execute();
            }

            $sorting = 0;
            foreach($value as $id){
                $objectToJoin = $this->em->getRepository('Areanet\PIM\Entity\File')->find($id);
                if($objectToJoin->getIsDeleted()) continue;

                $mappedEntity = new $acceptFrom();

                $mappedSetter = 'set'.ucfirst($mappedFrom);
                $mappedEntity->$mappedSetter($object);

                $mappedBySetter = 'set'.ucfirst($mappedBy);
                $mappedEntity->$mappedBySetter($objectToJoin);

                $mappedEntity->setSorting($sorting);

                $this->em->persist($mappedEntity);
                $collection->add($mappedEntity);

                $sorting++;
            }

            if($object->$getter()) $object->$getter()->clear();
            $object->$setter($collection);
        }else{
            if(!is_array($value) || !count($value)){
                if($object->$getter()) $object->$getter()->clear();
                return;
            }

            foreach($value as $id){

                $objectToJoin = $this->em->getRepository('Areanet\PIM\Entity\File')->find($id);
                if(!$objectToJoin->getIsDeleted()) $collection->add($objectToJoin);
            }

            if($object->$getter()) $object->$getter()->clear();
            $object->$setter($collection);
        }

    }
}