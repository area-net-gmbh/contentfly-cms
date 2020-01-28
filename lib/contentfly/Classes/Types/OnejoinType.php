<?php
namespace Areanet\Contentfly\Classes\Types;
use Areanet\Contentfly\Classes\Api;
use Areanet\Contentfly\Classes\Exceptions\ContentflyException;
use Areanet\Contentfly\Classes\Helper;
use Areanet\Contentfly\Classes\Permission;
use Areanet\Contentfly\Classes\Type;
use Areanet\Contentfly\Entity\Base;
use Custom\Entity\TestMeta;


class OnejoinType extends Type
{
    public function getAlias()
    {
        return 'onejoin';
    }

    public function getAnnotationFile()
    {
        return null;
    }

    public function doMatch($propertyAnnotations){

        if(!isset($propertyAnnotations['Doctrine\\ORM\\Mapping\\OneToOne'])) {
            return false;
        }

        return true;
    }

    public function processSchema($key, $defaultValue, $propertyAnnotations, $entityName){
        $schema                 = parent::processSchema($key, $defaultValue, $propertyAnnotations, $entityName);
        $propertyAnnotations    = $propertyAnnotations['Doctrine\\ORM\\Mapping\\OneToOne'];

        $helper = new Helper();
        $one2Oneentity = $helper->getShortEntityName($propertyAnnotations->targetEntity);

        $i18nTest = new $propertyAnnotations->targetEntity();
        if(is_a($i18nTest, 'Areanet\Contentfly\Entity\BaseI18n')){
            throw new ContentflyException('contentfly_i18n_onejoin_not_supported', $helper->getShortEntityName($propertyAnnotations->targetEntity));
       }


        $schema['dbtype']   = 'integer';
        $schema['accept']   = $one2Oneentity;
        $schema['multiple'] = false;
        $schema['tab']      = $one2Oneentity;
        

        $this->addTab($one2Oneentity, array('title' => $schema['label'], 'onejoin' => true, 'onejoin_field' => $key));

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


        if($permission == \Areanet\Contentfly\Entity\Permission::OWN && ($subobject->getUserCreated() != $this->app['auth.user'] && !$subobject->hasUserId($this->app['auth.user']->getId()))){
            return array('id' => $subobject->getId(), 'pim_blocked' => true);
        }

        if($permission == \Areanet\Contentfly\Entity\Permission::GROUP){
            if($subobject->getUserCreated() != $this->app['auth.user']){
                $group = $this->app['auth.user']->getGroup();
                if(!($group && $subobject->hasGroupId($group->getId()))){
                    return array('id' => $subobject->getId(), 'pim_blocked' => true);
                }
            }
        }

        return $flatten
            ? array("id" => $subobject->getId())
            : $subobject->toValueObject($this->app, $config['accept'], $flatten, array(), ($level + 1), true);
    }

    public function toDatabase(Api $api, Base $object, $property, $value, $entityName, $schema, $user, $data = null, $lang = null)
    {
        $setter     = 'set'.ucfirst($property);
        $joinEntity = $schema[ucfirst($entityName)]['properties'][$property]['accept'];

        if(!empty($value['id'])){
            $api->doUpdate($joinEntity, $value['id'], $value, false, null, $lang);
        }else{

            $value['users']     = isset($data['users']) ? $data['users'] : array();
            $value['groups']    = isset($data['groups']) ? $data['groups'] : array();

            $joinObject = $api->doInsert($joinEntity, $value, $lang);
            $object->$setter($joinObject);
        }

    }
}
