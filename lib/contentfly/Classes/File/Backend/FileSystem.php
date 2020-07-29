<?php
namespace Areanet\PIM\Classes\File\Backend;

use Areanet\PIM\Entity\File;
use Areanet\PIM\Classes\File\BackendInterface;
use Areanet\PIM\Entity\ThumbnailSetting;

class FileSystem implements BackendInterface
{
    public function getPath(File $file)
    {
        return ROOT_DIR.'/data/files/'.$this->path($file);
    }

    private function path(File $file){
        $path = ($file->getPath() ? $file->getPath() : '').$file->getId();
 
        if(!is_dir(ROOT_DIR.'/data/files/'.$path)) mkdir(ROOT_DIR.'/data/files/'.$path, 0777, true);

        return $path;
    }

    public function getWebPath(File $file)
    {
        return '/data/files/'.$this->path($file);
    }

    public function getUri(File $file, $size = null, $variant = null)
    {
        $fileName = $file->getName();
        $sizeUri  = '';

        switch($variant){
            case '1x':
            case '2x':
                $variant = $variant.'@';
                break;
            default:
                $variant = '';
                break;
        }

        if($size){
            if($size instanceof ThumbnailSetting){

                if($size->getForceJpeg()) {
                    $imgThumbNameList = explode('.', $fileName);
                    $imgThumbNameList[(count($imgThumbNameList) - 1)] = 'jpg';
                    $fileName = implode('.', $imgThumbNameList);
                }
                $sizeUri = $size->getAlias().'-';
            }else{
                $sizeUri = $size.'-';
            }

        }

        return ROOT_DIR.'/data/files/'.$this->path($file).'/'.$variant.$sizeUri.$fileName;
    }

    public function getWebUri(File $file, $size = null, $variant = null)
    {
        $fileName = $file->getName();
        $sizeUri  = '';

        switch($variant){
            case '1x':
            case '2x':
                $variant = $variant.'@';
                break;
            default:
                $variant = '';
                break;
        }

        if($size){
            if($size instanceof ThumbnailSetting){
                if($size->getForceJpeg()) {
                    $imgThumbNameList = explode('.', $fileName);
                    $imgThumbNameList[(count($imgThumbNameList) - 1)] = 'jpg';
                    $fileName = implode('.', $imgThumbNameList);
                }
                $sizeUri = $size->getAlias().'/';
            }else{
                $sizeUri = $size.'/';
            }
        }

        return 'files/get/'.$this->path($file).'/'.$variant.$sizeUri.$fileName;
    }


}