<?php
namespace Areanet\PIM\Controller;
use Areanet\PIM\Classes\Config;
use Areanet\PIM\Classes\Controller\BaseController;
use Areanet\PIM\Classes\Exceptions\Config\FileNotFoundException;
use Areanet\PIM\Classes\File\Backend;
use Areanet\PIM\Classes\File\Processing;
use Areanet\PIM\Classes\Permission;
use Areanet\PIM\Entity\File;
use Doctrine\Common\Annotations\AnnotationReader;
use Silex\Application;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;


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

        if(!Permission::isWritable($this->app['auth.user'], 'PIM\\File')){
            throw new AccessDeniedHttpException("Zugriff auf PIM\\File verweigert.");
        }

        $event = new \Areanet\PIM\Classes\Event();
        $event->setParam('request', $request);
        $event->setParam('user',    $this->app['auth.user']);
        $event->setParam('app',     $this->app);
        $this->app['dispatcher']->dispatch('pim.file.before.upload', $event);


        foreach($request->files as $key => $file){
            if($request->get("id")){

                $fileObject = $this->em->getRepository('Areanet\PIM\Entity\File')->find($request->get("id"));

                if (!$fileObject) {
                    throw new \Exception("Ungültige Bild-ID.");
                }

                $hash = md5_file($file->getRealPath());

                list($width, $height) = getimagesize($file->getRealPath());
                
                if($width){
                    $fileObject->setWidth($width);
                }
                if($height){
                    $fileObject->setHeight($height);
                }

                $fileObject->setType($file->getClientMimeType());
                $fileObject->setSize($file->getClientSize());

                $fileObject->setHash($hash);
                $this->em->persist($fileObject);
                $this->em->flush();

                $backend = Backend::getInstance();
                $file->move($backend->getPath($fileObject), $fileObject->getName());

                $processor = Processing::getInstance($file->getClientMimeType());
                $processor->execute($backend, $fileObject);
            }else {
                $hash = md5_file($file->getRealPath());

                $fileObject = null;
                if(Config\Adapter::getConfig()->FILE_HASH_MUST_UNIQUE){
                    $fileObject = $this->em->getRepository('Areanet\PIM\Entity\File')->findOneBy(array('hash' => $hash, 'isDeleted' => false));
                    die("test");
                }

                list($width, $height) = getimagesize($file->getRealPath());

                $extension      = $file->getClientOriginalExtension();
                $baseFilename   = str_replace($extension, "", $file->getClientOriginalName());
                $filename       = $this->sanitizeFileName($baseFilename) . "." . $extension;

                if (!$fileObject) {


                    $fileObject = new File();


                    $folder = null;
                    if($request->get("folder")){
                        $folder = $this->em->getRepository('Areanet\PIM\Entity\Folder')->find($request->get("folder"));
                        if($folder){
                            $fileObject->setFolder($folder);
                        }
                    }
                    
                    if($width){
                        $fileObject->setWidth($width);
                    }
                    if($height){
                        $fileObject->setHeight($height);
                    }

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
                } else {

                    if($width){
                        $fileObject->setWidth($width);
                    }
                    if($height){
                        $fileObject->setHeight($height);
                    }
                    
                    $fileObject->setIsDeleted(false);
                    $this->em->persist($fileObject);
                    $this->em->flush();
                }
            }
        }

        $event = new \Areanet\PIM\Classes\Event();
        $event->setParam('request', $request);
        $event->setParam('fileObject', $fileObject);
        $event->setParam('user',    $this->app['auth.user']);
        $event->setParam('app',     $this->app);
        $this->app['dispatcher']->dispatch('pim.file.after.upload', $event);

        $schema = $this->getSchema();

        return new JsonResponse(array('message' => 'File uploaded', 'data' => $fileObject->toValueObject($this->app['auth.user'], $schema, 'PIM\\File')));
    }

    /**
     * @apiVersion 1.3.0
     * @api {get} /file/get/:id/[:size]/[:variant]/[:alias] get
     * @apiName Get
     * @apiGroup File
     * @apiParam {string} id ID oder Dateiname
     * @apiParam {string} size=null Optional: Alias der gewünschten Thumbnail-Größe, muss im PIM-Backend oder als PIM-Standard ("pim_list", "pim_small") entsprechend definiert sein
     * @apiParam {string} variant=null Optional: 1x = 1/3 Größe von Originalbild / 2x = 2/3 Größe von Originalbild / 3x = Originalbild
     * @apiParam {string} alias=null Optional: Beliebiger Dateiname für SEO (Die Datei wird lediglich über die ID geladen)
     * @apiExample {curl} Abfrage anhand ID
     *     /file/get/12
     * @apiExample {curl} ID und Dateiname
     *     /file/get/12/sample.jpg
     * @apiExample {curl} Thumbnails anhand ID und Dateiname
     *     /file/get/12/small/sample.jpg
     * @apiExample {curl} Thumbnails anhand ID, Dateiname und Responsive
     *     /file/get/12/small/3x/sample.jpg (Original-Bild)
     * @apiExample {curl} Thumbnails anhand ID, Dateiname und Responsive
     *     /file/get/12/small/2x/sample.jpg (2/3 Größe von Original-Bild)
     * @apiExample {curl} Thumbnails anhand ID, Dateiname und Responsive
     *     /file/get/12/small/1x/sample.jpg (1/3 Größe von Original-Bild)
     *
     * @apiDescription Download/Darstellung von Dateien, der Aufruf kann über folgende Kombinationen erfolgen
     *
     * - /file/get/ID
     * - /file/get/ID/ALIAS
     * - /file/get/ID/SIZE/ALIAS
     * - /file/get/ID/SIZE/VARIANT/ALIAS
     *
     * Der Parameter ALIAS (z.B. beliebiger Dateiname) kann frei für SEO-Zwecke gesetzt werden und hat keinen Einfluss auf die Abfrage des entsprechenden Objektes. Für die Abfrage spielt lediglich die ID eine Rolle.
     */
    public function getAction($id, $alias = null, $size = null, $variant = null){

        $fileObject = null;
        $t1 = microtime();
        $fileObject = $this->em->getRepository('Areanet\PIM\Entity\File')->find($id);

        if(!$fileObject){
            throw new \Areanet\PIM\Classes\Exceptions\FileNotFoundException("FileObject not found");
        }

        $sizeObject = null;
        if($size){
            $sizeObject = $this->em->getRepository('Areanet\PIM\Entity\ThumbnailSetting')->findOneBy(array('alias' => $size));

            if(!$sizeObject){
                throw new \Areanet\PIM\Classes\Exceptions\FileNotFoundException("FileSize not found");
            }
        }

        $event = new \Areanet\PIM\Classes\Event();
        $event->setParam('id', $id);
        $event->setParam('fileObject', $fileObject);
        $event->setParam('sizeObject', $sizeObject);
        $event->setParam('app',     $this->app);
        $this->app['dispatcher']->dispatch('pim.file.before.get', $event);

        $mimeType   = $fileObject->getType();
        $backend    = Backend::getInstance();
        $fileUri    = $backend->getUri($fileObject, $sizeObject, $variant);

        $reExecute  =  false;

        $fileMTime = 0;
        $etagFile  = null;
        if(file_exists($fileUri)) {
            $fileMTime = filemtime($fileUri);
            $etagFile  = md5($fileUri . $fileMTime);
        }

        if($size){

            $sizeObject = $this->em->getRepository('Areanet\PIM\Entity\ThumbnailSetting')->findOneBy(array('alias' => $size));

            if($sizeObject->getForceJpeg()){
                $mimeType = 'image/jpeg';
            }

            $sizeTime = $sizeObject->getModified()->getTimestamp();
            if ($sizeTime > $fileMTime) {
                $reExecute = true;

            }
        }

        if(!file_exists($fileUri) || $reExecute){

            $processor = Processing::getInstance($fileObject->getType());
            if($processor instanceof Processing\Standard){
                throw new \Areanet\PIM\Classes\Exceptions\FileNotFoundException("FileSize for FileObject not found");
            }else{

                $processor->execute($backend, $fileObject, $size, $variant);
            }

            $fileMTime = filemtime($fileUri);
            $etagFile  = md5($fileUri . $fileMTime);
        }

        $fileName   = $backend->getUri($fileObject, $sizeObject, $variant);
        if(!file_exists($fileName)){

            throw new \Areanet\PIM\Classes\Exceptions\FileNotFoundException("File not found");
        }

        $client_etag =
            !empty($_SERVER['HTTP_IF_NONE_MATCH'])
                ?   trim($_SERVER['HTTP_IF_NONE_MATCH'])
                :   null
        ;
        $client_last_modified =
            !empty($_SERVER['HTTP_IF_MODIFIED_SINCE'])
                ?   trim($_SERVER['HTTP_IF_MODIFIED_SINCE'])
                :   null
        ;

        $server_last_modified   = gmdate('D, d M Y H:i:s', $fileMTime) . ' GMT';

        $matching_last_modified = $client_last_modified == $server_last_modified;
        $matching_etag          = $client_etag && strpos($client_etag, $etagFile) !== false;

        if (($client_last_modified && $client_etag) ?  $matching_last_modified && $matching_etag : $matching_last_modified || $matching_etag){
            return new \Symfony\Component\HttpFoundation\Response(null, 304, array('X-Status-Code' => 304, 'Cache-control' => 'max-age=86400, public'));
        }

        $event = new \Areanet\PIM\Classes\Event();
        $event->setParam('id', $id);
        $event->setParam('fileObject', $fileObject);
        $event->setParam('sizeObject', $sizeObject);
        $event->setParam('fileName',   $fileName);
        $event->setParam('app',     $this->app);
        $this->app['dispatcher']->dispatch('pim.file.before.send', $event);

        if(Config\Adapter::getConfig()->APP_ENABLE_XSENDFILE) {
            header('Pragma: public');
            header('Cache-Control: max-age=86400, public');
            header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));
            header("Content-length: " . filesize($fileName));
            header("Content-type: ".$mimeType);
            header("Last-Modified: ".$server_last_modified);
            header("ETag: ".$etagFile);
            header("X-Sendfile: ".$fileName);
            exit;
        }else{

            $stream = function () use ($fileName) {
                readfile($fileName);
            };

            return $this->app->stream($stream, 200, array(
                'Content-Type'   => $mimeType,
                'Content-length' => filesize($fileName),
                'Cache-Control' => 'max-age=86400, public',
                'Pragma' => 'public',
                'ETag' => $etagFile,
                'Last-Modified' => $server_last_modified,
                'Expires' => gmdate('D, d M Y H:i:s \G\M\T', time() + 86400)
            ));
        }

    }
    
    public function overwriteAction(Request $request)
    {
        $sourceId   = $request->get("sourceId");
        $destId     = $request->get("destId");

        if(!Permission::isWritable($this->app['auth.user'], 'PIM\\File')){
            throw new AccessDeniedHttpException("Zugriff auf PIM\\File verweigert.");
        }

        if(!$sourceId || !$destId){
            throw new \Areanet\PIM\Classes\Exceptions\FileNotFoundException("Source- or Dest-Id missing.");
        }
        

        $fileSource = $this->em->getRepository('Areanet\PIM\Entity\File')->find($sourceId);
        $fileDest   = $this->em->getRepository('Areanet\PIM\Entity\File')->find($destId);

        if(!$fileSource || !$fileDest){
            throw new \Areanet\PIM\Classes\Exceptions\FileNotFoundException("Source- or Dest-File not found.");
        }

        if($fileSource->getName() != $fileDest->getName()){
            throw new \Areanet\PIM\Classes\Exceptions\FileNotFoundException("Source- and Dest-Filename not matching.");
        }

        $backend    = Backend::getInstance();

        //Alte Daten löschen
        $pathDest   = $backend->getPath($fileDest);
        foreach (new \DirectoryIterator($pathDest) as $fileInfo) {
            if ($fileInfo->isDot() || !$fileInfo->isFile()) continue;
            unlink($fileInfo->getPathname());
        }

        //Neue Daten verschieben
        $pathSource  = $backend->getPath($fileSource);

        foreach (new \DirectoryIterator($pathSource) as $fileInfo) {
            if ($fileInfo->isDot() || !$fileInfo->isFile()) continue;
            $destName = $pathDest.'/'.$fileInfo->getBasename();
            rename($fileInfo->getPathname(), $destName);
        }

        @rmdir($pathSource);

        $fileSource->setIsDeleted(true);
        $this->em->persist($fileSource);

        list($width, $height) = getimagesize($pathDest.'/'.$fileInfo->getBasename());

        if($width){
            $fileDest->setWidth($width);
        }
        if($height){
            $fileDest->setHeight($height);
        }

        $now = new \DateTime();
        $fileDest->setModified($now);
        $this->em->persist($fileDest);
        $this->em->flush();

        return new JsonResponse(array('message' => 'File overwritten', 'sourceId' => $sourceId, 'destId' => $destId));
    }


    protected function sanitizeFileName($string, $force_lowercase = true, $anal = false) {
        $strip = array("~", "`", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "=", "+", "[", "{", "]",
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