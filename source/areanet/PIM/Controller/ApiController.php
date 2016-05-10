<?php
namespace Areanet\PIM\Controller;

use \Areanet\PIM\Classes\Config;
use Areanet\PIM\Classes\Controller\BaseController;
use Areanet\PIM\Classes\File\Backend\FileSystem;
use Areanet\PIM\Classes\Push;
use Areanet\PIM\Entity\Base;
use Areanet\PIM\Entity\File;
use Areanet\PIM\Entity\Log;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Silex\Application;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;


class ApiController extends BaseController
{

    protected $_MIMETYPES = array(
        'images' => array('image/jpeg', 'image/png', 'image/gif'),
        'pdf' => array('application/pdf')
    );

    public function singleAction(Request $request)
    {
        $data = array();

        $entityName = $request->get('entity');
        $id     = $request->get('id');

        $object = $this->em->getRepository('Custom\Entity\\'.ucfirst($entityName))->find($id);
        if(!$object){
            return new JsonResponse(array('message' => "Not found"), 404);
        }
        $data = json_encode($object);

        return new JsonResponse(array('message' => "ok", 'data' => $data));
    }

    /**
     * @apiVersion 1.3.0
     * @api {post} /api/list list
     * @apiName List
     * @apiGroup Objekte
     * @apiHeader {String} X-Token Acces-Token
     * @apiHeader {String} Content-Type=application/json
     *
     * @apiParam {String} entity Auszulesende Entity
     * @apiParam {Boolean} group Nur Rückgabe der Anzahl der Objekte
     * @apiParam {Object} order="{'id': 'DESC'}" Sortierung: <code>{'date': 'ASC/DESC',...}</code>
     * @apiParam {Object} where Bedingung, mehrere Felder werden mit AND verknüpft: <code>{'title': 'test', 'desc': 'foo',...}</code>
     * @apiParam {Integer} currentPage Aktuelle Seite für Pagination
     * @apiParamExample {json} Request-Beispiel:
     *     {
     *      "entity": "News",
     *      "currentPage": 1,
     *      "order": {
     *          "date": "DESC"
     *       },
     *      "where": {
     *          "title": "foo",
     *          "isHidden": false
     *     }
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "message": "allAction",
     *       "lastModified": "2016-02-21 12:20:00"
     *       "itemsPerPage": 15,
     *       "totalItems": 200,
     *       "data:" [
     *          {
     *              "id": 1,
     *              "isHidden": false,
     *              "isDeleted": false,
     *              "title": "Eine News"
     *          },
     *          {...},
     *          ...
     *      ]
     *   }
     * @apiError 404 Keine Einträge vorhanden
     */
    public function listAction(Request $request)
    {
        $data = array();

        $entityName = $request->get('entity');
        $doCount    = $request->get('count', false);
        $order      = $request->get('order', null);
        $where      = $request->get('where', null);
        $currentPage = $request->get('currentPage');

        if(substr($entityName, 0, 3) == 'PIM'){
            $entityNameToLoad = 'Areanet\PIM\Entity\\'.substr($entityName, 4);
        }else{
            $entityName = ucfirst($request->get('entity'));
            $entityNameToLoad = 'Custom\Entity\\'.ucfirst($entityName);
        }

        $schema     = $this->getSchema();


        /**
         * Get all entity objects for pagination
         */
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
            ->select($entityName)
            ->from($entityNameToLoad, $entityName)
            ->where("$entityName.isDeleted = false")
        ;

        if($where){
            $placeholdCounter = 0;
            $currentPage = 1;
            
            foreach($where as $field => $value){

                if(!isset($schema[$entityName]['properties'][$field])){
                    continue;
                }

                switch($schema[$entityName]['properties'][$field]['type']){
                    case 'boolean':
                        if(strtolower($value) == 'false'){
                            $value = 0;
                        }elseif(strtolower($value) == 'true'){
                            $value = 1;
                        }else{
                            $value = boolval($value);
                        }
                        break;
                    case 'integer':
                        $value = intval($value);
                        break;
                }

                $queryBuilder->andWhere("$entityName.$field = :$field");
                $queryBuilder->setParameter($field, $value);
                $placeholdCounter++;
            }

            if(isset($where['fulltext'])){
                $orX = $queryBuilder->expr()->orX();
                $fulltextTypes = array('string', 'text', 'textarea', 'rte');
                foreach($schema[$entityName]['properties'] as $field => $fieldOptions){

                    if(in_array($fieldOptions['type'], $fulltextTypes)){
                        $orX->add("$entityName.$field LIKE :FT_$field");
                        $queryBuilder->setParameter("FT_$field", '%' . $where['fulltext'] . '%');
                    }
                }

                $queryBuilder->andWhere($orX);
            }

            if(isset($where['mimetypes'])){

                if($where['mimetypes'] == 'other'){
                    $types = array();
                    foreach($this->_MIMETYPES as $mimetypes){
                        $types = array_merge($types, $mimetypes);
                    }
                    $queryBuilder->andWhere($queryBuilder->expr()->notIn("$entityName.type", $types));
                }elseif(isset($this->_MIMETYPES[$where['mimetypes']])){

                    $queryBuilder->andWhere($queryBuilder->expr()->in("$entityName.type", $this->_MIMETYPES[$where['mimetypes']]));

                    //$queryBuilder->andWhere($orX);
                }

                //die("$entityName.type");

            }
        }

        $query   = $queryBuilder->getQuery();
        //die($query->getSQL());
        $totalObjects = $query->getResult();

        /*
        $qb = $this->em->createQueryBuilder();
        $qb
            ->select($entityName)
            ->from($entityNameToLoad, $entityName)
            ->where("$entityName.isDeleted = false")
        ;
        */

        if($currentPage) {
            $queryBuilder
                ->setFirstResult(Config\Adapter::getConfig()->FRONTEND_ITEMS_PER_PAGE * ($currentPage - 1))
                ->setMaxResults(Config\Adapter::getConfig()->FRONTEND_ITEMS_PER_PAGE)
            ;
        }

        if($order != null){
            foreach($order as $orderBy => $orderSort){
                $queryBuilder->addOrderBy($entityName.'.'.$orderBy, $orderSort);
            }
        }else{
            $queryBuilder->orderBy($entityName.'.id', 'DESC');
        }

        $query   = $queryBuilder->getQuery();
        $objects = $query->getResult();


        if($doCount){
            return new JsonResponse(array('message' => "ok", 'data' => count($objects)));
        }

        if(!$objects){
            return new JsonResponse(array('message' => "Not found"), 404);
        }

        $array = array();
        foreach($objects as $object){
            $objectData = $object->toValueObject();

            //Todo: n:m-Objekte nur optional mit Parameter laden

            foreach($schema[$entityName]['properties'] as $key => $config){
                if(isset($config['type']) && $config['type'] == 'multifile'){
                    $getterName = 'get'.ucfirst($key);
                    $multiFiles = $object->$getterName();
                    $multiData  = array();
                    foreach($multiFiles as $multiFile){
                        $multiData[] = $multiFile->toValueObject();
                    }
                    $objectData->$key = $multiData;
                }
            }
            
            $array[] = $objectData;

        }

        $data = json_encode($array);

        if($currentPage) {
            return new JsonResponse(array('message' => "ok", 'data' => $data, 'itemsPerPage' => Config\Adapter::getConfig()->FRONTEND_ITEMS_PER_PAGE, 'totalItems' => count($totalObjects)));
        } else {
            return new JsonResponse(array('message' => "ok", 'data' => $data));
        }
    }


