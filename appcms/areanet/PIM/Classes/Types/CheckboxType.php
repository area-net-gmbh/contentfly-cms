<?php
namespace Areanet\PIM\Classes\Types;
use Areanet\PIM\Classes\Api;
use Areanet\PIM\Classes\Permission;
use Areanet\PIM\Classes\Type;
use Areanet\PIM\Entity\Base;
use Areanet\PIM\Entity\BaseSortable;
use Areanet\PIM\Entity\OptionGroup;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;


class CheckboxType extends Type
{
    public function getPriority()
    {
        return 10;
    }

    public function getAlias()
    {
        return 'checkbox';
    }

    public function getAnnotationFile()
    {
        return 'Checkbox';
    }

    public function doMatch($propertyAnnotations){

        if(!isset($propertyAnnotations['Areanet\\PIM\\Classes\\Annotations\\Checkbox'])){
            return false;
        }
        return true;
    }


    public function processSchema($key, $defaultValue, $propertyAnnotations, $entityName){
        $schema             = parent::processSchema($key, $defaultValue, $propertyAnnotations, $entityName);
        $schema['multipe']  = true;
        $schema['dbtype']   = null;
        $schema['sortable'] = false;

        if(isset($propertyAnnotations['Doctrine\\ORM\\Mapping\\ManyToMany'])) {
            $annotations = $propertyAnnotations['Doctrine\\ORM\\Mapping\\ManyToMany'];
            $schema['accept'] = $annotations->targetEntity;

            if(isset($propertyAnnotations['Doctrine\\ORM\\Mapping\\JoinTable'])) {
                $annotations = $propertyAnnotations['Doctrine\\ORM\\Mapping\\JoinTable'];
                $schema['foreign'] = $annotations->name;
            }
        }

        $propertyAnnotations    = $propertyAnnotations['Areanet\\PIM\\Classes\\Annotations\\Checkbox'];

        $optionsGroupName = isset($propertyAnnotations->group) ? $propertyAnnotations->group : $entityName.'/'.$key;
        $optionsGroupObject = $this->em->getRepository('Areanet\\PIM\\Entity\\OptionGroup')->findBy(array('name' => $optionsGroupName));

        $newOptionsGroupObject = null;
        if(!$optionsGroupObject) {
            $newOptionsGroupObject = new OptionGroup();
            $newOptionsGroupObject->setName($optionsGroupName);
            $this->em->persist($newOptionsGroupObject);
            $this->em->flush();
        }

        $schema['group']                = ($optionsGroupObject) ? $optionsGroupObject[0]->getId() : $newOptionsGroupObject->getId();
        $schema['horizontalAlignment']  = $propertyAnnotations->horizontalAlignment;

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

    public function toDatabase(Api $api, Base $object, $property, $value, $entityName, $schema, $user, $data = null)
    {
        $setter = 'set'.ucfirst($property);
        $getter = 'get'.ucfirst($property);

        $collection = new ArrayCollection();
        $entity     = $schema[ucfirst($entityName)]['properties'][$property]['accept'];


        if($object->$getter()) {
            $object->$getter()->clear();
        }

        if(!is_array($value) || !count($value)){
            return;
        }

        foreach($value as $id){

            if(is_array($id)){
                if(empty($id["id"])) continue;

                $id = $id["id"];
            }

            $objectToJoin = $this->em->getRepository($entity)->find($id);

            $collection->add($objectToJoin);

        }

        $object->$setter($collection);

    }

}
