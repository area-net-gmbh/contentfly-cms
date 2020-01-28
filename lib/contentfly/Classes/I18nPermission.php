<?php
namespace Areanet\Contentfly\Classes;

use Areanet\Contentfly\Entity\Group;
use Areanet\Contentfly\Entity\User;
use Silex\Application;

class I18nPermission
{
    const IS_READABLE       = 'readable';
    const IS_TRANSLATABALE  = 'translatable';

    public static function isWritable(Application $app, $entityName, $lang){
        /** @var $user User */
        $user   = $app['auth.user'];
        $schema = $app['schema'];

        $helper = new Helper();
        $entityShortName = $helper->getShortEntityName($entityName);
        $entitySchema = $schema[$entityShortName];

        if($user->getIsAdmin()){
            return true;
        }

        /** @var $group Group */
        if(!($group = $user->getGroup())){
            return false;
        }

        if(empty($entitySchema['settings']['i18n'])){
            return true;
        }

        return $group->langIsWritable($lang);

    }

    public static function isTranslatable( Application $app, $entityName, $lang){
        /** @var $user User */
        $user   = $app['auth.user'];
        $schema = $app['schema'];

        $helper = new Helper();
        $entityShortName = $helper->getShortEntityName($entityName);
        $entitySchema = $schema[$entityShortName];

        if($user->getIsAdmin()){
            return true;
        }

        /** @var $group Group */
        if(!($group = $user->getGroup())){
            return false;
        }

        if(empty($entitySchema['settings']['i18n'])){
            return true;
        }

        return $group->langIsTranslatable($lang);

    }

    public static function isOnlyReadable( Application $app, $entityName, $lang){
        /** @var $user User */
        $user   = $app['auth.user'];
        $schema = $app['schema'];

        $helper = new Helper();
        $entityShortName = $helper->getShortEntityName($entityName);
        $entitySchema = $schema[$entityShortName];

        if($user->getIsAdmin()){
            return false;
        }

        /** @var $group Group */
        if(!($group = $user->getGroup())){
            return true;
        }

        if(empty($entitySchema['settings']['i18n'])){
            return false;
        }

        return $group->langisOnlyReadable($lang);

    }

}