    /**
     * @apiVersion 1.3.0
     * @api {post} /api/delete delete
     * @apiName Delete
     * @apiGroup Objekte
     * @apiHeader {String} X-Token Acces-Token
     * @apiHeader {String} Content-Type=application/json
     *
     * @apiParam {String} entity Zu löschende Entity
     * @apiParam {Integer} id Zu löschende Objekt-ID
     * @apiParamExample {json} Request-Beispiel:
     *     {
     *      "entity": "News",
     *      "id": 12
     *      }
     */
    public function deleteAction(Request $request, Application $app)
    {
        $entityName = ucfirst($request->get('entity'));
        $id         = $request->get('id');

        $entityPath = 'Custom\Entity\\'.$entityName;
        if(substr($entityName, 0, 3) == "PIM"){
            $entityPath = 'Areanet\PIM\Entity\\'.substr($entityName, 4);
        }

        $object = $this->em->getRepository($entityPath)->find($id);
        if(!$object){
            return new JsonResponse(array('message' => "Das Objekt wurde nicht gefunden."), 404);
        }

        if($entityPath == 'Areanet\PIM\Entity\User'){

            if($object->getAlias() == 'admin'){
                return new JsonResponse(array('message' => "Der Hauptadministrator kann nicht gelöscht werden."), 400);
            }

            $alias = $object->getAlias();
            $object->setAlias("$alias (gelöscht)");
        }

        if($entityPath == 'Areanet\PIM\Entity\File') {
            $schema = $this->getSchema();

            foreach($schema as $entity => $entityConfig){

                $entityUpdatePath = 'Custom\Entity\\'.$entity;
                if(substr($entity, 0, 3) == "PIM"){
                    $entityUpdatePath = 'Areanet\PIM\Entity\\'.substr($entity, 4);
                }

                foreach($entityConfig['properties'] as $property => $propertyConfig){
                    if($propertyConfig['type'] == 'file'){
                        //Todo: TESTEN!
                        $query = $this->em->createQuery("UPDATE $entityUpdatePath e SET e.$property = NULL, e.modified = CURRENT_TIMESTAMP() WHERE e.$property = ?1");
                        $query->setParameter(1, $id);
                        $query->execute();
                    }elseif($propertyConfig['type'] == 'multifile'){
                        $query = $this->em->createQuery('UPDATE '.$entityUpdatePath.' e SET e.modified = CURRENT_TIMESTAMP() WHERE ?1 MEMBER OF e.'.$property);
                        $query->setParameter(1, $id);
                        $query->execute();
                        $foreignTable = $propertyConfig['foreign'];
                        $statement = "DELETE FROM $foreignTable WHERE file_id = ?";
                        $this->em->getConnection()->executeUpdate($statement, array($id));
                    }
                }
            }

        }

        $object->setIsDeleted(true);

        $this->em->persist($object);
        $this->em->flush();

        /**
         * Log delete actions
         */
        $log = new Log();

        $log->setModelId($object->getId());
        $log->setModelName(ucfirst($request->get('entity')));
        $log->setUser($app['auth.user']);
        $log->setMode('Gelöscht');

        $this->em->persist($log);
        $this->em->flush();

        return new JsonResponse(array('message' => 'Object deleted'));
    }


