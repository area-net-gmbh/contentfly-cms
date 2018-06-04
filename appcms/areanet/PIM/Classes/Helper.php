<?php
/**
 * Created by PhpStorm.
 * User: ms
 * Date: 30.08.16
 * Time: 10:01
 */

namespace Areanet\PIM\Classes;


use Areanet\PIM\Entity\ThumbnailSetting;
use Areanet\PIM\Entity\User;
use Doctrine\ORM\EntityManager;

class Helper
{
    public function getFullEntityName($entityShortName)
    {
        if (substr($entityShortName, 0, 3) == 'PIM') {
            $entityNameToLoad = 'Areanet\PIM\Entity\\' . substr($entityShortName, 4);
        } else {
            $entityNameToLoad = 'Custom\Entity\\' . ucfirst($entityShortName);
        }

        return $entityNameToLoad;
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