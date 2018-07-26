<?php
namespace Areanet\PIM\Classes;

use Areanet\PIM\Entity\User;
use Silex\Application;

class Permission
{
    public static function isReadable(User $user, $entityName){
        return self::is('readable', $user, $entityName);
    }

    public static function isWritable(User $user, $entityName){
        return self::is('writable', $user, $entityName);
    }

    public static function isDeletable(User $user, $entityName){
        return self::is('deletable', $user, $entityName);
    }

    public static function canExport(User $user, $entityName){
        $entityName = str_replace(array('Custom\\Entity\\', 'Areanet\\PIM\\Entity\\'), array('', 'PIM\\'), $entityName);

        if($user->getIsAdmin()) return true;
        if($user->getGroup() === null) return false;

        foreach($user->getGroup()->getPermissions() as $permission){

            if($permission->getEntityName() == $entityName){

                return ($permission->getExport() == 2);
            }
        }

        return false;
    }

    public static function getExtended(User $user, $entityName){
        $entityName = str_replace(array('Custom\\Entity\\', 'Areanet\\PIM\\Entity\\'), array('', 'PIM\\'), $entityName);

        if($user->getIsAdmin()) return null;

        $group = $user->getGroup();
        if(!$group){
            return null;
        }
        
        foreach($user->getGroup()->getPermissions() as $permission){
            if($permission->getEntityName() == $entityName){
                $extended = $permission->getExtended();
                if(empty($extended)){
                    return null;
                }

                return json_decode($extended);
            }
        }

        return null;
    }

    protected static function is($mode, User $user, $entityName)
    {
        $entityName = str_replace(array('Custom\\Entity\\', 'Areanet\\PIM\\Entity\\'), array('', 'PIM\\'), $entityName);

        $method= 'get'.ucfirst($mode);

        if($user->getIsAdmin()) return 2;

        if($user->getGroup() === null) return 0;

        foreach($user->getGroup()->getPermissions() as $permission){
            if($permission->getEntityName() == $entityName){
                return $permission->$method();
            }
        }
        return false;
    }
}