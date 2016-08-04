<?php
namespace Areanet\PIM\Classes;

use Areanet\PIM\Entity\User;
use Silex\Application;

class Permission
{
    public static function isReadable(User $user, $entityName){
        if($user->getIsAdmin()) return true;

        if($user->getGroup() == null) return false;

        foreach($user->getGroup()->getPermissions() as $permission){
            if($permission->getEntityName() == $entityName){
                return $permission->getReadable();
            }
        }

        return false;
    }
}