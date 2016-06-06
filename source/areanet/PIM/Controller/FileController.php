<?php
namespace Areanet\PIM\Controller;
use Areanet\PIM\Classes\Config;
use Areanet\PIM\Classes\Controller\BaseController;
use Areanet\PIM\Classes\Exceptions\Config\FileNotFoundException;
use Areanet\PIM\Classes\File\Backend;
use Areanet\PIM\Classes\File\Processing;
use Areanet\PIM\Entity\File;
use Doctrine\Common\Annotations\AnnotationReader;
use Silex\Application;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;


class FileController extends BaseController
{
    /**
     * @apiVersion 1.3.0
     * @api {post} /file/upload upload
     * @apiName Upload
     * @apiGroup File
     * @apiHeader {String} X-Token Acces-Token
     * @apiHeader {String} Content-Type=application/json
     *
     * @apiDescription Normaler POST-Upload von Dateien
     *
     */
    public function uploadAction(Request $request){

        $data = array();

        foreach($request->files as $key => $file){
            $hash = md5_file($file->getRealPath());
            $fileObject = $this->em->getRepository('Areanet\PIM\Entity\File')->findOneBy(array('hash' => $hash));

            $extension     = $file->getClientOriginalExtension();
            $baseFilename  = str_replace($extension, "", $file->getClientOriginalName());
            $filename      = $this->sanitizeFileName($baseFilename).".".$extension;

            if(!$fileObject) {
                $fileObject = new File();
                $fileObject->setName($filename);
                $fileObject->setType($file->getClientMimeType());
                $fileObject->setSize($file->getClientSize());
                $fileObject->setHash($hash);
                $this->em->persist($fileObject);
                $this->em->flush();

                $backend = Backend::getInstance();
                $file->move($backend->getPath($fileObject), $filename);

                $processor = Processing::getInstance($file->getClientMimeType());
                $processor->execute($backend, $fileObject);
            }
        }

        return new JsonResponse(array('message' => 'File uploaded', 'data' => $fileObject));
    }

    /**
     * @apiVersion 1.3.0
     * @api {get} /file/get/:id/[:size]/[:alias] get
     * @apiName Get
     * @apiGroup File
     * @apiParam {string} id ID oder Dateiname
     * @apiParam {string} size=null Optional: Alias der gewünschten Thumbnail-Größe, muss im PIM-Backend oder als PIM-Standard ("pim_list", "pim_small") entsprechend definiert sein
     * @apiParam {string} alias=null Optional: Beliebiger Dateiname für SEO (Die Datei wird lediglich über die ID geladen)
     * @apiExample {curl} Abfrage anhand ID
     *     /file/get/12
     * @apiExample {curl} ID und Dateiname
     *     /file/get/12/sample.jpg
     * @apiExample {curl} Thumbnails anhand ID und Dateiname
     *     /file/get/12/small/sample.jpg
     *
     * @apiDescription Download/Darstellung von Dateien, der Aufruf kann über folgende Kombinationen erfolgen
     *
     * - /file/get/ID
     * - /file/get/ID/ALIAS
     * - /file/get/ID/SIZE/ALIAS
     *
     * Der Parameter ALIAS (z.B. beliebiger Dateiname) kann frei für SEO-Zwecke gesetzt werden und hat keinen Einfluss auf die Abfrage des entsprechenden Objektes. Für die Abfrage spielt lediglich die ID eine Rolle.
     */
    public function getAction($id, $alias = null, $size = null){
        $fileObject = null;

        $fileObject = $this->em->getRepository('Areanet\PIM\Entity\File')->find($id);

        if(!$fileObject){
            throw new \Areanet\PIM\Classes\Exceptions\FileNotFoundException("FileObject not found");
        }

        $backend = Backend::getInstance();

        if(!file_exists($backend->getUri($fileObject, $size))){
            $processor = Processing::getInstance($fileObject->getType());
            if($processor instanceof Processing\Standard){
                throw new \Areanet\PIM\Classes\Exceptions\FileNotFoundException("FileSize for FileObject not found");
            }else{
                $processor->execute($backend, $fileObject, $size);
            }

        }

        $modules    = apache_get_modules();
        
        if($size){

            $sizeObject = $this->em->getRepository('Areanet\PIM\Entity\ThumbnailSetting')->findOneBy(array('alias' => $size));
            if(!$sizeObject){
                throw new \Areanet\PIM\Classes\Exceptions\FileNotFoundException("FileSize not found");
            }
        }

        $fileName   = $backend->getUri($fileObject, $sizeObject);

        if(in_array('mod_xsendfile', $modules) && Config\Adapter::getConfig()->APP_ENABLE_XSENDFILE) {

            header('Content-type: ' . $fileObject->getType());
            header("Content-length: " . $fileObject->getSize());
            header("X-Sendfile: ".$fileName);
            exit;
        }else{
            header('Content-type: ' . $fileObject->getType());
            header("Content-length: " . $fileObject->getSize());
            readfile($fileName);
        }
    }

    protected function sanitizeFileName($string, $force_lowercase = true, $anal = false) {
        $strip = array("~", "`", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "_", "=", "+", "[", "{", "]",
            "}", "\\", "|", ";", ":", "\"", "'", "&#8216;", "&#8217;", "&#8220;", "&#8221;", "&#8211;", "&#8212;",
            "â€”", "â€“", ",", "<", ".", ">", "/", "?");
        $clean = trim(str_replace($strip, "", strip_tags($string)));
        $clean = preg_replace('/\s+/', "-", $clean);
        $clean = ($anal) ? preg_replace("/[^a-zA-Z0-9]/", "", $clean) : $clean ;
        return ($force_lowercase) ?
            (function_exists('mb_strtolower')) ?
                mb_strtolower($clean, 'UTF-8') :
                strtolower($clean) :
            $clean;
    }
}