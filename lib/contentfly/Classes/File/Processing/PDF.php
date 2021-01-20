<?php
namespace Areanet\PIM\Classes\File\Processing;

use Areanet\PIM\Classes\Config\Adapter;
use Areanet\PIM\Classes\File\ProcessingInterface;
use Areanet\PIM\Classes\File\BackendInterface;
use Areanet\PIM\Entity\File;
use Areanet\PIM\Entity\ThumbnailSetting;

class PDF implements ProcessingInterface
{

 
    public function registerImageSize(ThumbnailSetting $thumbnailSetting)
    {
        $this->thumbnailSettings[$thumbnailSetting->getAlias()] = $thumbnailSetting;
    }

    public function getMimeTypes()
    {
        return array('application/pdf');
    }

    public function execute(BackendInterface $backend, File $fileObject, $fileSizeAlias = null, $variant = null)
    {

        if($fileSizeAlias && !isset($this->thumbnailSettings[$fileSizeAlias])){
            throw new \Areanet\PIM\Classes\Exceptions\FileNotFoundException("FileSizeSetting not found");
        }

        if (!extension_loaded('imagick')){
            throw new \Exception("Imagick-Modul nicht auf dem Server installiert.");
        }

        foreach($this->thumbnailSettings as $thumbnailSetting){

            if($fileSizeAlias && $fileSizeAlias != $thumbnailSetting->getAlias()){
                continue;
            }

            $imgName        = $backend->getPath($fileObject).'/'.$fileObject->getName();
            $imgThumbName   = $backend->getPath($fileObject).'/'.$thumbnailSetting->getAlias().'-'.$fileObject->getName();

            $imgThumbNameList = explode('.', $imgThumbName);
            $imgThumbNameList[(count($imgThumbNameList) -  1)] = 'jpg';
            $imgThumbName = implode('.', $imgThumbNameList);
            
            if(!$variant || !file_exists($imgThumbName)) {
                $im = new \Imagick();
                
                $im->readImage("{$imgName}[0]");
                $im->setImageFormat('jpeg');
                $im->setImageBackgroundColor('#ffffff');
                $im->setBackgroundColor('#ffffff');
                $im->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE );

                if ($thumbnailSetting->getWidth() && $thumbnailSetting->getHeight()) {
                    $im->scaleImage($thumbnailSetting->getWidth(), $thumbnailSetting->getHeight());    
                } elseif ($thumbnailSetting->getWidth()) {
                    $im->scaleImage((int)$thumbnailSetting->getWidth(), 0); 
                } elseif ($thumbnailSetting->getHeight()) {
                    $im->scaleImage(0, (int)$thumbnailSetting->getHeight()); 
                } elseif ($thumbnailSetting->getPercent()) {
                    continue;
                }

                $im->writeImage($imgThumbName);
            }
        }

    }

}