<?php
/**
 * Created by PhpStorm.
 * User: ms
 * Date: 12.07.16
 * Time: 14:25
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

class FileImport extends CustomCommand
{
    const IMPORT_FOLDER = "import";

    /** @var EntityManager */
    protected $em;

    /** @var BackendInterface */
    protected $backend;

    /** @var array */
    protected $cachedProducts = array();

    /** @var array */
    protected $cachedTitles = array();

    /** @var string */
    protected $rootDir = ROOT_DIR.'/data/';
    
    protected function configure()
    {
        parent::configure();

        $this->backend = Backend::getInstance();

        $this
            ->setName('file:import')
            ->setDescription('Importieren der Dateien (Bilder, Datenblätter)')
            ->addOption(
                'oRoot',
                null,
                InputOption::VALUE_REQUIRED,
                'Root-Import-Ordner',
                $this->rootDir
            )
            ->addOption(
                'oBilder',
                null,
                InputOption::VALUE_REQUIRED,
                'ID Ordner Bilder',
                Adapter::getConfig()->CUSTOM_IMPORT_FOLDER_BILDER
            )
            ->addOption(
                'oDatenblaetter',
                null,
                InputOption::VALUE_REQUIRED,
                'ID Ordner Datenblaetter',
                Adapter::getConfig()->CUSTOM_IMPORT_FOLDER_DATENBLAETTER
            )
            ->addOption(
                'oDigitalvorlagen',
                null,
                InputOption::VALUE_REQUIRED,
                'ID Ordner Digitalvorlagen',
                Adapter::getConfig()->CUSTOM_IMPORT_FOLDER_DIGITALVORLAGEN
            )
            ->addOption(
                'xBilder',
                null,
                InputOption::VALUE_NONE,
                'Bilder ausschließen'
            )
            ->addOption(
                'xDatenblaetter',
                null,
                InputOption::VALUE_NONE,
                'Datenblaetter ausschließen'
            )
            ->addOption(
                'xDigitalvorlagen',
                null,
                InputOption::VALUE_NONE,
                'Digitalvorlagen ausschließen'
            )
            ->addOption(
                'copy',
                null,
                InputOption::VALUE_NONE,
                'Dateien nicht verschieben, sondern kopieren'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->getSilexApplication();
        $this->em = $app['orm.em'];

        $doCopy         = $input->getOption('copy');
        $this->rootDir  = $input->getOption('oRoot');

        $importDB = $this->initDB();
        $sql = "SELECT artikel, block_name FROM var_artikelseiten WHERE block_name <> '' ORDER BY ts DESC";
        $stmt = $importDB->query($sql);


        while ($row = $stmt->fetch()) {
            if(isset($this->cachedTitles[$row['artikel']])){
                continue;
            }

            $this->cachedTitles[$row['artikel']] = str_replace('&quot;', '"', $row['block_name']);
        }

        if (!$input->getOption('xBilder')) {
            $folder = $input->getOption('oBilder');
            $this->importBilder($output, $folder, $doCopy);
        }

        if (!$input->getOption('xDatenblaetter')) {
            $folder = $input->getOption('oDatenblaetter');
            $this->importDatenblaetter($output, $folder, $doCopy);
        }

        if (!$input->getOption('xDigitalvorlagen')) {
            $folder = $input->getOption('oDigitalvorlagen');
            $this->importDigitalvorlagen($output, $folder, $doCopy);
        }
        
    }

    protected function importBilder(OutputInterface $output, $folder, $doCopy = false){

        $filePath = $this->rootDir.self::IMPORT_FOLDER.'/artikelbilder/';
        $output->writeln('BILDER-IMPORT: '.$filePath);

        $folderObject = $this->em->getRepository('Areanet\PIM\Entity\Folder')->find($folder);
        if(!$folderObject){
            $output->writeln("<error>Ordner $folder nicht vorhanden!</error>");
            return;
        }

        $count = 0;
        foreach (new \DirectoryIterator($filePath) as $fileInfo) {
            if($fileInfo->isDot() || !$fileInfo->isFile()) continue;

            $count++;
            $output->write('.');

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

            $fileObject = $this->em->getRepository('Areanet\PIM\Entity\File')->findOneBy(array('name' => $fileName, 'folder' => $folderObject));
            if(!$fileObject){
                $fileObject = new File();
                $fileObject->setName($fileName);
                $fileObject->setFolder($folderObject);
            }

            $fileObject->setAlias($alias);
            $fileObject->setType(mime_content_type($fileInfo->getPathName()));
            $fileObject->setSize(filesize($fileInfo->getPathName()));
            $fileObject->setHash(md5_file($fileInfo->getPathname()));

            if(isset($this->cachedTitles[$artikel])){
                $fileObject->setTitle($this->cachedTitles[$artikel]);
            }

            $this->em->persist($fileObject);
            $this->em->flush();

            //Bild erstellen
            $this->processFile($fileInfo, $fileObject, $doCopy);

            //Bild verknüpfen
            $product = null;
            if(isset($this->cachedProducts[$artikel])){
                $product = $this->cachedProducts[$artikel];
            }else{
                $product = $this->em->getRepository('Custom\Entity\Produkt')->findOneBy(array('artikel' => $artikel));
                if(!$product) continue;
                $this->cachedProducts[$artikel] = $product;
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
    }
    
    protected function importDigitalvorlagen(OutputInterface $output, $folder, $doCopy = false){

        $filePath = $this->rootDir.self::IMPORT_FOLDER.'/digitalvorlagen/';
        $output->writeln('DIGITALVORLAGEN-IMPORT: '.$filePath);


        $folderObject = $this->em->getRepository('Areanet\PIM\Entity\Folder')->find($folder);
        if(!$folderObject){
            $output->writeln("<error>Ordner $folder nicht vorhanden!</error>");
            return;
        }

        $count = 0;
        foreach (new \DirectoryIterator($filePath) as $fileInfo) {
            if ($fileInfo->isDot() || !$fileInfo->isDir()) continue;

            $artikel = $fileInfo->getBasename();

            $output->writeln('.');
            $count++;

            foreach (new \DirectoryIterator($fileInfo->getPathName()) as $subfileInfo) {
                if ($subfileInfo->isDot() || !$subfileInfo->isFile()) continue;

                $fileName = $subfileInfo->getFilename();

                $fileObject = $this->em->getRepository('Areanet\PIM\Entity\File')->findOneBy(array('name' => $fileName, 'folder' => $folderObject));
                if(!$fileObject){
                    $fileObject = new File();
                    $fileObject->setName($fileName);
                    $fileObject->setFolder($folderObject);
                }

                $fileObject->setType(mime_content_type($subfileInfo->getPathName()));
                $fileObject->setSize(filesize($subfileInfo->getPathName()));
                $fileObject->setHash(md5_file($subfileInfo->getPathname()));
                $this->em->persist($fileObject);
                $this->em->flush();


                $count++;

                //Datei erstellen
                
                $this->processFile($subfileInfo, $fileObject, $doCopy);

                $product = null;
                if(isset($this->cachedProducts[$artikel])){
                    $product = $this->cachedProducts[$artikel];
                }else{
                    $product = $this->em->getRepository('Custom\Entity\Produkt')->findOneBy(array('artikel' => $artikel));
                    if(!$product) continue;
                    $this->cachedProducts[$artikel] = $product;
                }

                $vorlageFile = $this->em->getRepository('Custom\Entity\ProduktDigitalvorlagen')->findOneBy(array('produkt' => $product, 'datei' => $fileObject));
                if(!$vorlageFile) {
                    $vorlageFile = new ProduktDigitalvorlagen();
                }
                $vorlageFile->setProdukt($product);
                $vorlageFile->setDatei($fileObject);
                $vorlageFile->setSorting(0);

                $this->em->persist($vorlageFile);
                $this->em->flush();
            }

            if(!$doCopy){
                @rmdir($fileInfo->getPathName());
            }



        }
        $output->writeln("=> $count Digitalvorlagen importiert.");
        $this->em->flush();

    }

    protected function importDatenblaetter(OutputInterface $output, $folder, $doCopy = false){

        $filePath = $this->rootDir.self::IMPORT_FOLDER.'/datenblaetter/';
        $output->writeln('DATENBLÄTTER-IMPORT: '.$filePath);

        $folderObject = $this->em->getRepository('Areanet\PIM\Entity\Folder')->find($folder);
        if(!$folderObject){
            $output->writeln("<error>Ordner $folder nicht vorhanden!</error>");
            return;
        }

        $count = 0;
        foreach (new \DirectoryIterator($filePath) as $fileInfo) {
            if ($fileInfo->isDot() || !$fileInfo->isFile()) continue;


            $artikel  = $fileInfo->getBasename('.'.$fileInfo->getExtension());
            $fileName = $fileInfo->getFilename();

            $fileObject = $this->em->getRepository('Areanet\PIM\Entity\File')->findOneBy(array('name' => $fileName, 'folder' => $folderObject));
            if(!$fileObject){
                $fileObject = new File();
                $fileObject->setName($fileName);
                $fileObject->setFolder($folderObject);
            }

            $fileObject->setType(mime_content_type($fileInfo->getPathName()));
            $fileObject->setSize(filesize($fileInfo->getPathName()));
            $fileObject->setHash(md5_file($fileInfo->getPathname()));
            $this->em->persist($fileObject);
            $this->em->flush();

            $output->write('.');
            $count++;

            //Datei erstellen
            $this->processFile($fileInfo, $fileObject, $doCopy);

            //Datei verknüpfen
            $product = null;
            if(isset($this->cachedProducts[$artikel])){
                $product = $this->cachedProducts[$artikel];
            }else{
                $product = $this->em->getRepository('Custom\Entity\Produkt')->findOneBy(array('artikel' => $artikel));
                if(!$product) continue;
                $this->cachedProducts[$artikel] = $product;
            }

            $product->setTechnischesDatenblatt($fileObject);
            $this->em->persist($product);

        }
        $output->writeln("=> $count Datenblätter importiert.");
        $this->em->flush();
    }

    protected function processFile(\DirectoryIterator $fileInfo, File $fileObject, $doCopy = false){

        if($doCopy){
            copy($fileInfo->getPathName(), $this->backend->getPath($fileObject).'/'.$fileObject->getName());
        }else{
            rename($fileInfo->getPathName(), $this->backend->getPath($fileObject).'/'.$fileObject->getName());
        }

        $processor = Processing::getInstance($fileObject->getType());
        $processor->execute($this->backend, $fileObject);

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