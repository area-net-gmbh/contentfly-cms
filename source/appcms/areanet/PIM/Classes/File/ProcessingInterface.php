<?php
namespace Areanet\PIM\Classes\File;


use Areanet\PIM\Entity\File;
use Areanet\PIM\Entity\ThumbnailSetting;

interface ProcessingInterface
{
    public function execute(BackendInterface $backend, File $fileObject, $fileSizeAlias = null, $variant = null);
    public function getMimeTypes();
    public function registerImageSize(ThumbnailSetting $size);
}