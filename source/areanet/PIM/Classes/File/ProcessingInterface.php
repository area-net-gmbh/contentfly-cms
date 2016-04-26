<?php
namespace Areanet\PIM\Classes\File;


use Areanet\PIM\Entity\File;

interface ProcessingInterface
{
    public function execute(BackendInterface $backend, File $fileObject);
}