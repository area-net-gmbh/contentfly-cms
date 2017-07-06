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

    public function toDatabase(ApiController $controller, Base $object, $property, $value, $entityName, $schema, $user)
    {
        $setter = 'set'.ucfirst($property);

        $entity = $schema[ucfirst($entityName)]['properties'][$property]['accept'];

        /*if(!Permission::isReadable($user, $entity)){
            throw new AccessDeniedHttpException("Zugriff auf $entity verweigert.");
        }*/

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
