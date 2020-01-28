<?php
namespace Areanet\Contentfly\Classes\File;


use Areanet\Contentfly\Entity\File;
use Areanet\Contentfly\Entity\ThumbnailSetting;

interface ProcessingInterface
{
    public function execute(BackendInterface $backend, File $fileObject, $fileSizeAlias = null, $variant = null);
    public function getMimeTypes();
    public function registerImageSize(ThumbnailSetting $size);
}