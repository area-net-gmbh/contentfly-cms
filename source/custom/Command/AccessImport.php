<?php
namespace Custom\Command;

use Areanet\PIM\Classes\Command\CustomCommand;
use Custom\Entity\Produkt;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AccessImport extends CustomCommand
{
    /** @var EntityManager $em */
    protected $em;

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
        $app        = $this->getSilexApplication();
        $this->em   = $app['orm.em'];

        $query = $this->em->createQuery('DELETE FROM Custom\Entity\Produkt');
        $numDeleted = $query->execute();

        $numImported = 0;
        $numBatch    = 0;
        $batchSize   = 20;

        if (($handle = fopen(ROOT_DIR.'/data/temp/artikel.csv', "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if(empty($data[0]) || empty($data[3]) || $data[1] == 'zubehoer') continue;

                $blockedChars = array('.', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
                if(in_array(substr($data[0], 0, 1), $blockedChars)) continue;

                $numBatch++;
                $numImported++;

                $produkt = new Produkt();
                $produkt->setFormnummer($data[0]);
                $produkt->setKuerzel($data[3]);
                $produkt->setTitel($data[2]);
                $this->em->persist($produkt);
                if ($numBatch >= $batchSize) {
                    $this->em->flush();
                    $this->em->clear();
                    $numBatch = 0;
                }
            }

            $this->em->flush();
            $this->em->clear();

        }

        $output->writeln("$numDeleted Produkte gelöscht und $numImported Produkte importiert!");
    }
}