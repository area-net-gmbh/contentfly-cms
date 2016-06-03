<?php
namespace Areanet\PIM\Classes\File;

use Areanet\PIM\Entity\File;

interface BackendInterface
{
    public function getPath(File $file);
    public function getWebPath(File $file);
    public function getUri(File $file, $size = null);
    public function getWebUri(File $file, $size = null);
}