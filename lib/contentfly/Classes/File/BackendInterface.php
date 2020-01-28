<?php
namespace Areanet\Contentfly\Classes\File;

use Areanet\Contentfly\Entity\File;

interface BackendInterface
{
    public function getPath(File $file);
    public function getWebPath(File $file);
    public function getUri(File $file, $size = null, $variant = null);
    public function getWebUri(File $file, $size = null, $variant = null);
}