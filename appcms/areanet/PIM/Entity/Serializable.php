<?php
namespace Areanet\PIM\Entity;

use Areanet\PIM\Classes\Config\Adapter;
use Areanet\PIM\Classes\Permission;

abstract class Serializable implements \JsonSerializable{

    function jsonSerialize()
    {
        return $this->toValueObject();
    }

    public function toValueObject(User $user = null, $schema = null, $entityName = null, $flatten = false, $propertiesToLoad = array(), $level = 0)
    {

        $result = new \stdClass();

        if($level > Adapter::getConfig()->DB_NESTED_LEVELS){
            $result->id = $this->getId();
            return $result;
        }

        foreach ($this as $property => $value) {

            if(count($propertiesToLoad) && !in_array($property, $propertiesToLoad)){
                continue;
            }

            if(!isset($schema[$entityName]['properties'][$property])){
                continue;
            }
            $config = $schema[$entityName]['properties'][$property];

            if(!$flatten){
                $getter = 'get' . ucfirst($property);
                if (method_exists($this, $getter)) {

                    if ($this->$property instanceof \Datetime) {
                        $res = $this->$property->format('Y');
                        if ($this->$property->format('Y') == '-0001' || $this->$property->format('Y') == '0000') {
                            $result->$property = array(
                                'LOCAL_TIME' => null,
                                'LOCAL' => null,
                                'ISO8601' => null,
                                'IMESTAMP' => null
                            );
                        } else {
                            $result->$property = array(
                                'LOCAL_TIME' => $this->$property->format('d.m.Y H:i'),
                                'LOCAL' => $this->$property->format('d.m.Y'),
                                'ISO8601' => $this->$property->format(\DateTime::ISO8601),
                                'TIMESTAMP' => $this->$property->getTimestamp()
                            );
                        }
                    }elseif($this->$property instanceof Base) {


                        $config['accept'] = str_replace(array('Custom\\Entity\\', 'Areanet\\PIM\\Entity\\'), array('', 'PIM\\'), $config['accept']);
                        if(substr($config['accept'], 0, 1) == '\\'){
                            $config['accept'] = substr($config['accept'], 1);
                        }
                        $permission = \Areanet\PIM\Entity\Permission::ALL;

                        $getterName = 'get' . ucfirst($property);
                        $subobject = $this->$getterName();



                        if($config['type'] == 'file'){
                            $config['accept'] = 'PIM\\File';

                            if (!($permission = Permission::isReadable($user, 'PIM\\File'))) {
                                $result->$property = array('id' => $subobject->getId(), 'pim_blocked' => true);
                                continue;
                            }

                        }else {
                            if (!($permission = Permission::isReadable($user, $config['accept']))) {
                                $result->$property = array('id' => $subobject->getId(), 'pim_blocked' => true);
                                continue;
                            }
                        }

                        if($permission == \Areanet\PIM\Entity\Permission::OWN && $subobject->getUserCreated() != $user){
                            $result->$property = array('id' => $subobject->getId(), 'pim_blocked' => true);
                            continue;
                        }
                        
                        $result->$property = $subobject->toValueObject($user, $schema, $config['accept'], $flatten, array(), ($level + 1));

                    }elseif($this->$property instanceof \Doctrine\ORM\PersistentCollection) {
                        $data       = array();
                        $permission = \Areanet\PIM\Entity\Permission::ALL;
                        $subEntity  = null;

                        if($config['type'] == 'multifile'){
                            $subEntity = 'PIM\\File';

                            if (!($permission = Permission::isReadable($user, 'PIM\\File'))) {
                                unset($result->$property);
                                continue;
                            }

                            if (isset($config['acceptFrom'])) {
                                $config['acceptFrom']   = str_replace(array('Custom\\Entity\\', 'Areanet\\PIM\\Entity\\'), array('', 'PIM\\'), $config['acceptFrom']);
                                $subEntity              = $config['acceptFrom'];

                                if(!($permission = Permission::isReadable($user, $config['acceptFrom']))){
                                    unset($result->$property);
                                    continue;
                                }

                            }
                        }elseif($config['type'] == 'permissions'){
                            if(!$user->getIsAdmin()){
                                continue;
                            }

                            $subEntity = 'PIM\\Permission';
                        }else{
                            if(isset($config['accept'])){
                                $config['accept']       = str_replace(array('Custom\\Entity\\', 'Areanet\\PIM\\Entity\\'), array('', 'PIM\\'), $config['accept']);
                                $subEntity              = $config['accept'];
                                
                                if (!($permission = Permission::isReadable($user, $config['accept']))) {
                                    unset($result->$property);
                                    continue;
                                }

                                if (isset($config['acceptFrom'])) {
                                    $config['acceptFrom']   = str_replace(array('Custom\\Entity\\', 'Areanet\\PIM\\Entity\\'), array('', 'PIM\\'), $config['acceptFrom']);
                                    $subEntity              = $config['acceptFrom'];
                                    
                                    if(!($permission = Permission::isReadable($user, $config['acceptFrom']))){
                                        unset($result->$property);
                                        continue;
                                    }

                                }
                            }
                        }

                        if (in_array($property, $propertiesToLoad)) {
                            foreach ($this->$property as $object) {
                                if($permission == \Areanet\PIM\Entity\Permission::OWN && $object->getUserCreated() != $user){
                                    continue;
                                }
                                $data[] = $object->getId();
                            }
                        } else {


                            foreach ($this->$property as $object) {
                                if($permission == \Areanet\PIM\Entity\Permission::OWN && $object->getUserCreated() != $user){
                                    continue;
                                }

                                $data[] = $object->toValueObject($user, $schema, $subEntity, $flatten, $propertiesToLoad, ($level + 1));
                            }
                        }

                        $result->$property = $data;
                    }else{
                        $result->$property = $this->$getter();
                    }
                }
            }else{
                $getter = 'get' . ucfirst($property);

                if (method_exists($this, $getter)) {
                    if ($this->$property instanceof \Datetime) {
                        $res = $this->$property->format('Y');
                        if ($this->$property->format('Y') == '-0001' || $this->$property->format('Y') == '0000') {
                            $result->$property = array(
                                'LOCAL_TIME' => null,
                                'LOCAL' => null,
                                'ISO8601' => null,
                                'IMESTAMP' => null
                            );
                        } else {
                            $result->$property = array(
                                'LOCAL_TIME' => $this->$property->format('d.m.Y H:i'),
                                'LOCAL' => $this->$property->format('d.m.Y'),
                                'ISO8601' => $this->$property->format(\DateTime::ISO8601),
                                'TIMESTAMP' => $this->$property->getTimestamp()
                            );
                        }
                    }elseif ($this->$property instanceof \Datetime) {
                        $res = $this->$property->format('Y');
                        if ($this->$property->format('Y') == '-0001' || $this->$property->format('Y') == '0000') {
                            $result->$property = array(
                                'LOCAL_TIME' => null,
                                'LOCAL' => null,
                                'ISO8601' => null,
                                'IMESTAMP' => null
                            );
                        } else {
                            $result->$property = array(
                                'LOCAL_TIME' => $this->$property->format('d.m.Y H:i'),
                                'LOCAL' => $this->$property->format('d.m.Y'),
                                'ISO8601' => $this->$property->format(\DateTime::ISO8601),
                                'TIMESTAMP' => $this->$property->getTimestamp()
                            );
                        }
                    }elseif ($this->$property instanceof \Doctrine\ORM\PersistentCollection) {
                        $permission = \Areanet\PIM\Entity\Permission::ALL;

                        if($config['type'] == 'multifile'){
                            if (!($permission = Permission::isReadable($user, 'PIM\\File'))) {
                                unset($result->$property);
                                continue;
                            }
                        }else {
                            $config['accept'] = str_replace(array('Custom\\Entity\\', 'Areanet\\PIM\\Entity\\'), array('', 'PIM\\'), $config['accept']);

                            if (!($permission = Permission::isReadable($user, $config['accept']))) {
                                unset($result->$property);
                                continue;
                            }

                            if (isset($config['acceptFrom'])) {
                                $config['acceptFrom'] = str_replace(array('Custom\\Entity\\', 'Areanet\\PIM\\Entity\\'), array('', 'PIM\\'), $config['acceptFrom']);
                                if(!($permission = Permission::isReadable($user, $config['acceptFrom']))){
                                    unset($result->$property);
                                    continue;
                                }

                            }
                        }

                        $data = array();
                        foreach($this->$getter() as $object){
                            if($permission == \Areanet\PIM\Entity\Permission::OWN && $object->getUserCreated() != $user){
                                continue;
                            }
                            $data[] =  array(
                                "id"    => $object->getId()
                            );
                        }
                        $result->$property = $data;
                    }elseif($this->$property instanceof Base){
                        $config['accept'] = str_replace(array('Custom\\Entity\\', 'Areanet\\PIM\\Entity\\'), array('', 'PIM\\'), $config['accept']);
                        $permission = \Areanet\PIM\Entity\Permission::ALL;

                        if($config['type'] == 'file'){
                            if (!($permission = Permission::isReadable($user, 'PIM\\File'))) {
                                unset($result->$property);
                                continue;
                            }
                        }else {
                            if (!($permission = Permission::isReadable($user, $config['accept']))) {
                                unset($result->$property);
                                continue;
                            }
                        }

                        $subobject = $this->$getter();

                        if($permission == \Areanet\PIM\Entity\Permission::OWN && $subobject->getUserCreated() != $user){
                            unset($result->$property);
                            continue;
                        }

                        $result->$property = array(
                            "id" => $subobject->getId()
                        );
                    }else{
                        $result->$property = $this->$getter();

                    }

                }
            }

        }
        return $result;
    }
}