<?php
namespace Areanet\PIM\Command;

use Areanet\PIM\Entity\ThumbnailSetting;
use Areanet\PIM\Entity\User;
use Knp\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SetupCommand extends Command
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('appcms:setup')
            ->setDescription('Setup-Routine für APP-CMS')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app   = $this->getSilexApplication();
        $em    = $app['orm.em'];

        //Admin-Benutzer
        $admin = $em->getRepository('Areanet\PIM\Entity\User')->findOneBy(array('alias' => 'admin'));
        if(!$admin){
            $admin = new User();
        }

        $admin->setAlias("admin");
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

        $output->writeln("<info>APP-CMS Setup wurde erfolgreich durchgeführt. Login in das Backend mit Benutzer=admin und Passwort=admin!</info>");

    }
}