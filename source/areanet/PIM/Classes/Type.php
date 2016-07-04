<?php

namespace Areanet\PIM\Classes;


abstract class Type
{
    private $tab = null;

    public function __construct()
    {
        
    }

    public function toDatabase()
    {

    }

    public function renderJSON()
    {

    }

    public function getTab()
    {
        return $this->tab;
    }

    protected function addTab($key, $config){
        $this->tab = new \stdClass();
        $this->tab->key = $key;
        $this->tab->config = $config;
    }

    public function processSchema($key, $defaultValue, $propertyAnnotations)
    {
        $schema = array(
            'label' => $key
        );

        $schema = array(
            'showInList' => false,
            'listShorten' => 0,
            'readonly' => false,
            'hide' => false,
            'type' => $this->getAlias(),
            'dbtype' => $this->getAlias(),
            'label' => $key,
            'filter' => '',
            'foreign' => null,
            'tab' => 'default',
            'sortable' => false,
            'default' => $defaultValue,
            'isFilterable' => false
        );

        if(isset($propertyAnnotations['Areanet\\PIM\\Classes\\Annotations\\Config'])){


            //Areanet\\PIM\\Classes\\Annotations\\Config
            $annotations = $propertyAnnotations['Areanet\\PIM\\Classes\\Annotations\\Config'];

            if($annotations->label){
                $schema['label'] = $annotations->label;
            }

            if($annotations->isFilterable){
                $schema['isFilterable'] = $annotations->isFilterable;
            }

            if($annotations->listShorten){
                $schema['listShorten'] = $annotations->listShorten;
            }

            if($annotations->showInList){
                $schema['showInList'] = $annotations->showInList;
            }

            if($annotations->hide){
                $schema['hide'] = $annotations->hide;
            }

            if($annotations->tab){
                $schema['tab'] = $annotations->tab;
            }



            //\Doctrine\ORM\Mapping\Column
            if(isset($propertyAnnotations['Doctrine\\ORM\\Mapping\\Column'])){
                $annotations = $propertyAnnotations['Doctrine\\ORM\\Mapping\\Column'];

                if($annotations->length){
                    $schema['length'] = $annotations->length;
                }

                if($annotations->unique){
                    $schema['unique'] = $annotations->unique;
                }

                $schema['nullable'] = $annotations->nullable ? $annotations->nullable : false;
            }


            //\Doctrine\ORM\Mapping\Id
            if(isset($propertyAnnotations['Doctrine\\ORM\\Mapping\\Id'])){
                $schema['readonly'] = true;
            }


        }

        return $schema;
    }

    abstract public function doMatch($propertyAnnotations);
    abstract public function getAlias();
    abstract public function getAnnotationFile();
    
}