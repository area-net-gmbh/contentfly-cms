<?php
/**
 * Created by PhpStorm.
 * User: ms
 * Date: 30.08.16
 * Time: 10:01
 */

namespace Areanet\PIM\Classes;


use Areanet\PIM\Entity\Base;
use Areanet\PIM\Entity\ThumbnailSetting;
use Areanet\PIM\Entity\User;
use Doctrine\ORM\EntityManager;

class Helper
{

    public function convertMomentFormatToPhp($format){
        $replacements = [
            'DD'   => 'd',
            'ddd'  => 'D',
            'D'    => 'j',
            'dddd' => 'l',
            'E'    => 'N',
            'o'    => 'S',
            'e'    => 'w',
            'DDD'  => 'z',
            'W'    => 'W',
            'MMMM' => 'F',
            'MM'   => 'm',
            'MMM'  => 'M',
            'M'    => 'n',
            'YYYY' => 'Y',
            'YY'   => 'y',
            'a'    => 'a',
            'A'    => 'A',
            'h'    => 'g',
            'H'    => 'G',
            'hh'   => 'h',
            'HH'   => 'H',
            'mm'   => 'i',
            'ss'   => 's',
            'SSS'  => 'u',
            'zz'   => 'e',
            'X'    => 'U',
        ];

        $phpFormat = strtr($format, $replacements);

        return $phpFormat;
    }

    public function getFullEntityName($entityName)
    {
        $entityFullName = null;

        if (substr($entityName, 0, 8) == 'Areanet\\' || substr($entityName, 0, 7) == 'Custom\\' || substr($entityName, 0, 8) == 'Plugins\\') {
            $entityFullName = $entityName;
        }elseif(substr($entityName, 0, 3) == 'PIM') {
            $entityFullName = 'Areanet\PIM\Entity\\' . substr($entityName, 4);
        }else{
            $entityFullName = 'Custom\Entity\\' . ucfirst($entityName);
        }

        return $entityFullName;
    }

    public function getShortEntityName($entityName)
    {
        $entityShortName = null;

        if (substr($entityName, 0, 8) == 'Areanet\\') {
            $entityShortName = 'PIM\\' . substr($entityName, 19);
        }elseif(substr($entityName, 0, 7) == 'Custom\\') {
            $entityShortName = substr($entityName, 14);
        }else{
            $entityShortName = ucfirst($entityName);
        }

        return $entityShortName;
    }

    public function getEntityName($entityName)
    {
        $entityNames = explode('\\', $entityName);

        return array_pop($entityNames);
    }

    public function getUsersRemoved(Base $currentObject, array $newData){

        $usersRemoved = array();

        if(isset($newData['userCreated'])){
            $userCreatedNewId = is_array($newData['userCreated']) ? $newData['userCreated']['id'] : $newData['userCreated'];

            if($currentObject->getUserCreated() && $currentObject->getUserCreated()->getId() != $userCreatedNewId){
                $usersRemoved[] = $currentObject->getUserCreated()->getId();
            }
        }

        if(isset($newData['users']) && $currentObject->getUsers(true)){
            $usersOldArr    = explode(',', $currentObject->getUsers(true));
            $usersNewArr    = explode(',', $newData['users']);

            $usersToRemove  = array_diff($usersOldArr, $usersNewArr);
            $usersRemoved   = array_merge($usersRemoved, $usersToRemove);
        }

        return $usersRemoved;
    }

    public function install(EntityManager $em){
        //Admin-Benutzer
        $admin = $em->getRepository('Areanet\PIM\Entity\User')->findOneBy(array('alias' => 'admin'));
        if(!$admin){
            $admin = new User();
        }

        $admin->setAlias("admin");
        $admin->setLoginManager('');
        $admin->setPass("admin");
        $admin->setIsAdmin(true);

        $em->persist($admin);

        //Bildgrößen
        $sizeList = $em->getRepository('Areanet\PIM\Entity\ThumbnailSetting')->findOneBy(array('alias' => 'pim_list'));
        if(!$sizeList){
            $sizeList = new ThumbnailSetting();
        }

        $sizeList->setAlias('pim_list');
        $sizeList->setWidth(200);
        $sizeList->setHeight(200);
        $sizeList->setDoCut(true);
        $sizeList->setIsIntern(true);

        $em->persist($sizeList);

        $sizeSmall = $em->getRepository('Areanet\PIM\Entity\ThumbnailSetting')->findOneBy(array('alias' => 'pim_small'));
        if(!$sizeSmall){
            $sizeSmall = new ThumbnailSetting();
        }

        $sizeSmall->setAlias('pim_small');
        $sizeSmall->setAlias('pim_small');
        $sizeSmall->setWidth(320);
        $sizeSmall->setIsIntern(true);

        $em->persist($sizeSmall);

        $em->flush();

        $this->createSymlinks();
    }

    public function createSymlinks(){
        $this->createSymlink(ROOT_DIR.'/public/custom/', 'Frontend', '../../../custom/Frontend');
        $this->createSymlink(ROOT_DIR.'/public/ui/', 'default', '../../areanet/PIM-UI/default/assets');
    }

    public function createSymlink($path, $target, $link){
        if(!is_link($path.$target)){
            $this->deleteFolder($path.$target);

            if(!is_dir($path)){
                mkdir($path);
            }

            if(!chdir($path)){
                return array('symlink', "chdir to $path failed.");
            }
            if(!symlink($link, $target)){
                return array('symlink', "symlink $path.$target failed.");
            }
        }

        return array();
    }

    protected function deleteFolder($dir) {
        if(!file_exists($dir)){
            return null;
        }
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->deleteFolder("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }
}