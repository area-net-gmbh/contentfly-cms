<?php
namespace Areanet\Contentfly\Classes\Types;
use Areanet\Contentfly\Classes\Api;
use Areanet\Contentfly\Classes\Type;
use Areanet\Contentfly\Controller\ApiController;
use Areanet\Contentfly\Entity\Base;
use Areanet\Contentfly\Entity\Permission;
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

    public function doMatch($propertyAnnotations)
    {
        if (!isset($propertyAnnotations['Areanet\\PIM\\Classes\\Annotations\\Permissions'])) {
            return false;
        }

        return true;
    }

    public function processSchema($key, $defaultValue, $propertyAnnotations, $entityName)
    {
        $schema = parent::processSchema($key, $defaultValue, $propertyAnnotations, $entityName);

        $schema['dbtype'] = "integer";

        return $schema;
    }

    public function fromDatabase(Base $object, $entityName, $property, $flatten = false, $level = 0, $propertiesToLoad = array())
    {

        $getter = 'get' . ucfirst($property);

        if (!$object->$getter() instanceof \Doctrine\ORM\PersistentCollection) {
            return null;
        }


        $data = array();
        $permission = \Areanet\Contentfly\Entity\Permission::ALL;
        $subEntity = null;

        if (!$this->app['auth.user']->getIsAdmin()) {
            return null;
        }

        $subEntity = 'PIM\\Permission';

        if (in_array($property, $propertiesToLoad)) {
            foreach ($object->$getter() as $objectToLoad) {
                if ($permission == \Areanet\Contentfly\Entity\Permission::OWN && ($objectToLoad->getUserCreated() != $this->app['auth.user'] && !$objectToLoad->hasUserId($this->app['auth.user']->getId()))) {
                    continue;
                }

                if ($permission == \Areanet\Contentfly\Entity\Permission::GROUP) {
                    if ($objectToLoad->getUserCreated() != $this->app['auth.user']) {
                        $group = $this->app['auth.user']->getGroup();
                        if (!($group && $objectToLoad->hasGroupId($group->getId()))) {
                            continue;
                        }
                    }
                }

                $data[] = $object->getId();
            }
        } else {

            foreach ($object->$getter() as $objectToLoad) {
                if ($permission == \Areanet\Contentfly\Entity\Permission::OWN && ($objectToLoad->getUserCreated() != $this->app['auth.user'] && !$objectToLoad->hasUserId($this->app['auth.user']->getId()))) {
                    continue;
                }

                if ($permission == \Areanet\Contentfly\Entity\Permission::GROUP) {
                    if ($objectToLoad->getUserCreated() != $this->app['auth.user']) {
                        $group = $this->app['auth.user']->getGroup();
                        if (!($group && $objectToLoad->hasGroupId($group->getId()))) {
                            continue;
                        }
                    }
                }

                $data[] = $flatten
                    ? array("id" => $object->getId())
                    : $objectToLoad->toValueObject($this->app, $subEntity, $flatten, $propertiesToLoad, ($level + 1), $propertiesToLoad);
            }
        }

        return $data;
    }

    public function toDatabase(Api $api, Base $object, $property, $value, $entityName, $schema, $user, $data = null, $lang = null)
    {
        $this->em->persist($object);
        $this->em->flush();

        $query = $this->em->createQuery('DELETE FROM Areanet\Contentfly\\Entity\\Permission e WHERE e.group = ?1');
        $query->setParameter(1, $object);
        $query->execute();

        $pObject = new Permission();
        $pObject->setEntityName('PIM\\Tag');
        $pObject->setReadable(2);
        $pObject->setWritable(2);
        $pObject->setDeletable(2);
        $pObject->setGroup($object);

        $this->em->persist($pObject);

        foreach ($value as $config) {

            $pObject = new Permission();
            $pObject->setEntityName($config['name']);
            $pObject->setReadable($config['readable']);
            $pObject->setWritable($config['writable']);
            $pObject->setDeletable($config['deletable']);
            $pObject->setExport($config['export']);
            if (!empty($config['extended'])) {
                $pObject->setExtended(json_encode($config['extended']));
            } else {
                $pObject->setExtended('');
            }
            $pObject->setGroup($object);

            $this->em->persist($pObject);
        }


        $this->em->flush();
    }


}
