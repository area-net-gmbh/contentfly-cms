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


class RadioType extends Type
{
    public function getPriority()
    {
        return 10;
    }

    public function getAlias()
    {
        return 'radio';
    }

    public function getAnnotationFile()
    {
        return 'Radio';
    }

    public function doMatch($propertyAnnotations){

        if(!isset($propertyAnnotations['Areanet\\PIM\\Classes\\Annotations\\Radio'])){
            return false;
        }
        return true;
    }


    public function processSchema($key, $defaultValue, $propertyAnnotations, $entityName){
        $schema             = parent::processSchema($key, $defaultValue, $propertyAnnotations, $entityName);
        $schema['multiple'] = false;

        if(isset($propertyAnnotations['Doctrine\\ORM\\Mapping\\ManyToOne'])) {
            $annotations = $propertyAnnotations['Doctrine\\ORM\\Mapping\\ManyToOne'];
            $schema['accept'] = $annotations->targetEntity;

            if(isset($propertyAnnotations['Doctrine\\ORM\\Mapping\\JoinColumn'])) {
                $annotationsColumn = $propertyAnnotations['Doctrine\\ORM\\Mapping\\JoinColumn'];
                $schema['nullable'] = isset($annotationsColumn->nullable) ? $annotationsColumn->nullable : true;
            }
        }

        $propertyAnnotations    = $propertyAnnotations['Areanet\\PIM\\Classes\\Annotations\\Radio'];

        $optionsGroupName       = isset($propertyAnnotations->group) ? $propertyAnnotations->group : $entityName.'/'.$key;
        $optionsGroupObject     = $this->em->getRepository('Areanet\\PIM\\Entity\\OptionGroup')->findOneBy(array('name' => $optionsGroupName));

        if(!$optionsGroupObject) {
            $optionsGroupObject = new OptionGroup();
            $optionsGroupObject->setName($optionsGroupName);
            $this->em->persist($optionsGroupObject);
            $this->em->flush();
        }

        $schema['group']                = $optionsGroupObject->getId();
        $schema['horizontalAlignment']  = $propertyAnnotations->horizontalAlignment;
        $schema['select']               = $propertyAnnotations->select;
        $schema['columns']              = $propertyAnnotations->columns;

        return $schema;
    }

    public function fromDatabase(Base $object, $entityName, $property, $flatten = false, $level = 0, $propertiesToLoad = array())
    {
        $config             = $this->app['schema'][ucfirst($entityName)]['properties'][$property];
        $config['accept']   = str_replace(array('Custom\\Entity\\', 'Areanet\\PIM\\Entity\\'), array('', 'PIM\\'), $config['accept']);

        if(substr($config['accept'], 0, 1) == '\\'){
            $config['accept'] = substr($config['accept'], 1);
        }

        $getterName = 'get' . ucfirst($property);
        $subobject  = $object->$getterName();

        if(!$subobject){
            return;
        }

        if (!($permission = Permission::isReadable($this->app['auth.user'], $config['accept']))) {
            return array('id' => $subobject->getId(), 'pim_blocked' => true);
        }


        if($permission == \Areanet\PIM\Entity\Permission::OWN && ($subobject->getUserCreated() != $this->app['auth.user'] && !$subobject->hasUserId($this->app['auth.user']->getId()))){
            return array('id' => $subobject->getId(), 'pim_blocked' => true);
        }

        if($permission == \Areanet\PIM\Entity\Permission::GROUP){
            if($subobject->getUserCreated() != $this->app['auth.user']){
                $group = $this->app['auth.user']->getGroup();
                if(!($group && $subobject->hasGroupId($group->getId()))){
                    return array('id' => $subobject->getId(), 'pim_blocked' => true);
                }
            }
        }

        return $flatten
            ? array("id" => $subobject->getId())
            : $subobject->toValueObject($this->app, $config['accept'], $flatten, array(), ($level + 1));
    }

    public function toDatabase(Api $api, Base $object, $property, $value, $entityName, $schema, $user, $data = null, $lang = null)
    {
        $setter = 'set'.ucfirst($property);

        $entity = $schema[ucfirst($entityName)]['properties'][$property]['accept'];


        if(is_array($value)){
            if(empty($value["id"])) return;

            $value = $value["id"];
        }

        if(!empty($value)){
            $objectToJoin = $this->em->getRepository($entity)->find($value);
        }else{
            $objectToJoin = null;
        }

        $object->$setter($objectToJoin);

    }

}