    /**
     * @apiVersion 1.3.0
     * @api {post} /api/insert insert
     * @apiName Insert
     * @apiGroup Objekte
     * @apiHeader {String} X-Token Acces-Token
     * @apiHeader {String} Content-Type=application/json
     *
     * @apiDescription Datumsfelder sollten im ISO 8601-Format übertragen werden.
     *
     * @apiParam {String} entity Einzutragende Entity
     * @apiParam {Object} data Daten des Objekts, abhhängig von der Entity
     * @apiParamExample {json} Request-Beispiel:
     *     {
     *      "entity": "News",
     *      "data": {
     *          "title": "Eine neue News",
     *          "subtitle: "Untertitel der neuen News",
     *          "date": "2016-02-18 15:30:00"
     *      }
     * @apiError 500 Ein Objekt mit einem gleichen UNIQUE-INDEX ist bereits vorhanden
     * @apiError 501 Unbekannter Serverfehler
     */
    public function insertAction(Request $request, Application $app)
    {
        $entityName          = ucfirst($request->get('entity'));
        $data                = $request->get('data');
        $schema              = $this->getSchema();

        $entityPath = 'Custom\Entity\\'.ucfirst($entityName);
        if(substr($entityName, 0, 3) == "PIM"){
            $entityPath = 'Areanet\PIM\Entity\\'.substr($entityName, 4);
        }
        $object     = new $entityPath();
        //Todo: Validierung!

        foreach($data as $property => $value){
            $setter = 'set'.ucfirst($property);

            //Todo: dynamisch alle Joins
            switch($schema[ucfirst($request->get('entity'))]['properties'][$property]['type']){
                case 'file':
                    if(empty($value)){
                        $object->$setter(null);
                        continue;
                    }
                    $objectToJoin = $this->em->getRepository('Areanet\PIM\Entity\File')->find($value);

                    if(!$objectToJoin) {
                        continue;
                    }
                    $object->$setter($objectToJoin);

                    break;
                case 'multifile':
                    $collection = new ArrayCollection();
                    if(!is_array($value) || !count($value)){
                        $object->$setter($collection);
                        continue;
                    }

                    foreach($value as $id){
                        $objectToJoin = $this->em->getRepository('Areanet\PIM\Entity\File')->find($id);
                        $collection->add($objectToJoin);
                    }

                    $object->$setter($collection);

                    break;
                case 'join':
                    $entity = $schema[ucfirst($request->get('entity'))]['properties'][$property]['accept'];
                    $objectToJoin = $this->em->getRepository($entity)->find($value);
                    $object->$setter($objectToJoin);
                    break;
                default:
                    $object->$setter($value);
                    break;
            }

        }

        $object->setUser($app['auth.user']);

        try {

            $this->em->persist($object);
            $this->em->flush();


            $isPush = $schema[ucfirst($request->get('entity'))]['settings']['isPush'];
            if($isPush){
                $pushTitle  = $schema[ucfirst($request->get('entity'))]['settings']['pushTitle'];
                $pushText   = $schema[ucfirst($request->get('entity'))]['settings']['pushText'];
                $pushObject = $schema[ucfirst($request->get('entity'))]['settings']['pushObject'];

                $push = new Push($this->em, $object);
                $push->send($pushTitle, $pushText, $pushObject);
            }

            /**
             * Log insert actions
             */
            $log = new Log();

            $log->setModelId($object->getId());
            $log->setModelName(ucfirst($request->get('entity')));
            $log->setUser($app['auth.user']);
            $log->setMode('Erstellt');

            $this->em->persist($log);
            $this->em->flush();
        }catch(UniqueConstraintViolationException $e){
            if($entityPath == 'Areanet\PIM\Entity\User'){
                return new JsonResponse(array('message' => "Ein Benutzer mit diesem Benutzername ist bereits vorhanden."), 500);
            }
            $uniqueObjectLoaded = false;

            foreach($schema[ucfirst($request->get('entity'))]['properties'] as $property => $propertySettings){

                if($propertySettings['unique']){
                    $object = $this->em->getRepository($entityPath)->findOneBy(array($property => $data[$property]));
                    if(!$object){
                        return new JsonResponse(array('message' => "Unbekannter Fehler"), 501);
                    }
                    $uniqueObjectLoaded = true;
                    break;
                }
            }

            if(!$uniqueObjectLoaded) return new JsonResponse(array('message' => "Unbekannter Fehler"), 500);
        }


        return new JsonResponse(array('message' => 'Object inserted', 'id' => $object->getId(), "data" => json_encode($object)));
    }

