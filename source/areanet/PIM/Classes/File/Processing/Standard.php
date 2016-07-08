<?php
namespace Areanet\PIM\Classes\File\Processing;

use Areanet\PIM\Classes\File\ProcessingInterface;
use Areanet\PIM\Classes\File\BackendInterface;
use Areanet\PIM\Entity\File;
use Areanet\PIM\Entity\ThumbnailSetting;

class Standard implements ProcessingInterface
{
    public function execute(BackendInterface $backend, File $fileObject, $fileSizeAlias = null, $variant = null)
    {

    }

    public function registerImageSize(ThumbnailSetting $size){

    }

    public function getMimeTypes()
    {
        return array();
    }
}