<?php
namespace Areanet\Contentfly\Classes\File\Processing;

use Areanet\Contentfly\Classes\Config\Adapter;
use Areanet\Contentfly\Classes\File\ProcessingInterface;
use Areanet\Contentfly\Classes\File\BackendInterface;
use Areanet\Contentfly\Entity\File;
use Areanet\Contentfly\Entity\ThumbnailSetting;

class Image implements ProcessingInterface
{

    protected $thumbnailSettings = array();

    protected $mimeMapping = array(
        'image/jpeg' => 'jpeg',
        'image/jpg' => 'jpeg',
        'image/gif' => 'gif',
        'image/png' => 'png'
    );
    protected $qualityMapping = null;

    function __construct(){
        $this->qualityMapping = array(
            'image/jpeg'    => Adapter::getConfig()->FILE_IMAGE_QUALITY_JPEG,
            'image/jpg'     => Adapter::getConfig()->FILE_IMAGE_QUALITY_JPEG,
            'image/gif'     => null,
            'image/png'     => Adapter::getConfig()->FILE_IMAGE_QUALITY_PNG
        );
    }

    public function registerImageSize(ThumbnailSetting $thumbnailSetting)
    {
        $this->thumbnailSettings[$thumbnailSetting->getAlias()] = $thumbnailSetting;
    }

    public function getMimeTypes()
    {
        return array('image/jpeg', 'image/gif', 'image/png');
    }

