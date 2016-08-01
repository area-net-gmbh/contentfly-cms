<?php
namespace Areanet\PIM\Entity;

use Areanet\PIM\Classes\Config\Adapter;

abstract class Serializable implements \JsonSerializable{

    function jsonSerialize()
    {
        return $this->toValueObject();
    }

    public function toValueObject($flatten = false, $propertiesToLoad = array(), $level = 0)
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
                    }
                    elseif($this->$property instanceof Base && $property != 'user') {
                        $getterName = 'get' . ucfirst($property);
                        $result->$property = $this->$getterName()->toValueObject($flatten, $propertiesToLoad, ($level + 1));
                    }elseif($this->$property instanceof \Doctrine\ORM\PersistentCollection) {
                        $data = array();
                        if(in_array($property, $propertiesToLoad)){

                            foreach ($this->$property as $object) {
                                $data[] =  $object->getId();
                            }
                        }else{
                            foreach ($this->$property as $object) {
                                $data[] = $object->toValueObject($flatten, $propertiesToLoad,  ($level + 1));
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
                    if ($this->$property instanceof \Doctrine\ORM\PersistentCollection) {
                        $data = array();
                        foreach($this->$getter() as $object){
                            $data[] =  $object->getId();
                        }
                        $result->$property = $data;
                    }elseif($this->$property instanceof Base){
                        $result->$property = $this->$getter()->getId();
                    }else{
                        $result->$property = $this->$getter();

                    }

                }
            }

        }
        return $result;
    }
}