<?php
namespace Areanet\PIM\Classes\File\Backend;

use Areanet\PIM\Entity\File;
use Areanet\PIM\Classes\File\BackendInterface;

class FileSystem implements BackendInterface
{
    public function getPath(File $file)
    {
        if(!is_dir(ROOT_DIR.'/data/files/'.$file->getId())) mkdir(ROOT_DIR.'/data/files/'.$file->getId());
        return ROOT_DIR.'/data/files/'.$file->getId();
    }

    public function getWebPath(File $file)
    {
        if(!is_dir(ROOT_DIR.'/data/files/'.$file->getId())) mkdir(ROOT_DIR.'/data/files/'.$file->getId());
        return '/data/files/'.$file->getId();
    }

    public function getUri(File $file, $size = null)
    {
        if(!is_dir(ROOT_DIR.'/data/files/'.$file->getId())) mkdir(ROOT_DIR.'/data/files/'.$file->getId());
        $sizeUri = $size ? $size.'-' : '';
        return ROOT_DIR.'/data/files/'.$file->getId().'/'.$sizeUri.$file->getName();
    }

    public function getWebUri(File $file, $size = null)
    {
        if(!is_dir(ROOT_DIR.'/data/files/'.$file->getId())) mkdir(ROOT_DIR.'/data/files/'.$file->getId());
        $sizeUri = $size ? $size.'-' : '';
        return '/data/files/'.$file->getId().'/'.$sizeUri.$file->getName();
    }


}