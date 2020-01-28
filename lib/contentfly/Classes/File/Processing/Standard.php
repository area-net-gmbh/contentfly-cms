<?php
namespace Areanet\Contentfly\Classes\File\Processing;

use Areanet\Contentfly\Classes\File\ProcessingInterface;
use Areanet\Contentfly\Classes\File\BackendInterface;
use Areanet\Contentfly\Entity\File;
use Areanet\Contentfly\Entity\ThumbnailSetting;

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