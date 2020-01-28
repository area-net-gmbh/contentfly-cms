<?php
namespace Areanet\Contentfly\Command;

use Areanet\Contentfly\Entity\ThumbnailSetting;
use Areanet\Contentfly\Entity\User;
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

        $app['helper']->install($em);

        $output->writeln("<info>APP-CMS Setup wurde erfolgreich durchgeführt. Login in das Backend mit Benutzer=admin und Passwort=admin!</info>");

    }
}