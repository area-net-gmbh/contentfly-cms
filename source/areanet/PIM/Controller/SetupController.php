<?php
namespace Areanet\PIM\Controller;

use Areanet\PIM\Classes\Controller\BaseController;
use Areanet\PIM\Entity\User;
use Symfony\Component\HttpFoundation\Response;

class SetupController extends BaseController
{

    public function setupAction()
    {


        $user = new User();
        $user->setAlias("admin");
        $user->setPass("admin");
        $user->setIsAdmin(true);

        $this->em->persist($user);
        $this->em->flush();

        return new Response('Das Setup wurde erfolgreich durchgeführt!', 201);
    }
}