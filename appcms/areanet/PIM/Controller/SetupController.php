<?php
namespace Areanet\PIM\Controller;

use Areanet\PIM\Classes\Config\Adapter;
use Areanet\PIM\Classes\Controller\BaseController;
use Areanet\PIM\Entity\Folder;
use Areanet\PIM\Entity\ThumbnailSetting;
use Areanet\PIM\Entity\User;
use Symfony\Component\HttpFoundation\Response;

class SetupController extends BaseController
{

    public function setupAction()
    {

        if(!Adapter::getConfig()->DO_INSTALL){
            return $this->app->redirect(Adapter::getConfig()->FRONTEND_URL, 303);
        }

        try {
            $user = new User();
            $user->setAlias("admin");
            $user->setPass("admin");
            $user->setIsAdmin(true);

            $this->em->persist($user);
            $this->em->flush();

            //$folder = new Folder();
            //$folder->setId(1);
            //$folder->setTitle('Allgemein');
            //$this->em->getClassMetaData(get_class($folder))->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);
            //$this->em->persist($folder);

            $size = new ThumbnailSetting();
            $size->setAlias('pim_list');
            $size->setWidth(200);
            $size->setHeight(200);
            $size->setDoCut(true);
            $size->setIsIntern(true);
            $this->em->persist($size);

            $size = new ThumbnailSetting();
            $size->setAlias('pim_small');
            $size->setWidth(320);
            $size->setIsIntern(true);
            $this->em->persist($size);

            $this->em->flush();

            $configContent = file_get_contents(ROOT_DIR.'/../../custom/config.php');
            $configContent = str_replace('$configDefault->DO_INSTALL = true;', '', $configContent);
            file_put_contents(ROOT_DIR.'/../../custom/config.php', $configContent);
            @chmod(ROOT_DIR.'/../../custom/config.php', 0755);
            
        }catch(\Exception $e){};
        
        return $this->app->redirect(Adapter::getConfig()->FRONTEND_URL, 303);
    }
    
}