    /**
     * @apiVersion 1.3.0
     * @api {post} /api/update update
     * @apiName Update
     * @apiGroup Objekte
     * @apiHeader {String} X-Token Acces-Token
     * @apiHeader {String} Content-Type=application/json
     *
     * @apiDescription Datumsfelder sollten im ISO 8601-Format übertragen werden.
     *
     * @apiParam {String} entity zu aktualisierende Entity
     * @apiParam {Integer} id Zu löschende Objekt-ID
     * @apiParam {Object} data Daten des Objekts, abhhängig von der Entity
     * @apiParamExample {json} Request-Beispiel:
     *     {
     *      "entity": "News",
     *      "id": 12,
     *      "data": {
     *          "title": "Eine geänderte News",
     *          "subtitle: "Untertitel der geänderten News",
     *          "date": "2016-02-18 15:30:00"
     *      }
     * @apiError 400 zu aktualisierendes Objekt ist nicht vorhanden
     * @apiError 500 Ein Objekt mit einem gleichen UNIQUE-INDEX ist bereits vorhanden
     */
    public function updateAction(Request $request, Application $app)
    {
        $entityName          = $request->get('entity');
        $id                  = $request->get('id');
        $data                = $request->get('data');
        $disableModifiedTime = $request->get('disableModifiedTime');
        $schema              = $this->getSchema();

        $entityPath = 'Custom\Entity\\'.ucfirst($entityName);
        if(substr($entityName, 0, 3) == "PIM"){
            $entityPath = 'Areanet\PIM\Entity\\'.substr($entityName, 4);
        }

        $object = $this->em->getRepository($entityPath)->find($id);
        if(!$object){
            return new JsonResponse(array('message' => "Not found"), 404);
        }

        //Todo: Validierung!

        foreach($data as $property => $value){
            $setter = 'set'.ucfirst($property);
            $getter = 'get'.ucfirst($property);

            //Todo: dynamisch alle Joins?
            switch($schema[ucfirst($request->get('entity'))]['properties'][$property]['type']){
                case 'file':
                    if(empty($value)){
                        $object->$setter(null);
                        continue;
                    }
                    $objectToJoin = $this->em->getRepository('Areanet\PIM\Entity\File')->find($value);

                    if(!$objectToJoin) {
                        continue;
                    }
                    $object->$setter($objectToJoin);

                    break;
                case 'multifile':
                    $collection = new ArrayCollection();
                    if(!is_array($value) || !count($value)){
                        $object->$setter($collection);
                        continue;
                    }

                    foreach($value as $id){
                        $objectToJoin = $this->em->getRepository('Areanet\PIM\Entity\File')->find($id);
                        $collection->add($objectToJoin);
                    }

                    $object->$setter($collection);

                    break;
                case 'join':
                    $entity = $schema[ucfirst($request->get('entity'))]['properties'][$property]['accept'];
                    $objectToJoin = $this->em->getRepository($entity)->find($value);
                    $object->$setter($objectToJoin);
                    break;
                default:
                    if(strtoupper($value) == 'INC'){
                        $oldValue = $object->$getter();
                        $oldValue++;
                        $object->$setter($oldValue);
                    }elseif(strtoupper($value) == 'DEC'){
                        $oldValue = $object->$getter();
                        $oldValue--;
                        $object->$setter($oldValue);
                    }else{
                        $object->$setter($value);
                    }

                    break;
            }
        }

        $object->setUser($app['auth.user']);

        try{
            if($disableModifiedTime){
                $object->doDisableModifiedTime(true);
            }

            $this->em->persist($object);
            $this->em->flush();



        }catch(UniqueConstraintViolationException $e){
            if($entityPath == 'Areanet\PIM\Entity\User'){
                return new JsonResponse(array('message' => "Ein Benutzer mit diesem Benutzername ist bereits vorhanden."), 500);
            }else{
                return new JsonResponse(array('message' => "Ein gleicher Eintrag ist bereits vorhanden."), 500);
            }
        }

        /**
         * Log update actions
         */
        $log = new Log();

        $log->setModelId($object->getId());
        $log->setModelName(ucfirst($request->get('entity')));
        $log->setUser($app['auth.user']);
        $log->setMode('Geändert');

        $this->em->persist($log);
        $this->em->flush();

        return new JsonResponse(array('message' => 'Object updated', 'id' => $object->getId()));

    }

