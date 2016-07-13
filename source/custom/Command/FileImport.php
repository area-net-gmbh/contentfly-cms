<?php
/**
 * Created by PhpStorm.
 * User: ms
 * Date: 12.07.16
 * Time: 14:25
 */

namespace Custom\Command;

use Areanet\PIM\Classes\Command\CustomCommand;
use Areanet\PIM\Classes\File\Backend;
use Areanet\PIM\Classes\File\BackendInterface;
use Areanet\PIM\Classes\File\Processing;
use Areanet\PIM\Entity\File;
use Custom\Entity\ProduktDetailbilder;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FileImport extends CustomCommand
{
    /** @var EntityManager $em */
    protected $em;

    /** @var BackendInterface $em */
    protected $backend;

    protected function configure()
    {
        parent::configure();

        $this->backend = Backend::getInstance();

        $this
            ->setName('file:import')
            ->setDescription('Importieren der Dateien (Bilder, Datenblätter)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->getSilexApplication();
        $this->em = $app['orm.em'];

        $cachedProducts = array();

        $imagesPath = ROOT_DIR.'/data/import/artikelbilder/';
        $output->writeln('BILDER-IMPORT: '.$imagesPath);
        $count = 0;
        foreach (new \DirectoryIterator($imagesPath) as $fileInfo) {
            if($fileInfo->isDot() || !$fileInfo->isFile()) continue;

            $count++;

            $artikel  = $fileInfo->getBasename('.'.$fileInfo->getExtension());
            $fileName = $fileInfo->getFilename();
            $alias    = null;
            $sorting  = 0;

            if(strpos($fileInfo->getBasename(), "_") !== false){
                $parts      = explode('_', $fileInfo->getBasename('.'.$fileInfo->getExtension()));
                $artikel    = $parts[0];
                $fileName   = $parts[0].'_'.$parts[1].'.'.$fileInfo->getExtension();
                $sorting    = intval($parts[1]);
                $alias      = $fileInfo->getBasename();
            }

            $fileObject = $this->em->getRepository('Areanet\PIM\Entity\File')->findOneBy(array('name' => $fileName));
            if(!$fileObject){
                $fileObject = new File();
                $fileObject->setName($fileName);
            }
    
            $fileObject->setAlias($alias);
            $fileObject->setType(mime_content_type($fileInfo->getPathName()));
            $fileObject->setSize(filesize($fileInfo->getPathName()));
            $fileObject->setHash(md5_file($fileInfo->getPathname()));
            $this->em->persist($fileObject);
            $this->em->flush();

            //Bild erstellen
            $this->processFile($fileInfo, $fileObject);
            
            //Bild verknüpfen
            $product = null;
            if(isset($cachedProducts[$artikel])){
                $product = $cachedProducts[$artikel];
            }else{
                $product = $this->em->getRepository('Custom\Entity\Produkt')->findOneBy(array('artikel' => $artikel));
                if(!$product) continue;
                $cachedProducts[$artikel] = $product;
            }

            $detailbild = $this->em->getRepository('Custom\Entity\ProduktDetailbilder')->findOneBy(array('produkt' => $product, 'bild' => $fileObject));
            if(!$detailbild) {
                $detailbild = new ProduktDetailbilder();
            }
            $detailbild->setProdukt($product);
            $detailbild->setBild($fileObject);
            $detailbild->setSorting($sorting);

            $this->em->persist($detailbild);
            $this->em->flush();

        }
        $output->writeln("=> $count Bilder importiert.");


        $filePath = ROOT_DIR.'/data/import/digitalvorlagen/';
        $output->writeln('DIGITALVORLAGEN-IMPORT: '.$filePath);
        $count = 0;
        foreach (new \DirectoryIterator($imagesPath) as $fileInfo) {
            if ($fileInfo->isDot() || !$fileInfo->isFile()) continue;
            $count++;
        }
        $output->writeln("=> $count Digitalvorlagen importiert.");
        $this->em->flush();

        $filePath = ROOT_DIR.'/data/import/datenblaetter/';
        $output->writeln('DATENBLÄTTER-IMPORT: '.$filePath);
        $count = 0;
        foreach (new \DirectoryIterator($filePath) as $fileInfo) {
            if ($fileInfo->isDot() || !$fileInfo->isFile()) continue;

            $count++;

            $artikel  = $fileInfo->getBasename('.'.$fileInfo->getExtension());
            $fileName = 'datenblatt_'.$fileInfo->getFilename();

            $fileObject = $this->em->getRepository('Areanet\PIM\Entity\File')->findOneBy(array('name' => $fileName));
            if(!$fileObject){
                $fileObject = new File();
                $fileObject->setName($fileName);
            }

            $fileObject->setType(mime_content_type($fileInfo->getPathName()));
            $fileObject->setSize(filesize($fileInfo->getPathName()));
            $fileObject->setHash(md5_file($fileInfo->getPathname()));
            $this->em->persist($fileObject);
            $this->em->flush();

            //Datei erstellen
            $this->processFile($fileInfo, $fileObject);

            //Datei verknüpfen
            $product = null;
            if(isset($cachedProducts[$artikel])){
                $product = $cachedProducts[$artikel];
            }else{
                $product = $this->em->getRepository('Custom\Entity\Produkt')->findOneBy(array('artikel' => $artikel));
                if(!$product) continue;
                $cachedProducts[$artikel] = $product;
            }

            $product->setTechnischesDatenblatt($fileObject);
            $this->em->persist($product);
            
        }
        $output->writeln("=> $count Datenblätter importiert.");
        $this->em->flush();
    }


    protected function processFile(\DirectoryIterator $fileInfo, File $fileObject){

        rename($fileInfo->getPathName(), $this->backend->getPath($fileObject).'/'.$fileObject->getName());
        $processor = Processing::getInstance($fileObject->getType());
        $processor->execute($this->backend, $fileObject);

    }
}