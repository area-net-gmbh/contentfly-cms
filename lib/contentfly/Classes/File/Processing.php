<?php
namespace Areanet\Contentfly\Classes\File;


class Processing
{
    static protected $mapping = array();

    static public function registerProcessor(ProcessingInterface $processor)
    {
        foreach($processor->getMimeTypes() as $mimeType){
            self::$mapping[$mimeType] = $processor;
        }

    }

    static public function getInstance($mimeType)
    {

        if(isset(self::$mapping[$mimeType])){
            return self::$mapping[$mimeType];
        }

        return new \Areanet\Contentfly\Classes\File\Processing\Standard();
    }
}