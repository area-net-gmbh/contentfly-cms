<?php
namespace Areanet\Contentfly\Classes\Types;
use Areanet\Contentfly\Classes\Api;
use Areanet\Contentfly\Classes\Permission;
use Areanet\Contentfly\Classes\Type;
use Areanet\Contentfly\Controller\ApiController;
use Areanet\Contentfly\Entity\Base;
use Areanet\Contentfly\Entity\BaseSortable;
use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;


class JoinBidirectionalType extends Type
{
    public function getAlias()
    {
        return 'joinbidirectional';
    }

    public function getAnnotationFile()
    {
        return null;
    }

    public function doMatch($propertyAnnotations){


        if(isset($propertyAnnotations['Doctrine\\ORM\\Mapping\\OneToMany']) && !isset($propertyAnnotations['Areanet\\PIM\Classes\\Annotations\\ManyToMany'])){
            $annotations = $propertyAnnotations['Doctrine\\ORM\\Mapping\\OneToMany'];
            if($annotations->targetEntity != 'Areanet\Contentfly\Entity\BaseTree'){
                return true;
            }
        }

        return false;
    }


    public function processSchema($key, $defaultValue, $propertyAnnotations, $entityName){
        $schema             = parent::processSchema($key, $defaultValue, $propertyAnnotations, $entityName);
        $schema['multipe']  = true;
        $schema['dbtype']   = null;
        $schema['sortable'] = false;


        if(isset($propertyAnnotations['Doctrine\\ORM\\Mapping\\OneToMany'])) {
            $annotations = $propertyAnnotations['Doctrine\\ORM\\Mapping\\OneToMany'];
            $schema['targetEntity'] = $annotations->targetEntity;
            $schema['mappedBy']     = $annotations->mappedBy;
            $schema['foreignTable'] = $this->em->getClassMetadata($annotations->targetEntity)->getTableName();



            $targetEntity = new $annotations->targetEntity();
            if($targetEntity instanceof BaseSortable){
                $annotationReader   = new AnnotationReader();
                $reflect            = new \ReflectionClass($targetEntity);
                $classAnnotations   = $annotationReader->getClassAnnotations($reflect);

                foreach($classAnnotations as $classAnnotation) {
                    if ($classAnnotation instanceof \Areanet\Contentfly\Classes\Annotations\Config) {
                        if(isset($classAnnotation->sortRestrictTo) && $classAnnotation->sortRestrictTo == $annotations->mappedBy){
                            $schema['sortable'] = true;
                            break;
                        }
                    }
                }

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
        $subEntity  = null;

        $targetEntity = str_replace(array('Custom\\Entity\\', 'Areanet\\PIM\\Entity\\'), array('', 'PIM\\'), $config['targetEntity']);

        if(!($permission = Permission::isReadable($this->app['auth.user'], $targetEntity))){
            return null;
        }

        if (in_array($property, $propertiesToLoad)) {
            foreach ($object->$getter() as $objectToLoad) {
                if($permission == \Areanet\Contentfly\Entity\Permission::OWN && ($objectToLoad->getUserCreated() != $this->app['auth.user'] &&  !$objectToLoad->hasUserId($this->app['auth.user']->getId()))){
                    continue;
                }

                if($permission == \Areanet\Contentfly\Entity\Permission::GROUP){
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
                if($permission == \Areanet\Contentfly\Entity\Permission::OWN && ($objectToLoad->getUserCreated() != $this->app['auth.user'] && !$objectToLoad->hasUserId($this->app['auth.user']->getId()))){
                    continue;
                }

                if($permission == \Areanet\Contentfly\Entity\Permission::GROUP){
                    if($objectToLoad->getUserCreated() != $this->app['auth.user']){
                        $group = $this->app['auth.user']->getGroup();
                        if(!($group && $objectToLoad->hasGroupId($group->getId()))){
                            continue;
                        }
                    }
                }

                $data[] = $objectToLoad->toValueObject($this->app, $targetEntity, $flatten, $propertiesToLoad, ($level + 1), $propertiesToLoad);

            }
        }

        return $data;
    }

    public function toDatabase(Api $api, Base $object, $property, $value, $entityName, $schema, $user, $data = null, $lang = null)
    {

    }

}