    public function execute(BackendInterface $backend, File $fileObject, $fileSizeAlias = null, $variant = null)
    {
        if(!isset($this->mimeMapping[$fileObject->getType()])){
            return;
        }

        if($fileSizeAlias && !isset($this->thumbnailSettings[$fileSizeAlias])){
            throw new \Areanet\Contentfly\Classes\Exceptions\FileNotFoundException("FileSizeSetting not found");
        }

        $type           = $this->mimeMapping[$fileObject->getType()];
        $loadMethodName = "imagecreatefrom$type";
        $saveMethodName = "image$type";

        if(!function_exists($loadMethodName)){
            throw new \Exception("GDLib-Funktion $loadMethodName nicht auf dem Server verfügbar.");
        }

        $imgName = $backend->getPath($fileObject).'/'.$fileObject->getName();
        $img     = null;

        if($type == 'jpeg' && function_exists("exif_read_data")){

            try {
                $exif = exif_read_data($imgName);
            }catch(\Exception $e){

            }

            if($exif && !empty($exif['Orientation'])) {
                switch($exif['Orientation']) {
                    case 8:
                        $img0 = $loadMethodName($imgName);
                        $img  = imagerotate($img0,90,0);
                        imagedestroy($img0);
                        break;
                    case 3:
                        $img0 = $loadMethodName($imgName);
                        $img  = imagerotate($img0,180,0);
                        imagedestroy($img0);
                        break;
                    case 6:
                        $img0 = $loadMethodName($imgName);
                        $img  = imagerotate($img0,-90,0);
                        imagedestroy($img0);
                        break;
                    default:
                        $img = $loadMethodName($imgName);
                        break;
                }
            }else{
                $img = $loadMethodName($imgName);
            }
        }else{
            $img = $loadMethodName($imgName);
        }

        if($fileObject->getType() == 'image/png') {
            imagealphablending($img, false);
            imagesavealpha($img, true);
        }

        foreach($this->thumbnailSettings as $thumbnailSetting){


            if($fileSizeAlias && $fileSizeAlias != $thumbnailSetting->getAlias()){
                continue;
            }

            $imgThumbName   = $backend->getPath($fileObject).'/'.$thumbnailSetting->getAlias().'-'.$fileObject->getName();

            if($thumbnailSetting->getForceJpeg()){
                $imgThumbNameList = explode('.', $imgThumbName);
                $imgThumbNameList[(count($imgThumbNameList) -  1)] = 'jpg';
                $imgThumbName = implode('.', $imgThumbNameList);
            }

            if(!$variant || !file_exists($imgThumbName)) {
                $orig_width = imagesx($img);
                $orig_height = imagesy($img);

                $thumb = null;

                if ($thumbnailSetting->getWidth() && $thumbnailSetting->getHeight()) {
                    if ($thumbnailSetting->getDoCut()) {
                        $thumb = $this->resizeByCutting($fileObject, $img, $thumbnailSetting);
                    } else {
                        if ($orig_width > $orig_height) {
                            $thumb = $this->resizeByWidth($fileObject, $img, $thumbnailSetting);
                        } else {
                            $thumb = $this->resizeByHeight($fileObject, $img, $thumbnailSetting);
                        }
                    }
                } elseif ($thumbnailSetting->getWidth()) {
                    $thumb = $this->resizeByWidth($fileObject, $img, $thumbnailSetting);
                } elseif ($thumbnailSetting->getHeight()) {
                    $thumb = $this->resizeByHeight($fileObject, $img, $thumbnailSetting);
                } elseif ($thumbnailSetting->getPercent()) {
                    $thumb = $this->resizePercentual($fileObject, $img, $thumbnailSetting);
                }

                if ($thumb) {
                    $imgThumbName = $backend->getPath($fileObject) . '/' . $thumbnailSetting->getAlias() . '-' . $fileObject->getName();
                    if ($thumbnailSetting->getForceJpeg()) {
                        $imgThumbNameList = explode('.', $imgThumbName);
                        $imgThumbNameList[(count($imgThumbNameList) - 1)] = 'jpg';
                        $imgThumbName = implode('.', $imgThumbNameList);
                        imagejpeg($thumb, $imgThumbName, $this->qualityMapping['image/jpeg']);
                    } else {
                        $saveMethodName($thumb, $imgThumbName, $this->qualityMapping[$fileObject->getType()]);
                    }

                    imagedestroy($thumb);
                }
            }


            if($thumbnailSetting->getIsResponsive() &&  $variant != '1x' || $variant == '2x' ) {
                $quality        = $this->qualityMapping[$fileObject->getType()];
                $imgThumbName   = $backend->getPath($fileObject).'/'.$thumbnailSetting->getAlias().'-'.$fileObject->getName();

                if($thumbnailSetting->getForceJpeg()){
                    $imgThumbNameList = explode('.', $imgThumbName);
                    $imgThumbNameList[(count($imgThumbNameList) -  1)] = 'jpg';
                    $imgThumbName = implode('.', $imgThumbNameList);
                }

                if($thumbnailSetting->getForceJpeg()){
                    $loadMethodName = 'imagecreatefromjpeg';
                    $saveMethodName = 'imagejpeg';
                    $quality        = $this->qualityMapping['image/jpeg'];
                }

                $imgThumb   = $loadMethodName($imgThumbName);
                $thumb = $this->resizeByPercent($fileObject, $imgThumb, 2/3*100);

                $imgThumbNameList = explode("/", $imgThumbName);
                $imgThumbNameList[count($imgThumbNameList) - 1] = "2x@" . $imgThumbNameList[count($imgThumbNameList) - 1];
                $imgThumbName2x = implode('/', $imgThumbNameList);

                $saveMethodName($thumb, $imgThumbName2x, $quality);

                imagedestroy($thumb);

            }

            if($thumbnailSetting->getIsResponsive() &&  $variant != '2x' || $variant == '1x' ) {
                $quality        = $this->qualityMapping[$fileObject->getType()];
                $imgThumbName   = $backend->getPath($fileObject).'/'.$thumbnailSetting->getAlias().'-'.$fileObject->getName();


                if($thumbnailSetting->getForceJpeg()){
                    $imgThumbNameList = explode('.', $imgThumbName);
                    $imgThumbNameList[(count($imgThumbNameList) -  1)] = 'jpg';
                    $imgThumbName = implode('.', $imgThumbNameList);
                }

                if($thumbnailSetting->getForceJpeg()){
                    $loadMethodName = 'imagecreatefromjpeg';
                    $saveMethodName = 'imagejpeg';
                    $quality        = $this->qualityMapping['image/jpeg'];
                }

                $imgThumb   = $loadMethodName($imgThumbName);
                $thumb = $this->resizeByPercent($fileObject, $imgThumb, 1/3*100);

                $imgThumbNameList = explode("/", $imgThumbName);
                $imgThumbNameList[count($imgThumbNameList) - 1] = "1x@" . $imgThumbNameList[count($imgThumbNameList) - 1];
                $imgThumbName2x = implode('/', $imgThumbNameList);

                $saveMethodName($thumb, $imgThumbName2x, $quality);

                imagedestroy($thumb);

            }



        }
    }


    protected function resizeByWidth($fileObject, $img, ThumbnailSetting $thumbnailSetting){
        $orig_width  = imagesx($img);
        $orig_height = imagesy($img);

        $width       = $thumbnailSetting->getWidth() < $orig_width ? $thumbnailSetting->getWidth() : $orig_width;
        $height      = (($orig_height * $width) / $orig_width);

        $thumb = imagecreatetruecolor($width, $height);

        if($fileObject->getType() == 'image/png') {

            if($thumbnailSetting->getBackgroundColor()){
                $colorName          = str_replace('#', '', $thumbnailSetting->getBackgroundColor());
                list($r, $g, $b)    = array_map('hexdec',str_split($colorName,2));

                $bgcolor = imageColorAllocate($thumb, $r, $g, $b);
                imagefilledrectangle($thumb, 0, 0, $width, $height, $bgcolor);
            }else{
                imagealphablending($thumb, false);
                imagesavealpha($thumb, true);
            }

        }

        imagecopyresampled($thumb, $img,
            0, 0, 0, 0,
            $width, $height,
            $orig_width, $orig_height);

        return $thumb;
    }

