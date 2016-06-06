<?php
namespace Areanet\PIM\Controller;

use Areanet\PIM\Classes\Controller\BaseController;
use Areanet\PIM\Entity\ThumbnailSetting;
use Areanet\PIM\Entity\User;
use Symfony\Component\HttpFoundation\Response;

class SetupController extends BaseController
{

    public function setupAction()
    {
       
        $user = new User();
        $user->setAlias("admin2");
        $user->setPass("admin2");
        $user->setIsAdmin(true);

        $this->em->persist($user);
        $this->em->flush();

        
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
        
        return new Response('Das Setup wurde erfolgreich durchgeführt!', 201);
    }
}