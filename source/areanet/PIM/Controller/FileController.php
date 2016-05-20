<?php
namespace Areanet\PIM\Controller;
use Areanet\PIM\Classes\Controller\BaseController;
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
     * @apiName File
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

    public function getAction(Request $request){
        
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