    protected function resizeByHeight($fileObject, $img, ThumbnailSetting $thumbnailSetting){
        $orig_width  = imagesx($img);
        $orig_height = imagesy($img);

        $height     = $thumbnailSetting->getHeight() < $orig_height ? $thumbnailSetting->getHeight() : $orig_height;
        $width      = (($orig_width * $height) / $orig_height);

        $thumb = imagecreatetruecolor($width, $height);

        if($fileObject->getType() == 'image/png') {
            if($thumbnailSetting->getBackgroundColor()){
                $colorName          = str_replace('#', '', $thumbnailSetting->getBackgroundColor());
                list($r, $g, $b)    = array_map('hexdec',str_split($colorName,2));

                $bgcolor = imageColorAllocate($thumb, $r, $g, $b);
                imagefilledrectangle($thumb, 0, 0, $width, $height, $bgcolor);
            }else{
                imagealphablending($thumb, false);
                imagesavealpha($thumb, true);
            }
        }

        imagecopyresampled($thumb, $img,
            0, 0, 0, 0,
            $width, $height,
            $orig_width, $orig_height);

        return $thumb;
    }

    protected function resizeByCutting($fileObject, $img, ThumbnailSetting $thumbnailSetting){
        $orig_width  = imagesx($img);
        $orig_height = imagesy($img);

        $width  = $thumbnailSetting->getWidth();
        $height  = $thumbnailSetting->getHeight();

        if($width > $height){
            if($orig_height/$orig_width >=  $height/$width){
                $width_resized  = $width;
                $height_resized = round($width/$orig_width * $orig_height);
            }else{
                $height_resized  = $height;
                $width_resized   = round($height/$orig_height * $orig_width);
            }
        }else{
            if($orig_width/$orig_height >=  $width/$height){
                $width_resized  = round($height/$orig_height * $orig_width);
                $height_resized = $height;
            }else{
                $height_resized = round($height/$orig_width * $orig_height);
                $width_resized  = $height;
            }
        }

        $image_resized = imagecreatetruecolor($width_resized, $height_resized);
        if($fileObject->getType() == 'image/png') {
            if($thumbnailSetting->getBackgroundColor()){
                $colorName          = str_replace('#', '', $thumbnailSetting->getBackgroundColor());
                list($r, $g, $b)    = array_map('hexdec',str_split($colorName,2));

                $bgcolor = imageColorAllocate($image_resized, $r, $g, $b);
                imagefilledrectangle($image_resized, 0, 0, $width_resized, $height_resized, $bgcolor);
            }else{
                imagealphablending($image_resized, false);
                imagesavealpha($image_resized, true);
            }
        }

        imagecopyresampled($image_resized, $img, 0, 0, 0, 0, $width_resized, $height_resized, $orig_width, $orig_height);

        $width  = $width_resized <  $width ? $width_resized : $width;
        $height = $height_resized <  $height ? $height_resized : $height;

        $thumb = imagecreatetruecolor($width, $height);
        if($fileObject->getType() == 'image/png') {
            if($thumbnailSetting->getBackgroundColor()){
                $colorName          = str_replace('#', '', $thumbnailSetting->getBackgroundColor());
                list($r, $g, $b)    = array_map('hexdec',str_split($colorName,2));

                $bgcolor = imageColorAllocate($image_resized, $r, $g, $b);
                imagefilledrectangle($thumb, 0, 0, $width, $height, $bgcolor);
            }else{
                imagealphablending($image_resized, false);
                imagesavealpha($thumb, true);
            }
        }
        $src_y = 0;
        $src_x = round(($width_resized - $width)/2);

        imagecopyresampled($thumb, $image_resized, 0, 0, $src_x, $src_y, $width, $height, $width, $height);

        imagedestroy($image_resized);

        return $thumb;
    }

    protected function resizePercentual($fileObject, $img, ThumbnailSetting $thumbnailSetting){

        return $this->resizeByPercent($fileObject, $img, $thumbnailSetting->getPercent());
    }

    protected function resizeByPercent($fileObject, $img, $sizePerCent){
        $sizeFactor  = $sizePerCent/100;

        $orig_width  = imagesx($img);
        $orig_height = imagesy($img);

        $width       = $orig_width * $sizeFactor;
        $height      = $orig_height * $sizeFactor;

        $thumb = imagecreatetruecolor($width, $height);

        if($fileObject->getType() == 'image/png') {
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
        }

        imagecopyresampled($thumb, $img,
            0, 0, 0, 0,
            $width, $height,
            $orig_width, $orig_height);

        return $thumb;
    }
}