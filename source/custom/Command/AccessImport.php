<?php
namespace Custom\Command;

use Areanet\PIM\Classes\Command\CustomCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AccessImport extends CustomCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('access:import')
            ->setDescription('Importieren der Access-Datenbank')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("OLE IMPORTIERT!");
    }
}