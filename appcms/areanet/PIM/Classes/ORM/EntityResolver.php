<?php
namespace Areanet\PIM\Classes\ORM;

class EntityResolver extends \Doctrine\ORM\Mapping\DefaultEntityListenerResolver{

    private $mapping = array('Areanet\PIM\Entity\User' => 'Custom\Entiy\User');

    public function __construct()
    {

    }

    public function register($object)
    {
        die("test 2");
        if(isset($this->mapping[$className])){
            $className = $this->mapping[$className];
        }

        return parent::resolve($className);
    }

    public function resolve($className)
    {
        die("test");
        if(isset($this->mapping[$className])){
            $className = $this->mapping[$className];
        }

        return parent::resolve($className);
    }
}