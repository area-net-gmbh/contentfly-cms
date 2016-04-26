<?php
namespace Areanet\PIM\Classes\File\Processing;

use Areanet\PIM\Classes\File\ProcessingInterface;
use Areanet\PIM\Classes\File\BackendInterface;
use Areanet\PIM\Entity\File;

class Image implements ProcessingInterface
{

    static protected $imageSizes = array();
    protected $mimeMapping = array(
        'image/jpeg' => 'jpeg',
        'image/gif' => 'gif',
        'image/png' => 'png'
    );
    protected $qualityMapping = array(
        'image/jpeg' => 90,
        'image/gif' => null,
        'image/png' => 0
    );

    static public function registerImageSize($size)
    {
        self::$imageSizes[] = $size;
    }

    public function execute(BackendInterface $backend, File $fileObject)
    {
        if(!isset($this->mimeMapping[$fileObject->getType()])){
            return;
        }

        $type           = $this->mimeMapping[$fileObject->getType()];
        $loadMethodName = "imagecreatefrom$type";
        $saveMethodName = "image$type";

        $imgName  = $backend->getPath($fileObject).'/'.$fileObject->getName();
        $img      = $loadMethodName($imgName);

        if($fileObject->getType() == 'image/png') {
            imagealphablending($img, false);
            imagesavealpha($img, true);
        }

        foreach(self::$imageSizes as $size){
            $orig_width  = imagesx($img);
            $orig_height = imagesy($img);
            $height      = (($orig_height * $size) / $orig_width);

            $thumb = imagecreatetruecolor($size, $height);

            if($fileObject->getType() == 'image/png') {
                imagealphablending($thumb, false);
                imagesavealpha($thumb, true);
            }

            imagecopyresampled($thumb, $img,
                0, 0, 0, 0,
                $size, $height,
                $orig_width, $orig_height);

            $imgThumbName = $backend->getPath($fileObject).'/'.$size.'-'.$fileObject->getName();
            $saveMethodName($thumb, $imgThumbName, $this->qualityMapping[$fileObject->getType()]);

            $thumb = null;
        }
    }
}