    /**
     * @apiVersion 1.3.0
     * @api {post} /api/all all
     * @apiName All
     * @apiGroup Objekte
     * @apiHeader {String} X-Token Acces-Token
     * @apiHeader {String} Content-Type=application/json
     *
     * @apiDescription Gibt alle Objekte aller Entitys zurück
     *
     * @apiParam {String} [lastModified="yyyymmdd hh:mm:ii"] Es werden nur die Objekte zurückgegeben, die seit lastModified geändert wurden.
     * @apiParamExample {json} Request-Beispiel:
     *     {
     *      "lastModified": "2016-02-20 15:30:22"
     *      }
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "message": "allAction",
     *       "lastModified": "2016-02-21 12:20:00"
     *       "data:" {
     *          "News": [
     *              {
     *                  "id": 1,
     *                  "isHidden": false,
     *                  "isDeleted": false,
     *                  "title": "Eine News"
     *              },
     *              ...
     *          },
     *          "EntityXYZ": [
     *              {...},
     *              {...},
     *              ...
     *          ]
     *      }
     *     }
     */
    public function allAction(Request $request)
    {
        $timestamp    = $request->get('lastModified');
        $filedata     = $request->get('filedata');
        $check        = $request->get('check', false);

        $lastModified = null;
        if(!empty($timestamp)) {
            try {
                $lastModified = new \Datetime($timestamp);
            } catch (\Exception $e) {

            }
        }

        $entities   = array('Areanet\PIM\Entity\File');
        $schema     = $this->getSchema();

        $entityFolder = __DIR__.'/../../../custom/Entity/';
        foreach (new \DirectoryIterator($entityFolder) as $fileInfo) {
            if($fileInfo->isDot()) continue;
            $entities[] = 'Custom\Entity\\'.ucfirst($fileInfo->getBasename('.php'));
        }

        $all = array();

        foreach($entities as $entityName){
            $entityShortcut = substr($entityName, strrpos($entityName, '\\') + 1);
            $qb = $this->em->createQueryBuilder();
            $qb
                ->select($entityShortcut)
                ->from($entityName, $entityShortcut);
            ;

            if($lastModified){
                //$qb->where($qb->expr()->lte('modified', $lastModified));
                $qb->where($entityShortcut.'.modified >= :lastModified')->setParameter('lastModified', $lastModified);
            }

            $query = $qb->getQuery();
            $objects = $query->getResult();

            if(!$objects){
                continue;
            }

            $array = array();
            foreach($objects as $object){

                $objectData = $object->toValueObject();

                if(isset($schema[$entityShortcut])) {
                    foreach ($schema[$entityShortcut]['properties'] as $key => $config) {

                        if ($config['type'] == 'multifile') {
                            $getterName = 'get' . ucfirst($key);
                            $multiFiles = $object->$getterName();
                            $multiData = array();
                            foreach ($multiFiles as $multiFile) {
                                $multiData[] = $multiFile->toValueObject();
                            }
                            $objectData->$key = $multiData;
                        }
                    }
                }
                

                if($object instanceof File && !$object->getIsDeleted() && $filedata != null){
                    $backendFS = new FileSystem();
                    foreach($filedata as $size){
                        $sizePrefix = $size == 'org' ? '' : $size.'-';
                        $path      = $backendFS->getPath($object);

                        $filePath = $path.'/'.$sizePrefix.$object->getName();
                        if(file_exists($filePath)){
                            if(!isset($objectData->filedata)) $objectData->filedata = new \stdClass();
                            $data   = file_get_contents($filePath);
                            $base64 = base64_encode($data);

                            $src = 'data: '.$object->getType().';base64,'.$base64;
                            //die("test:".$src);
                            $objectData->filedata->$size = $base64;
                            //die("test: ".$objectData->filedata->$size);
                        }


                    }
                }

                $array[] = $objectData;

            }

            $all[$entityShortcut] = $array;
        }

        $currentDate = new \Datetime();
        $jsonResponse = new JsonResponse(array('message' => 'allAction',  'lastModified' => $currentDate->format('Y-m-d H:i:s'),  'data' => $all), count($all) ? 200 : 204);

        if($filedata != null) file_put_contents(ROOT_DIR.'/data/currentData.json', json_encode($all));

        return $jsonResponse;
    }

