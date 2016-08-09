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

    protected static function is($mode, User $user, $entityName)
    {
        $method= 'get'.ucfirst($mode);

        if($user->getIsAdmin()) return 2;

        if($user->getGroup() == null) return 0;

        foreach($user->getGroup()->getPermissions() as $permission){
            if($permission->getEntityName() == $entityName){
                return $permission->$method();
            }
        }
        return false;
    }
}