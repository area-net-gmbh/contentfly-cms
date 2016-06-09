<?php
namespace Custom\Command;

use Areanet\PIM\Classes\Command\CustomCommand;
use Custom\Entity\Filteroption;
use Custom\Entity\Hinweistext;
use Custom\Entity\Produkt;
use Custom\Entity\ProduktAlternativprodukte;
use Custom\Entity\ProduktBeschreibung;
use Custom\Entity\ProduktPreise;
use Custom\Entity\ProduktWebinformationen;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Driver\PDOException;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DatabaseImport extends CustomCommand
{
    /** @var EntityManager $em */
    protected $em;

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('mysql:import')
            ->setDescription('Importieren der Mysql-Datenbank')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app        = $this->getSilexApplication();
        $this->em   = $app['orm.em'];

        $query          = $this->em->createQuery('DELETE FROM Custom\Entity\Produkt');
        $numDeleted     = $query->execute();

        $query = $this->em->createQuery('DELETE FROM Custom\Entity\ProduktMetainformationen');
        $query->execute();

        $query = $this->em->createQuery('DELETE FROM Custom\Entity\ProduktPreise');
        $query->execute();

        $query = $this->em->createQuery('DELETE FROM Custom\Entity\ProduktWebinformationen');
        $query->execute();

        $query = $this->em->createQuery('DELETE FROM Custom\Entity\ProduktBeschreibung');
        $query->execute();

        $query = $this->em->createQuery('DELETE FROM Custom\Entity\Hinweistext');
        $query->execute();

        $query = $this->em->createQuery('DELETE FROM Custom\Entity\Filteroption');
        $query->execute();


        $numImported    = 0;

        $importDB = $this->initDB();

        //Import Produkte
        $output->writeln('Import Produkte');
        $sql = "SELECT * FROM const_artikel";
        $stmt = $importDB->query($sql);

        $mapping = array();

        while ($row = $stmt->fetch()) {
            $numImported++;
            $output->write('.');
            //Preise
            $produktPreise = new ProduktPreise();
            $produktPreise->setKalkModus($row['kalk_modus']);
            if(!empty($row['ab_pr'])) $produktPreise->setAbPreis($row['ab_pr']);
            if(!empty($row['ab_prx'])) $produktPreise->setHerbstpreis($row['ab_prx']);
            $produktPreise->setAbPreisText($row['ab_text']);
            if($row['prx_beginn'] && $row['prx_beginn'] != '0000-00-00 00:00:00'){
                $date = new \DateTime($row['prx_beginn']);
                $produktPreise->setBeginnHerbstpreise($date);
            }
            if($row['prx_ende'] && $row['prx_ende'] != '0000-00-00 00:00:00'){
                $date = new \DateTime($row['prx_ende']);
                $produktPreise->setEndeHerbstpreise($date);
            }
            $this->em->persist($produktPreise);

            //Beschreibung
            $produktBeschreibungen = new ProduktBeschreibung();
            $produktBeschreibungen->setBeschreibung($row['text']);
            $produktBeschreibungen->setFormat1($row['format']);
            $produktBeschreibungen->setWerbeflaeche1($row['werbeflaeche']);
            $this->em->persist($produktBeschreibungen);

            //Produkt
            $produkt = new Produkt();
            $produkt->setVersteckt($row['versteckt']);
            $produkt->setArtikel($row['artikel']);
            $produkt->setKuerzel($row['kuerzel']);
            $produkt->setTitel($row['name']);
            $produkt->setKeywords($row['keywords']);
            $produkt->setPreise($produktPreise);
            $produkt->setBeschreibung($produktBeschreibungen);

            $this->em->persist($produkt);
            $this->em->flush();

            $mapping[$row['artikel']] = $produkt;
        }
        $output->writeln('');

        //Import Alternativartikel
        $output->writeln('Import Alternativartikel...');
        $sql = "SELECT * FROM var_alternativartikel";
        $stmt = $importDB->query($sql);

        while ($row = $stmt->fetch()) {
            if(!isset($mapping[$row['artikel_id']]) || !isset($mapping[$row['alternative_id']])){
                continue;
            }
            $output->write('.');
            $alternativArtikel = new ProduktAlternativprodukte();
            $alternativArtikel->setProdukt($mapping[$row['artikel_id']]);
            $alternativArtikel->setAlternativprodukt($mapping[$row['alternative_id']]);
            $alternativArtikel->setSorting($row['sortkey']);
            $this->em->persist($alternativArtikel);

        }
        $output->writeln('');
        $this->em->flush();

        //Import Artikelseiten
        $output->writeln('Import Artikelseiten...');
        $sql = "SELECT * FROM var_artikelseiten WHERE block_teaser_text <> '' ORDER BY ts DESC";
        $stmt = $importDB->query($sql);


        while ($row = $stmt->fetch()) {
            if(!isset($mapping[$row['artikel']]) || $mapping[$row['artikel']]->getWebinformationen()){
                continue;
            }
            $output->write('.');
            $webinfos = new ProduktWebinformationen();
            $webinfos->setTeaserText($row['block_teaser_text']);
            $webinfos->setMarketingText($row['block_marketing_text']);
            $webinfos->setIstFsc($row['img_fsc']);
            $webinfos->setIstExpress($row['express']);
            $webinfos->setIstBWare($row['bware']);
            $webinfos->setBWareRabatt($row['bware_rabatt']);
            $this->em->persist($webinfos);

            $mapping[$row['artikel']]->setWebinformationen($webinfos);
        }
        $output->writeln('');

        //Import Artikelverfügbarkeit
        $output->writeln('Import Artikelverfügbarkeit...');
        $sql = "SELECT * FROM var_artikelstati";
        $stmt = $importDB->query($sql);
        while ($row = $stmt->fetch()) {
            if(!isset($mapping[$row['artikel']])){
                continue;
            }
            $output->write('.');
            $mapping[$row['artikel']]->setVerfuegbarkeit($row['verfuegbarkeit']);
            $mapping[$row['artikel']]->setAktiv(!$row['ausgeblendet']);
        }
        $output->writeln('');
        $this->em->flush();
        $this->em->clear();

        //Import Hinweistexte
        $output->writeln('Import Hinweistexte...');
        $sql = "SELECT * FROM const_zusatzinfos";
        $stmt = $importDB->query($sql);

        while ($row = $stmt->fetch()) {
            $output->write('.');
            $hinweistext = new Hinweistext();
            $hinweistext->setTitel($row['titel']);
            $hinweistext->setText($row['text']);
            $this->em->persist($hinweistext);
        }
        $output->writeln('');
        $this->em->flush();

        //Import Filteroptionen
        $output->writeln('Import Filteroptionen 1...');
        $sql = "SELECT * FROM const_uebersichten2artikel_neu";
        $stmt = $importDB->query($sql);

        while ($row = $stmt->fetch()) {
            $output->write('.');

            if(!empty($row['filter1_wert'])){
                $existingObject = $this->em->getRepository('Custom\Entity\Filteroption')->findOneBy(array('titel' => substr($row['filter1_wert'], 3)));
                if(!$existingObject) {
                    $object = new Filteroption();
                    $object->setTitel(substr($row['filter1_wert'], 3));
                    $this->em->persist($object);
                    $this->em->flush();
                }
            }

            if(!empty($row['filter2_wert'])){
                $existingObject = $this->em->getRepository('Custom\Entity\Filteroption')->findOneBy(array('titel' =>  substr($row['filter2_wert'], 3)));
                if(!$existingObject) {
                    $object = new Filteroption();
                    $object->setTitel(substr($row['filter2_wert'], 3));
                    $this->em->persist($object);
                    $this->em->flush();
                }
            }

            if(!empty($row['filter3_wert'])){
                $existingObject = $this->em->getRepository('Custom\Entity\Filteroption')->findOneBy(array('titel' => substr($row['filter3_wert'], 3)));
                if(!$existingObject) {
                    $object = new Filteroption();
                    $object->setTitel(substr($row['filter3_wert'], 3));
                    $this->em->persist($object);
                    $this->em->flush();
                }
            }

        }




        $output->writeln("$numDeleted Produkte gelöscht und $numImported Produkte importiert!");

    }

    protected function initDB()
    {
        $config = new \Doctrine\DBAL\Configuration();
        $connectionParams = array(
            'url' => 'mysql://p212925d2:ifAnaboh.678@db1246.mydbserver.com/usr_p212925_4',
        );
        return  \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);
    }
}