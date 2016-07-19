<?php
/**
 * Created by PhpStorm.
 * User: ms
 * Date: 19.07.16
 * Time: 15:51
 */

namespace Custom\Command;

use Areanet\PIM\Classes\Command\CustomCommand;
use Areanet\PIM\Classes\Config\Adapter;
use Areanet\PIM\Classes\File\Backend;
use Areanet\PIM\Classes\File\BackendInterface;
use Areanet\PIM\Classes\File\Processing;
use Areanet\PIM\Entity\File;
use Custom\Entity\ProduktDetailbilder;
use Custom\Entity\ProduktDigitalvorlagen;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class SizeImport extends CustomCommand
{
    protected function configure()
    {
        parent::configure();

        $this->backend = Backend::getInstance();

        $this
            ->setName('size:import')
            ->setDescription('Importieren der Dateigrößen (Länge/Breite)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->getSilexApplication();
        $em = $app['orm.em'];
        
        $fileObjects = $em->getRepository('Areanet\\PIM\\Entity\\File')->findBy(array('isDeleted' => false));

        $backend = Backend::getInstance();
        $output->writeln('IMPORT GRÖßEN');

        foreach($fileObjects as $fileObject){
            $output->write('.');
            $fileName   = $backend->getUri($fileObject, null, null);

            list($width, $height) = getimagesize($fileName);
            if($width){
                $fileObject->setWidth($width);
            }
            if($height){
                $fileObject->setHeight($height);
            }

            $em->persist($fileObject);

        }

        $em->flush();
        $output->write('END');
    }
}