<?php
/**
 * Created by PhpStorm.
 * User: ms
 * Date: 04.07.16
 * Time: 15:48
 */

namespace Areanet\PIM\Classes;


class TypeManager
{
    protected static $types = array();

    public static function registerType(Type $type){
        self::$types[$type->getAlias()] = $type;
    }

    public static function getTypes(){
        return self::$types;
    }

    public static function getType($alias){
        if(!isset(self::$types[$alias])){
            return null;
        }

        return self::$types[$alias];
    }
}