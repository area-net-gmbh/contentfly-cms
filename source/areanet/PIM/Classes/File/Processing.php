<?php
namespace Areanet\PIM\Classes\File;


class Processing
{
    static protected $mapping = array();

    static public function registerProcessor($mimeType, $className)
    {
        self::$mapping[$mimeType] = $className;
    }

    static public function getInstance($mimeType)
    {

        if(isset(self::$mapping[$mimeType])){
            $class = self::$mapping[$mimeType];
            return new $class();
        }

        return new \Areanet\PIM\Classes\File\Processing\Standard();
    }
}