    /**
     * @apiVersion 1.3.0
     * @api {get} /api/schema schema
     * @apiName Schema
     * @apiGroup Objekte
     * @apiHeader {String} X-Token Acces-Token
     * @apiHeader {String} Content-Type=application/json
     *
     * @apiDescription Gibt das Schema aller Entities zurück
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "devmode": false,
     *       "version": "1.3.0"
     *       "data:" {
     *         ...
     *       }
     *     }
     */
    public function schemaAction()
    {

        $frontend = array(
            'customLogo' => Config\Adapter::getConfig()->FRONTEND_CUSTOM_LOGO
        );

        return new JsonResponse(array('message' => 'schemaAction', 'frontend' => $frontend, 'devmode' => Config\Adapter::getConfig()->APP_DEBUG, 'version' => APP_VERSION, 'data' => $this->getSchema()));
    }

    protected function getSchema()
    {
        $entities = array();

        $entityFolder = __DIR__.'/../../../custom/Entity/';
        foreach (new \DirectoryIterator($entityFolder) as $fileInfo) {
            if($fileInfo->isDot()) continue;
            $entities[] = $fileInfo->getBasename('.php');
        }
        $entities[] = "PIM\\File";
        $entities[] = "PIM\\User";
        $entities[] = "PIM\\Log";
        $entities[] = "PIM\\PushToken";

        $data     = array();

        foreach($entities as $entity){
            if(substr($entity,0,3) == "PIM"){
                $className = 'Areanet\PIM\Entity\\'.substr($entity, 4);
            }else{
                $className = "\Custom\Entity\\$entity";
            }

            $object    = new $className();
            $reflect   = new \ReflectionClass($object);
            $props     = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED);

            $annotationReader = new AnnotationReader();

            $settings = array(
                'label' => $entity,
                'readonly' => false,
                'isPush' => false,
                'pushTitle' => '',
                'pushText' => '',
                'pushObject' => '',
                'sortBy' => 'id',
                'sortOrder' => 'DESC',
                'tabs' => array('default' => 'Allgemein')
            );

            $classAnnotations = $annotationReader->getClassAnnotations($reflect);


            foreach($classAnnotations as $classAnnotation) {

                if ($classAnnotation instanceof \Areanet\PIM\Classes\Annotations\Config) {

                    $settings['label']       = $classAnnotation->label ? $classAnnotation->label : $entity;
                    $settings['readonly']    = $classAnnotation->readonly ? $classAnnotation->readonly : false;
                    $settings['isPush']      = ($classAnnotation->pushText && $classAnnotation->pushTitle);
                    $settings['pushTitle']   = $classAnnotation->pushTitle ? $classAnnotation->pushTitle : null;
                    $settings['pushText']    = $classAnnotation->pushText ? $classAnnotation->pushText : null;
                    $settings['pushObject']  = $classAnnotation->pushObject ? $classAnnotation->pushObject : null;
                    $settings['sortBy']      = $classAnnotation->sortBy ? $classAnnotation->sortBy : $settings['sortBy'];
                    $settings['sortOrder']   = $classAnnotation->sortOrder ? $classAnnotation->sortOrder : $settings['sortOrder'];

                    if($classAnnotation->tabs){
                        $tabs = json_decode(str_replace("'", '"', $classAnnotation->tabs));
                        foreach($tabs as $key=>$value){
                            $settings['tabs'][$key] = $value;
                        }
                    }
                }
            }

            $list       = array();
            $properties = array();
            foreach ($props as $prop) {


                $annotations = array(
                    'showInList' => false,
                    'readonly' => false,
                    'hide' => false,
                    'type' => "",
                    'label' => $prop->getName(),
                    'accept' => '*',
                    'rteOptions' => '',
                    'filter' => '',
                    'options' => array(),
                    'foreign' => null,
                    'tab' => null
                );

                $reflectionProperty = new \ReflectionProperty($className, $prop->getName());
                $propertyAnnotations = $annotationReader->getPropertyAnnotations($reflectionProperty);

                foreach($propertyAnnotations as $propertyAnnotation){

                    if($propertyAnnotation instanceof \Areanet\PIM\Classes\Annotations\Config) {
                        $annotations['showInList']  = $propertyAnnotation->showInList;
                        $annotations['readonly']    = $propertyAnnotation->readonly;
                        $annotations['hide']        = $propertyAnnotation->hide;
                        $annotations['label']       = $propertyAnnotation->label ? $propertyAnnotation->label : $annotations['label'];
                        $annotations['lines']       = $propertyAnnotation->lines;
                        if(!empty($propertyAnnotation->accept) && $propertyAnnotation->accept != '*'){
                            $annotations['accept']     = $propertyAnnotation->accept;
                        }
                        //$annotations['accept']      = !empty($propertyAnnotation->accept) ? $propertyAnnotation->accept : $annotations['accept'];
                        $annotations['rteToolbar']  = $propertyAnnotation->rteToolbar ? $propertyAnnotation->rteToolbar : '';
                        $annotations['filter']      = $propertyAnnotation->filter ? $propertyAnnotation->filter : '';
                        $annotations['tab']         = $propertyAnnotation->tab ? $propertyAnnotation->tab : $annotations['tab'];

                        if($propertyAnnotation->type){
                            $annotations['type'] = $propertyAnnotation->type;
                        }

                        if($annotations['type'] == 'select' && $propertyAnnotation->options){
                            $options = explode(',', $propertyAnnotation->options);

                            $optionsData = array();
                            $count = 0;
                            foreach($options as $option){
                                $optionSplit = explode('=', $option);
                                if(count($optionSplit) == 1){
                                    $optionsData[] = array(
                                        "id" => trim($optionSplit[0]),
                                        "name" => trim($optionSplit[0])
                                    );
                                    $count++;
                                }else{
                                    $optionsData[] = array(
                                        "id" => trim($optionSplit[0]),
                                        "name" => trim($optionSplit[1])
                                    );
                                }
                            }

                            $annotations['options'] = $optionsData;
                        }

                        if($annotations['showInList']){
                            $list[$annotations['showInList']] = $prop->getName();
                        }
                    }

                    if($propertyAnnotation instanceof \Doctrine\ORM\Mapping\Column){
                        $annotations['type']     = !empty($annotations['type']) ? $annotations['type'] : $propertyAnnotation->type;

                        $annotations['length'] = $propertyAnnotation->length ? $propertyAnnotation->length : 524288;
                        $annotations['unique'] = $propertyAnnotation->unique ? $propertyAnnotation->unique : false;

                        if($annotations['type'] == "text"){
                            $annotations['type'] = "textarea";
                        }
                        $annotations['nullable'] = $propertyAnnotation->nullable;
                    }

                    if($propertyAnnotation instanceof \Doctrine\ORM\Mapping\Id){
                        $annotations['readonly'] = true;
                    }

                    if($propertyAnnotation instanceof \Doctrine\ORM\Mapping\ManyToOne){

                        switch($propertyAnnotation->targetEntity){
                            case 'Areanet\PIM\Entity\User':
                                $annotations['showInList'] = false;
                                $annotations['hide'] = true;
                                break;
                            case 'Areanet\PIM\Entity\File':
                                $annotations['type']     = 'file';
                                $annotations['multiple'] = false;
                                break;
                            default:
                                $annotations['type']     = 'join';
                                $annotations['accept']   = $propertyAnnotation->targetEntity;
                                $annotations['multiple'] = false;
                                break;
                        }

                    }

                    if($propertyAnnotation instanceof \Doctrine\ORM\Mapping\ManyToMany){
                        $annotations['nullable'] = true;
                        switch($propertyAnnotation->targetEntity){
                            case 'Areanet\PIM\Entity\File':
                                $annotations['type']     = 'multifile';
                                $annotations['multiple'] = true;
                                break;
                            default:
                                $annotations['type']     = 'multijoin';
                                $annotations['accept']   = $propertyAnnotation->targetEntity;
                                $annotations['multiple'] = true;
                                break;
                        }

                    }

                    if($propertyAnnotation instanceof \Doctrine\ORM\Mapping\JoinTable){
                        $annotations['foreign'] = $propertyAnnotation->name;
                    }


                }

                $properties[$prop->getName()] = $annotations;
            }

            ksort($list);
            $data[$entity] = array(
                'list' => $list,
                'settings' => $settings,
                'properties' => $properties
            );
        }

        return $data;
    }



    public function mailAction(Request $request)
    {


        $mailto = $request->get("mailto");
        if(!$mailto){
            return new JsonResponse(array('message' => "No mailto address"), 500);
        }
        $data    = $request->get("data", array());
        $subject = $request->get("subject", "Anfrage über PIM-API");

        $body = "";
        foreach($data as $name=>$value){
            $body .= $name.":\t\t\t".$value."\n";
        }

        mail($mailto, $subject, $body, 'From: '.APP_MAILFROM);

        $return = array(
            'mailto' => $mailto,
            'subject' => $subject
        );

        $data = json_encode($return);
        return new JsonResponse(array('message' => "ok", "data" => $data));
    }
}