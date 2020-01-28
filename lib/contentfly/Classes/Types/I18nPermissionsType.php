<?php
namespace Areanet\Contentfly\Classes\Types;
use Areanet\Contentfly\Classes\Api;
use Areanet\Contentfly\Classes\Type;
use Areanet\Contentfly\Controller\ApiController;
use Areanet\Contentfly\Entity\Base;
use Areanet\Contentfly\Entity\Permission;
use Doctrine\Common\Collections\ArrayCollection;


class I18nPermissionsType extends Type
{
    public function getPriority()
    {
        return 10;
    }

    public function getAlias()
    {
        return 'i18npermissions';
    }

    public function getAnnotationFile()
    {
        return 'I18nPermissions';
    }

    public function doMatch($propertyAnnotations)
    {
        if (!isset($propertyAnnotations['Areanet\\PIM\\Classes\\Annotations\\I18nPermissions'])) {
            return false;
        }

        return true;
    }

    public function processSchema($key, $defaultValue, $propertyAnnotations, $entityName)
    {
        $schema = parent::processSchema($key, $defaultValue, $propertyAnnotations, $entityName);

        $schema['dbtype'] = "string";

        return $schema;
    }

    public function fromDatabase(Base $object, $entityName, $property, $flatten = false, $level = 0, $propertiesToLoad = array())
    {
        $getter = 'get'.ucfirst($property);

        if(!$object->$getter()){
            return null;
        }

        return is_string($object->$getter()) ? json_decode($object->$getter(), true) : $object->$getter();
    }

    public function toDatabase(Api $api, Base $object, $property, $value, $entityName, $schema, $user, $data = null, $lang = null)
    {

        if($value){
            $value = !is_string($value) ? json_encode($value) : $value;

        }

        $setter = 'set'.ucfirst($property);
        $object->$setter($value);
    }


}
