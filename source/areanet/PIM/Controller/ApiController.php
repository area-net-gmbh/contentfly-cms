<?php
namespace Areanet\PIM\Controller;

use Areanet\PIM\Classes\Annotations\ManyToMany;
use Areanet\PIM\Classes\Annotations\MatrixChooser;
use \Areanet\PIM\Classes\Config;
use Areanet\PIM\Classes\Controller\BaseController;
use Areanet\PIM\Classes\Exceptions\Config\EntityDuplicateException;
use Areanet\PIM\Classes\Exceptions\Config\EntityNotFoundException;
use Areanet\PIM\Classes\File\Backend;
use Areanet\PIM\Classes\File\Backend\FileSystem;
use Areanet\PIM\Classes\File\Processing;
use Areanet\PIM\Classes\File\Processing\Standard;
use Areanet\PIM\Classes\Push;
use Areanet\PIM\Entity\Base;
use Areanet\PIM\Entity\BaseSortable;
use Areanet\PIM\Entity\BaseTree;
use Areanet\PIM\Entity\File;
use Areanet\PIM\Entity\Log;
use Areanet\PIM\Entity\User;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\Query;
use Silex\Application;

use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;


class ApiController extends BaseController
{
    protected $_MIMETYPES = array(
        'images' => array('image/jpeg', 'image/png', 'image/gif'),
        'pdf' => array('application/pdf')
    );

    /**
     * @apiVersion 1.3.0
     * @api {post} /api/single single
     * @apiName Single
     * @apiGroup Objekte
     * @apiHeader {String} X-Token Acces-Token
     * @apiHeader {String} Content-Type=application/json
     *
     * @apiParam {String} entity Auszulesende Entity
     * @apiParam {Boolean} group Nur Rückgabe der Anzahl der Objekte
     * @apiParam {Object} order="{'id': 'DESC'}" Sortierung: <code>{'date': 'ASC/DESC',...}</code>
     * @apiParam {Object} where Bedingung, mehrere Felder werden mit AND verknüpft: <code>{'title': 'test', 'desc': 'foo',...}</code>
     * @apiParam {Integer} currentPage Aktuelle Seite für Pagination
     * @apiParam {Integer} itemsPerPage="Config::FRONTEND_ITEMS_PER_PAGE" Anzahl Objekte pro Seite bei Pagination
     * @apiParam {Boolean} flatten="false" Gibt bei Joins lediglich die IDs und nicht die kompletten Objekte zurück
     * @apiParamExample {json} Request-Beispiel:
     *     {
     *      "entity": "News",
     *      "id": 1
     *     }
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "message": "singleAction",
     *       "data:" {
     *          "id": 1,
     *          "isHidden": false,
     *          "isDeleted": false,
     *          "title": "Eine News"
     *       }
     *   }
     * @apiError 404 Objekt nicht gefunden
     */
    public function singleAction(Request $request)
    {
        $data = array();

        $entityName = $request->get('entity');
        $id     = $request->get('id');

        if(substr($entityName, 0, 3) == 'PIM'){
            $entityNameToLoad = 'Areanet\PIM\Entity\\'.substr($entityName, 4);
        }else{
            $entityName = ucfirst($request->get('entity'));
            $entityNameToLoad = 'Custom\Entity\\'.ucfirst($entityName);
        }

        $object = $this->em->getRepository($entityNameToLoad)->find($id);
        if(!$object){
            return new JsonResponse(array('message' => "Object not found"), 404);
        }

        /*if($object instanceof File){
            $backend = Backend::getInstance();
            $object->uris = array(
                'original' => $backend->getWebUri($object)
            );

            $processor = Processing::getInstance($object->getType());
            if(!($processor instanceof Standard)){
                foreach($this->app['thumbnailSettings'] as $thumbnailSetting){
                    $object->uris[$thumbnailSetting->getAlias()] =  $backend->getWebUri($object, $thumbnailSetting);
                }
            }

        }*/

        return new JsonResponse(array('message' => "singleAction", 'data' => $object));
    }

    /**
     * @apiVersion 1.3.0
     * @api {post} /api/tree tree
     * @apiName Baumansicht
     * @apiGroup Objekte
     * @apiHeader {String} X-Token Acces-Token
     * @apiHeader {String} Content-Type=application/json
     *
     * @apiParam {String} entity Auszulesende Entity
     * @apiParamExample {json} Request-Beispiel:
     *     {
     *      "entity": "Category",
     *     }
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "message": "treeAction",
     *       "data:" [
     *          {
     *              "id": 1,
     *              "isHidden": false,
     *              "isDeleted": false,
     *              "title": "Eine Kategorie",
     *              "treeChilds" : [
     *                  {
     *                      ....
     *                  }
     *              ]
     *          },
     *          {...},
     *          ...
     *      ]
     *   }
     */
    public function treeAction(Request $request)
    {
        $entityName   = $request->get('entity');

        if(substr($entityName, 0, 3) == 'PIM'){
            $entityNameToLoad = 'Areanet\PIM\Entity\\'.substr($entityName, 4);
        }else{
            $entityName = ucfirst($request->get('entity'));
            $entityNameToLoad = 'Custom\Entity\\'.ucfirst($entityName);
        }

        return new JsonResponse(array('message' => "treeAction", 'data' => $this->loadTree($entityNameToLoad, null)));
    }

    protected function loadTree($entity, $parent){
        $objects = $this->em->getRepository($entity)->findBy(array('treeParent' => $parent), array('sorting' => 'ASC'));
        $array   = array();
        foreach($objects as $object){
            $data = $object->toValueObject(true);
            $data->treeChilds = $this->loadTree($entity, $object);
            $array[] = $data;
        }
        return $array;
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
     * @apiParam {Boolean} [group] Nur Rückgabe der Anzahl der Objekte
     * @apiParam {Object} [order="{'id': 'DESC'}"] Sortierung: <code>{'date': 'ASC/DESC',...}</code>
     * @apiParam {Object} [where] Bedingung, mehrere Felder werden mit AND verknüpft: <code>{'title': 'test', 'desc': 'foo',...}</code>
     * @apiParam {Integer} [currentPage] Aktuelle Seite für Pagination
     * @apiParam {Integer} [itemsPerPage="Config::FRONTEND_ITEMS_PER_PAGE"] Anzahl Objekte pro Seite bei Pagination
     * @apiParam {Boolean} [flatten="false"] Gibt bei Joins lediglich die IDs und nicht die kompletten Objekte zurück
     * @apiParam {Array} [properties=null] Gibt nur die angebenenen Eigenschaften/Felder zurück, ansonsten werden alle Eigenschaften geladen (Performance!)
     * @apiParam {String} [lastModified="yyyymmdd hh:mm:ii"] Es werden nur die Objekte zurückgegeben, die seit lastModified geändert wurden.
     * @apiParamExample {json} Request-Beispiel mit Where-Abfrage:
     *     {
     *      "entity": "News",
     *      "currentPage": 1,
     *      "order": {
     *          "date": "DESC"
     *       },
     *      "where": {
     *          "title": "foo",
     *          "isHidden": false
     *      },
     *      "properties": ["id", "title"]
     * @apiParamExample {json} Request-Beispiel zuletzt aktualisierte Objekte
     *     {
     *      "entity": "News",
     *      "lastModified": "2016-02-20 15:30:22"
     *     }
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "message": "listAction",
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

        $entityName             = $request->get('entity');
        $doCount                = $request->get('count', false);
        $order                  = $request->get('order', null);
        $where                  = $request->get('where', null);
        $currentPage            = $request->get('currentPage');
        $itemsPerPage           = $request->get('itemsPerPage', Config\Adapter::getConfig()->FRONTEND_ITEMS_PER_PAGE);
        $flatten                = $request->get('flatten', false);
        $properties             = $request->get('properties', array());
        $properties             = is_array($properties) ? $properties : array();
        $lastModified           = $request->get('lastModified', null);

        if(!empty($lastModified)) {
            try {
                $lastModified = new \Datetime($lastModified);
            } catch (\Exception $e) {

            }
        }

        if(substr($entityName, 0, 3) == 'PIM'){
            $entityNameToLoad = 'Areanet\PIM\Entity\\'.substr($entityName, 4);
        }else{
            $entityName = ucfirst($request->get('entity'));
            $entityNameToLoad = 'Custom\Entity\\'.ucfirst($entityName);
        }

        $schema     = $this->getSchema();

        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
            ->select("count(".$entityName.")")
            ->from($entityNameToLoad, $entityName)
            ->where("$entityName.isDeleted = false")
            ->andWhere("$entityName.isIntern = false");

        if($lastModified){
            //$qb->where($qb->expr()->lte('modified', $lastModified));
            $queryBuilder->andWhere($entityName.'.modified >= :lastModified')->setParameter('lastModified', $lastModified);
        }

        if($where){
            $placeholdCounter = 0;
            //$currentPage = 1;

            foreach($where as $field => $value){

                if(!isset($schema[$entityName]['properties'][$field])){
                    continue;
                }

                if($schema[$entityName]['properties'][$field]['type'] == 'multijoin'){
                    if($schema[$entityName]['properties'][$field]['mappedBy']){
                        $searchJoinedEntity = $schema[$entityName]['properties'][$field]['accept'];
                        $searchJoinedObject = $this->em->getRepository($searchJoinedEntity)->find($value);
                        $mappedBy           = $schema[$entityName]['properties'][$field]['mappedBy'];

                        $queryBuilder->leftJoin("$entityName.$field", 'joined');
                        $queryBuilder->andWhere("joined.$mappedBy = :$field");
                        $queryBuilder->setParameter($field, $searchJoinedObject);
                        $placeholdCounter++;
                    }else{
                        $queryBuilder->leftJoin("$entityName.$field", 'k');
                        $queryBuilder->andWhere("k.id = :$field");
                        $queryBuilder->setParameter($field, $value);
                        $placeholdCounter++;
                    }

                }else{
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

            }

            if(isset($where['fulltext'])){
                $orX = $queryBuilder->expr()->orX();
                $fulltextTypes = array('string', 'text', 'textarea', 'rte');

                $orX->add("$entityName.id = :FT_id");
                $queryBuilder->setParameter("FT_id", $where['fulltext']);

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


        $query      = $queryBuilder->getQuery();
        $totalObjects = $query->getSingleScalarResult();

        //die($currentPage*$itemsPerPage . " = " . $totalObjects);
        if($currentPage*$itemsPerPage > $totalObjects){
            $currentPage = ceil($totalObjects/$itemsPerPage);
        }

        if($currentPage) {
            $queryBuilder
                ->setFirstResult($itemsPerPage * ($currentPage - 1))
                ->setMaxResults($itemsPerPage)
            ;
        }

        if($order != null){
            foreach($order as $orderBy => $orderSort){
                $queryBuilder->addOrderBy($entityName.'.'.$orderBy, $orderSort);
            }
        }else{
            $queryBuilder->orderBy($entityName.'.id', 'DESC');
        }

        if(count($properties) > 0){
            $partialProperties = implode(',', $properties);
            $queryBuilder->select('partial '.$entityName.'.{'.$partialProperties.'}');
            $query  = $queryBuilder->getQuery();
        }else{
            $queryBuilder->select($entityName);
            $query = $queryBuilder->getQuery();
        }


        $objects = $query->getResult();

        if($doCount){
            return new JsonResponse(array('message' => "listAction", 'data' => count($objects)));
        }

        if(!$objects){
            return new JsonResponse(array('message' => "Not found"), 404);
        }


        $array = array();
        foreach($objects as $object){
            
            $objectData = $object->toValueObject($flatten, $properties);

            foreach($schema[$entityName]['properties'] as $key => $config){
                if(count($properties) && !in_array($key, $properties)){
                    continue;
                }
                if($flatten){
                    if (isset($config['type']) && $config['type'] == 'multifile') {
                        $getterName = 'get' . ucfirst($key);
                        $multiFiles = $object->$getterName();
                        $multiData = array();
                        foreach ($multiFiles as $multiFile) {
                            $multiData[] =  $multiFile->getid();
                        }
                        $objectData->$key = $multiData;
                    }

                    if (isset($config['type']) && $config['type'] == 'multijoin') {
                        $getterName = 'get' . ucfirst($key);
                        $multiFiles = $object->$getterName();
                        $multiData = array();
                        foreach ($multiFiles as $multiFile) {
                            $multiData[] =  $multiFile->getid();
                        }
                        $objectData->$key = $multiData;
                    }
                }else {
                    if (isset($config['type']) && $config['type'] == 'multifile') {
                        $getterName = 'get' . ucfirst($key);
                        $multiFiles = $object->$getterName();
                        $multiData = array();
                        foreach ($multiFiles as $multiFile) {
                            if (!$multiFile->getIsDeleted()) $multiData[] = $multiFile->toValueObject();
                        }
                        $objectData->$key = $multiData;
                    }

                    if (isset($config['type']) && $config['type'] == 'multijoin') {
                        $getterName = 'get' . ucfirst($key);
                        $multiFiles = $object->$getterName();
                        $multiData = array();
                        foreach ($multiFiles as $multiFile) {
                            if (!$multiFile->getIsDeleted()) $multiData[] = $multiFile->toValueObject();
                        }
                        $objectData->$key = $multiData;
                    }
                }
            }

            $array[] = $objectData;

        }


        if($currentPage) {
            $data = array('message' => "listAction",  'data' => $array, 'itemsPerPage' => $itemsPerPage, 'totalItems' => $totalObjects);

            if($lastModified){
                $currentDate = new \Datetime();
                $data['lastModified'] = $currentDate->format('Y-m-d H:i:s');
            }

            return new JsonResponse($data);
        } else {
            $data = array('message' => "listAction", 'data' => $array);

            if($lastModified){
                $currentDate = new \Datetime();
                $data['lastModified'] = $currentDate->format('Y-m-d H:i:s');
            }

            return new JsonResponse($data);
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

        $object->setIsDeleted(true);

        $this->em->remove($object);
        $this->em->flush();

        $object->setId($id);
        $this->em->persist($object);
        $this->em->getClassMetaData(get_class($object))->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);
        $this->em->getClassMetaData(get_class($object))->setIdGenerator(new \Doctrine\ORM\Id\AssignedGenerator());
        $this->em->flush();

        $schema = $this->getSchema();
        if($schema[ucfirst($entityName)]['settings']['isSortable']){
            $oldPos = $object->getSorting();
            //@todo: ACHTUNG BEI NESTED SET / KATEGORIEN
            $query = $this->em->createQuery("UPDATE $entityPath e SET e.sorting = e.sorting - 1 WHERE e.isDeleted = false AND e.sorting > $oldPos");
            $query->execute();
        }

        $this->em->flush();

        /**
         * Log delete actions
         */
        $log = new Log();

        $log->setModelId($object->getId());
        $log->setModelName(ucfirst($request->get('entity')));
        $log->setUser($app['auth.user']);
        $log->setMode('Gelöscht');

        if($schema[ucfirst($entityName)]['settings']['labelProperty']){
            $labelGetter = 'get'.ucfirst($schema[ucfirst($entityName)]['settings']['labelProperty']);
            $label = $object->$labelGetter();
            $log->setModelLabel($label);
        }

        $this->em->persist($log);
        $this->em->flush();

        return new JsonResponse(array('message' => 'deleteAction: '.$id));
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

        try {
            $object = $this->insert($entityName, $data, $app['auth.user']);
        }catch(EntityDuplicateException $e){
            return new JsonResponse(array('message' => $e->getMessage()), 500);
        }catch(Exception $e){
            return new JsonResponse(array('message' => $e->getMessage()), $e->getCode());
        }

        return new JsonResponse(array('message' => 'Object inserted', 'id' => $object->getId(), "data" => ($object)));
    }

    protected function insert($entityName, $data, $user)
    {
        $schema              = $this->getSchema();

        $entityPath = 'Custom\Entity\\'.ucfirst($entityName);
        if(substr($entityName, 0, 3) == "PIM"){
            $entityPath = 'Areanet\PIM\Entity\\'.substr($entityName, 4);
        }
        $object     = new $entityPath();
        //Todo: Validierung!


        foreach($data as $property => $value){
            $setter = 'set'.ucfirst($property);
            $getter = 'get'.ucfirst($property);

            //Todo: dynamisch alle Joins
            if(!isset($schema[ucfirst($entityName)]['properties'][$property])){
                throw new \Exception("Unkown property $property for entity $entityPath", 500);
            }
            switch($schema[ucfirst($entityName)]['properties'][$property]['type']){
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

                    if($schema[ucfirst($entityName)]['properties'][$property]['sortable']){
                        $acceptFrom = $schema[ucfirst($entityName)]['properties'][$property]['acceptFrom'];
                        $mappedFrom = $schema[ucfirst($entityName)]['properties'][$property]['mappedFrom'];
                        $mappedBy   = $schema[ucfirst($entityName)]['properties'][$property]['mappedBy'];

                        $sorting = 0;
                        foreach($value as $id){
                            $objectToJoin = $this->em->getRepository('Areanet\PIM\Entity\File')->find($id);
                            if($objectToJoin->getIsDeleted()) continue;

                            $mappedEntity = new $acceptFrom();

                            $mappedSetter = 'set'.ucfirst($mappedFrom);
                            $mappedEntity->$mappedSetter($object);

                            $mappedBySetter = 'set'.ucfirst($mappedBy);
                            $mappedEntity->$mappedBySetter($objectToJoin);

                            $mappedEntity->setSorting($sorting);

                            $this->em->persist($mappedEntity);
                            $collection->add($mappedEntity);

                            $sorting++;
                        }

                        $object->$getter()->clear();
                        $object->$setter($collection);
                    }else {

                        if (!is_array($value) || !count($value)) {
                            $object->$setter($collection);
                            continue;
                        }

                        foreach ($value as $id) {
                            $objectToJoin = $this->em->getRepository('Areanet\PIM\Entity\File')->find($id);
                            $collection->add($objectToJoin);
                        }

                        $object->$setter($collection);
                    }

                    break;
                case 'join':
                    $entity = $schema[ucfirst($entityName)]['properties'][$property]['accept'];
                    $objectToJoin = $this->em->getRepository($entity)->find($value);
                    $object->$setter($objectToJoin);
                    break;
                case 'multijoin':
                    $collection = new ArrayCollection();
                    $entity     = $schema[ucfirst($entityName)]['properties'][$property]['accept'];

                    if(!is_array($value) || !count($value)){
                        continue;
                    }

                    foreach($value as $id){
                        $objectToJoin = $this->em->getRepository($entity)->find($id);
                        if(!$objectToJoin->getIsDeleted()) $collection->add($objectToJoin);
                    }

                    $object->$setter($collection);

                    break;
                case 'matrixchooser':
                    $collection = new ArrayCollection();

                    $acceptFrom = $schema[ucfirst($entityName)]['properties'][$property]['acceptFrom'];
                    $mappedFrom = $schema[ucfirst($entityName)]['properties'][$property]['mappedFrom'];


                    if(!is_array($value) || !count($value)){
                        continue;
                    }

                    foreach($value as $subobject){
                        $mappedEntity   = new $acceptFrom();

                        $target1Entity  = $schema[ucfirst($entityName)]['properties'][$property]['target1Entity'];
                        $mapped1By      = $schema[ucfirst($entityName)]['properties'][$property]['mapped1By'];
                        $target2Entity  = $schema[ucfirst($entityName)]['properties'][$property]['target2Entity'];
                        $mapped2By      = $schema[ucfirst($entityName)]['properties'][$property]['mapped2By'];

                        $object1ToJoin  = $this->em->getRepository($target1Entity)->find($subobject[$mapped1By]);
                        if(!$object1ToJoin){
                            continue;
                        }
                        $mapped1Setter = 'set'.ucfirst($mapped1By);
                        $mappedEntity->$mapped1Setter($object1ToJoin);

                        $object2ToJoin  = $this->em->getRepository($target2Entity)->find($subobject[$mapped2By]);
                        if(!$object2ToJoin){
                            continue;
                        }
                        $mapped2Setter = 'set'.ucfirst($mapped2By);
                        $mappedEntity->$mapped2Setter($object2ToJoin);

                        $mappedSetter = 'set'.ucfirst($mappedFrom);
                        $mappedEntity->$mappedSetter($object);

                        $this->em->persist($mappedEntity);
                        $collection->add($mappedEntity);
                    }

                    $object->$setter($collection);

                    break;
                case 'onejoin':
                    $joinEntity = $schema[ucfirst($entityName)]['properties'][$property]['accept'];

                    $joinObject = $this->insert($joinEntity, $value, $user);
                    $object->$setter($joinObject);

                    break;
                case 'integer':
                    $object->$setter(intval($value));
                    break;
                case 'select':
                    if($schema[ucfirst($entityName)]['properties'][$property]['dbtype'] == 'integer'){
                        $object->$setter(intval($value));
                    }else{
                        $object->$setter($value);
                    }
                    break;
                case 'string':
                case 'datetime':
                case 'decimal':
                case 'float':
                case 'textarea':
                case 'text':
                case 'rte':
                case 'boolean':
                case 'password':
                    $object->$setter($value);
                    break;
            }

        }

        if($object instanceof Base){
            $object->setUser($user);
        }


        try {

            $this->em->persist($object);

            if($schema[ucfirst($entityName)]['settings']['isSortable']){
                //@todo: ACHTUNG BEI NESTED SET / KATEGORIEN
                $query = $this->em->createQuery("UPDATE $entityPath e SET e.sorting = e.sorting + 1 WHERE e.isDeleted = false");
                $query->execute();
            }

            $this->em->flush();

            $isPush = $schema[$entityName]['settings']['isPush'];
            if($isPush){
                $pushTitle  = $schema[$entityName]['settings']['pushTitle'];
                $pushText   = $schema[$entityName]['settings']['pushText'];
                $pushObject = $schema[$entityName]['settings']['pushObject'];

                $push = new Push($this->em, $object);
                $push->send($pushTitle, $pushText, $pushObject);
            }

            /**
             * Log insert actions
             */
            $log = new Log();

            $log->setModelId($object->getId());
            $log->setModelName($entityName);
            $log->setUser($user);
            $log->setMode('Erstellt');

            if($schema[ucfirst($entityName)]['settings']['labelProperty']){
                $labelGetter = 'get'.ucfirst($schema[ucfirst($entityName)]['settings']['labelProperty']);
                $label = $object->$labelGetter();
                $log->setModelLabel($label);
            }

            $this->em->persist($log);
            $this->em->flush();
        }catch(UniqueConstraintViolationException $e){
            if($entityPath == 'Areanet\PIM\Entity\User'){
                throw new EntityDuplicateException("Ein Benutzer mit diesem Benutzername ist bereits vorhanden.");
            }
            $uniqueObjectLoaded = false;

            foreach($schema[$entityName]['properties'] as $property => $propertySettings){

                if($propertySettings['unique']){
                    $object = $this->em->getRepository($entityPath)->findOneBy(array($property => $data[$property]));
                    if(!$object){
                        throw new Exception("Unbekannter Fehler", 501);
                    }
                    $uniqueObjectLoaded = true;
                    break;
                }
            }

            if(!$uniqueObjectLoaded) throw new Exception("Unbekannter Fehler", 500);
        }

        return $object;
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

        try{
            $this->update($entityName, $id, $data, $disableModifiedTime, $app['auth.user']);
        }catch(EntityDuplicateException $e){
            return new JsonResponse(array('message' => $e->getMessage()), 500);
        }catch(EntityNotFoundException $e){
            return new JsonResponse(array('message' => "Not found"), 404);
        }

        return new JsonResponse(array('message' => 'updateAction', 'id' => $id));

    }
    
    public function multiupdateAction(Request $request, Application $app)
    {
        $objects             = $request->get('objects');
        $disableModifiedTime = $request->get('disableModifiedTime');
        
        foreach($objects as $object){
            try{
                $this->update($object['entity'], $object['id'], $object['data'], $disableModifiedTime, $app['auth.user']);
            }catch(EntityDuplicateException $e){
                continue;
            }catch(EntityNotFoundException $e){
                continue;
            }
        }

        return new JsonResponse(array('message' => 'multiupdate'));
    }


    /**
     * @param string $entityName
     * @param integer $id
     * @param array $data
     * @param boolean $disableModifiedTime
     * @param User $user
     * @return JsonResponse
     */
    protected function update($entityName, $id, $data, $disableModifiedTime, $user = null)
    {
        $schema              = $this->getSchema();

        $entityPath = 'Custom\Entity\\'.ucfirst($entityName);
        if(substr($entityName, 0, 3) == "PIM"){
            $entityPath = 'Areanet\PIM\Entity\\'.substr($entityName, 4);
        }


        $object = $this->em->getRepository($entityPath)->find($id);
        if(!$object){
            throw new EntityNotFoundException();

        }

        //Todo: Validierung!

        foreach($data as $property => $value){
            $setter = 'set'.ucfirst($property);
            $getter = 'get'.ucfirst($property);

            //Todo: dynamisch alle Joins?
            if(!isset($schema[ucfirst($entityName)]['properties'][$property])){
                throw new \Exception("Unkown property $property for entity $entityPath", 500);
            }
            switch($schema[ucfirst($entityName)]['properties'][$property]['type']){
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

                    if($schema[ucfirst($entityName)]['properties'][$property]['sortable']){
                        $acceptFrom = $schema[ucfirst($entityName)]['properties'][$property]['acceptFrom'];
                        $mappedFrom = $schema[ucfirst($entityName)]['properties'][$property]['mappedFrom'];
                        $mappedBy   = $schema[ucfirst($entityName)]['properties'][$property]['mappedBy'];

                        $object->$getter()->clear();
                        $query = $this->em->createQuery('DELETE FROM '.$acceptFrom.' e WHERE e.'.$mappedFrom.' = ?1');
                        $query->setParameter(1, $id);
                        $query->execute();

                        $sorting = 0;
                        foreach($value as $id){
                            $objectToJoin = $this->em->getRepository('Areanet\PIM\Entity\File')->find($id);
                            if($objectToJoin->getIsDeleted()) continue;

                            $mappedEntity = new $acceptFrom();

                            $mappedSetter = 'set'.ucfirst($mappedFrom);
                            $mappedEntity->$mappedSetter($object);

                            $mappedBySetter = 'set'.ucfirst($mappedBy);
                            $mappedEntity->$mappedBySetter($objectToJoin);

                            $mappedEntity->setSorting($sorting);

                            $this->em->persist($mappedEntity);
                            $collection->add($mappedEntity);

                            $sorting++;
                        }

                        $object->$getter()->clear();
                        $object->$setter($collection);
                    }else{
                        if(!is_array($value) || !count($value)){
                            $object->$getter()->clear();
                            continue;
                        }

                        foreach($value as $id){

                            $objectToJoin = $this->em->getRepository('Areanet\PIM\Entity\File')->find($id);
                            if(!$objectToJoin->getIsDeleted()) $collection->add($objectToJoin);
                        }

                        $object->$getter()->clear();
                        $object->$setter($collection);
                    }


                    break;
                case 'join':
                    $entity = $schema[ucfirst($entityName)]['properties'][$property]['accept'];
                    $objectToJoin = $this->em->getRepository($entity)->find($value);
                    $object->$setter($objectToJoin);
                    break;
                case 'multijoin':
                    $collection = new ArrayCollection();
                    $entity     = $schema[ucfirst($entityName)]['properties'][$property]['accept'];
                    $mappedBy   = isset($schema[ucfirst($entityName)]['properties'][$property]['mappedBy']) ? $schema[ucfirst($entityName)]['properties'][$property]['mappedBy'] : null;

                    if($mappedBy){
                        $acceptFrom = $schema[ucfirst($entityName)]['properties'][$property]['acceptFrom'];
                        $mappedFrom = $schema[ucfirst($entityName)]['properties'][$property]['mappedFrom'];

                        $object->$getter()->clear();
                        $query = $this->em->createQuery('DELETE FROM '.$acceptFrom.' e WHERE e.'.$mappedFrom.' = ?1');
                        $query->setParameter(1, $id);
                        $query->execute();
                    }else{
                        $object->$getter()->clear();
                    }

                    if(!is_array($value) || !count($value)){
                        continue;
                    }

                    $sorting = 0;
                    foreach($value as $id){


                        $objectToJoin = $this->em->getRepository($entity)->find($id);

                        if(!$objectToJoin->getIsDeleted()){


                            if($mappedBy){
                                $isSortable     = $schema[ucfirst($entityName)]['properties'][$property]['sortable'];
                                $acceptFrom     = $schema[ucfirst($entityName)]['properties'][$property]['acceptFrom'];
                                $mappedFrom     = $schema[ucfirst($entityName)]['properties'][$property]['mappedFrom'];
                                $mappedEntity   = new $acceptFrom();

                                $mappedSetter = 'set'.ucfirst($mappedBy);
                                $mappedEntity->$mappedSetter($objectToJoin);

                                $mappedSetter = 'set'.ucfirst($mappedFrom);
                                $mappedEntity->$mappedSetter($object);

                                if($isSortable){
                                    $mappedEntity->setSorting($sorting++);
                                }

                                $this->em->persist($mappedEntity);
                                $collection->add($mappedEntity);
                            }else{
                                $collection->add($objectToJoin);
                            }

                        }
                    }

                    $object->$setter($collection);

                    break;
                case 'matrixchooser':
                    $collection = new ArrayCollection();

                    $acceptFrom = $schema[ucfirst($entityName)]['properties'][$property]['acceptFrom'];
                    $mappedFrom = $schema[ucfirst($entityName)]['properties'][$property]['mappedFrom'];

                    $object->$getter()->clear();
                    $query = $this->em->createQuery('DELETE FROM '.$acceptFrom.' e WHERE e.'.$mappedFrom.' = ?1');
                    $query->setParameter(1, $id);
                    $query->execute();

                    $object->$setter($collection);

                    if(!is_array($value) || !count($value)){
                        continue;
                    }

                    foreach($value as $subobject){
                        $mappedEntity   = new $acceptFrom();

                        $target1Entity  = $schema[ucfirst($entityName)]['properties'][$property]['target1Entity'];
                        $mapped1By      = $schema[ucfirst($entityName)]['properties'][$property]['mapped1By'];
                        $target2Entity  = $schema[ucfirst($entityName)]['properties'][$property]['target2Entity'];
                        $mapped2By      = $schema[ucfirst($entityName)]['properties'][$property]['mapped2By'];

                        $object1ToJoin  = $this->em->getRepository($target1Entity)->find($subobject[$mapped1By]);
                        if(!$object1ToJoin){
                            continue;
                        }
                        $mapped1Setter = 'set'.ucfirst($mapped1By);
                        $mappedEntity->$mapped1Setter($object1ToJoin);

                        $object2ToJoin  = $this->em->getRepository($target2Entity)->find($subobject[$mapped2By]);
                        if(!$object2ToJoin){
                            continue;
                        }
                        $mapped2Setter = 'set'.ucfirst($mapped2By);
                        $mappedEntity->$mapped2Setter($object2ToJoin);

                        $mappedSetter = 'set'.ucfirst($mappedFrom);
                        $mappedEntity->$mappedSetter($object);

                        $this->em->persist($mappedEntity);
                        $collection->add($mappedEntity);
                    }

                    $object->$setter($collection);

                    break;
                case 'onejoin':
                    $joinEntity = $schema[ucfirst($entityName)]['properties'][$property]['accept'];


                    if(!empty($value['id'])){
                        $this->update($joinEntity, $value['id'], $value, false, $user);
                    }else{
                        $joinObject = $this->insert($joinEntity, $value, $user);
                        $object->$setter($joinObject);
                    }
                    break;
                case 'datetime':
                    //echo($setter." = ".$value);
                    if(!is_array($value) && $value){
                        $object->$setter($value);
                    }else{
                        $object->$setter(null);
                    }
                    break;
                case 'integer':
                    $object->$setter(intval($value));
                    break;
                case 'select':
                    if($schema[ucfirst($entityName)]['properties'][$property]['dbtype'] == 'integer'){
                        $object->$setter(intval($value));
                    }else{
                        $object->$setter($value);
                    }
                    break;
                case 'password':
                case 'string':
                case 'decimal':
                case 'float':
                case 'textarea':
                case 'text':
                case 'rte':
                case 'boolean':
                case 'entity':
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
        $object->setUser($user);

        try{
            if($disableModifiedTime){
                $object->doDisableModifiedTime(true);
            }

            $this->em->persist($object);
            $this->em->flush();

        }catch(UniqueConstraintViolationException $e){
            if($entityPath == 'Areanet\PIM\Entity\User'){
                throw new EntityDuplicateException("Ein Benutzer mit diesem Benutzername ist bereits vorhanden.");
            }else{
                throw new EntityDuplicateException("Ein gleicher Eintrag ist bereits vorhanden.");
            }
        }

        /**
         * Log update actions
         */
        $log = new Log();

        $log->setModelId($object->getId());
        $log->setModelName(ucfirst($entityName));
        if($user) $log->setUser($user);
        $log->setMode('Geändert');

        if($schema[ucfirst($entityName)]['settings']['labelProperty']){
            $labelGetter = 'get'.ucfirst($schema[ucfirst($entityName)]['settings']['labelProperty']);
            $label = $object->$labelGetter();
            $log->setModelLabel($label);
        }

        $this->em->persist($log);
        $this->em->flush();
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
     * @apiParam {Boolean} [flatten="false"] Gibt bei Joins lediglich die IDs und nicht die kompletten Objekte zurück
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
        $timestamp              = $request->get('lastModified');
        $filedata               = $request->get('filedata');
        $check                  = $request->get('check', false);
        $flatten                = $request->get('flatten', false);

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
            if(substr($entityName, 0, 11) == 'Areanet\\PIM'){
                $entityShortcut = 'PIM\\'.$entityShortcut;
            }
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

                $objectData = $object->toValueObject($flatten);

                if(isset($schema[$entityShortcut])) {



                    foreach ($schema[$entityShortcut]['properties'] as $key => $config) {
                        if($flatten){
                            if (isset($config['type']) && $config['type'] == 'multifile') {
                                $getterName = 'get' . ucfirst($key);
                                $multiFiles = $object->$getterName();
                                $multiData = array();
                                foreach ($multiFiles as $multiFile) {
                                    $multiData[] =  $multiFile->getid();
                                }
                                $objectData->$key = $multiData;
                            }

                            if (isset($config['type']) && $config['type'] == 'multijoin') {
                                $getterName = 'get' . ucfirst($key);
                                $multiFiles = $object->$getterName();
                                $multiData = array();
                                foreach ($multiFiles as $multiFile) {
                                    $multiData[] =  $multiFile->getid();
                                }
                                $objectData->$key = $multiData;
                            }
                        }else {
                            if (isset($config['type']) && $config['type'] == 'multifile') {
                                $getterName = 'get' . ucfirst($key);
                                $multiFiles = $object->$getterName();
                                $multiData = array();
                                foreach ($multiFiles as $multiFile) {
                                    if (!$multiFile->getIsDeleted()) $multiData[] = $multiFile->toValueObject();
                                }
                                $objectData->$key = $multiData;
                            }

                            if (isset($config['type']) && $config['type'] == 'multijoin') {

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

                if($object->getId() == '2949'){
                    //$test = $object->getWebInformationen()->toValueObject();

                }

                $array[] = $objectData;

            }

            $all[$entityShortcut] = $array;
        }

        $currentDate = new \Datetime();
        $jsonResponse = new JsonResponse(array('message' => 'allAction',  'lastModified' => $currentDate->format('Y-m-d H:i:s'),  'data' => $all), count($all) ? 200 : 204);

        if($filedata != null) file_put_contents(ROOT_DIR.'/data/currentData.json', ($all));

        return $jsonResponse;
    }

    /**
     * @apiVersion 1.3.0
     * @api {get} /api/config config
     * @apiName Config
     * @apiGroup Settings
     * @apiHeader {String} Content-Type=application/json
     *
     * @apiDescription Grundlegende, frei-zugängliche Konfiguration, z.B. für Login-Seite
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
    public function configAction()
    {
        $frontend = array(
            'customLogo' => Config\Adapter::getConfig()->FRONTEND_CUSTOM_LOGO
        );

        return new JsonResponse(array('message' => 'configAction', 'frontend' => $frontend, 'devmode' => Config\Adapter::getConfig()->APP_DEBUG, 'version' => APP_VERSION));
    }

    /**
     * @apiVersion 1.3.0
     * @api {get} /api/schema schema
     * @apiName Schema
     * @apiGroup Settings
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
        $cacheFile = ROOT_DIR.'/data/cache/schema.cache';

        if(Config\Adapter::getConfig()->APP_ENABLE_SCHEMA_CACHE){

            if(file_exists($cacheFile)){

                $data = unserialize(file_get_contents($cacheFile));
                return $data;
            }
        }

        $entities = array();

        $entityFolder = __DIR__.'/../../../custom/Entity/';
        foreach (new \DirectoryIterator($entityFolder) as $fileInfo) {
            if($fileInfo->isDot()) continue;
            $entities[] = $fileInfo->getBasename('.php');
        }
        $entities[] = "PIM\\File";
        $entities[] = "PIM\\Tag";
        $entities[] = "PIM\\User";
        $entities[] = "PIM\\Log";
        $entities[] = "PIM\\PushToken";
        $entities[] = "PIM\\ThumbnailSetting";

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

            $defaultValues = $reflect->getDefaultProperties();

            $annotationReader = new AnnotationReader();

            $settings = array(
                'label' => $entity,
                'readonly' => false,
                'isPush' => false,
                'hide' => false,
                'pushTitle' => '',
                'pushText' => '',
                'pushObject' => '',
                'sortBy' => 'id',
                'sortOrder' => 'DESC',
                'isSortable' => false,
                'labelProperty' => null,
                'type' => 'default',
                'tabs' => array('default' => array('title' => 'Allgemein', 'onejoin' => false))
            );

            if($object instanceof BaseSortable){
                $settings['sortBy']     = 'sorting';
                $settings['sortOrder']  = 'ASC';
                $settings['isSortable'] = true;
            }

            if($object instanceof BaseTree){
                $settings['type']  = 'tree';
            }

            $classAnnotations = $annotationReader->getClassAnnotations($reflect);


            foreach($classAnnotations as $classAnnotation) {

                if ($classAnnotation instanceof \Areanet\PIM\Classes\Annotations\Config) {
                    $settings['label']       = $classAnnotation->label ? $classAnnotation->label : $entity;
                    $settings['labelProperty']= $classAnnotation->labelProperty ? $classAnnotation->labelProperty : $settings['labelProperty'];
                    $settings['readonly']    = $classAnnotation->readonly ? $classAnnotation->readonly : false;
                    $settings['isPush']      = ($classAnnotation->pushText && $classAnnotation->pushTitle);
                    $settings['pushTitle']   = $classAnnotation->pushTitle ? $classAnnotation->pushTitle : null;
                    $settings['pushText']    = $classAnnotation->pushText ? $classAnnotation->pushText : null;
                    $settings['pushObject']  = $classAnnotation->pushObject ? $classAnnotation->pushObject : null;
                    $settings['sortBy']      = $classAnnotation->sortBy ? $classAnnotation->sortBy : $settings['sortBy'];
                    $settings['sortOrder']   = $classAnnotation->sortOrder ? $classAnnotation->sortOrder : $settings['sortOrder'];
                    $settings['hide']        = $classAnnotation->hide ? $classAnnotation->hide : $settings['hide'];

                    if($classAnnotation->tabs){
                        $tabs = json_decode(str_replace("'", '"', $classAnnotation->tabs));
                        foreach($tabs as $key=>$value){
                            $settings['tabs'][$key] = array('title' => $value, 'onejoin' => false);
                        }
                    }
                }
            }

            $list       = array();
            $properties = array();
            foreach ($props as $prop) {


                $annotations = array(
                    'showInList' => false,
                    'listShorten' => 0,
                    'readonly' => false,
                    'hide' => false,
                    'type' => "",
                    'dbtype' => "",
                    'label' => $prop->getName(),
                    'accept' => '*',
                    'rteOptions' => '',
                    'filter' => '',
                    'options' => array(),
                    'foreign' => null,
                    'tab' => null,
                    'sortable' => false,
                    'default' => $defaultValues[$prop->getName()],
                    'isFilterable' => false
                );

                $reflectionProperty = new \ReflectionProperty($className, $prop->getName());


                $propertyAnnotations = $annotationReader->getPropertyAnnotations($reflectionProperty);

                $customMany2ManyAnnotationsIterator = 1;

                foreach($propertyAnnotations as $propertyAnnotation){

                    if($propertyAnnotation instanceof \Areanet\PIM\Classes\Annotations\Config) {

                        $annotations['isFilterable']  = $propertyAnnotation->isFilterable;
                        $annotations['listShorten']  = $propertyAnnotation->listShorten;
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
                        $annotations['tab']         = $propertyAnnotation->tab && !$annotations['tab'] ? $propertyAnnotation->tab : $annotations['tab'];

                        if($propertyAnnotation->type){
                            $annotations['type'] = $propertyAnnotation->type;
                        }

                        if($annotations['type'] == 'onejoin'){

                            $settings['tabs'][$annotations['accept']]['title'] = $annotations['label'];
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
                        $annotations['dbtype']   = $propertyAnnotation->type;

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

                    if($propertyAnnotation instanceof \Doctrine\ORM\Mapping\OneToOne){
                        $annotations['type']     = 'onejoin';

                        $entityPath     = explode('\\', $propertyAnnotation->targetEntity);
                        $one2Oneentity  = $entityPath[(count($entityPath) - 1)];

                        $annotations['accept']   = $one2Oneentity;
                        $annotations['multiple'] = false;

                        $settings['tabs'][$one2Oneentity] =array('title' =>  $annotations['label'], 'onejoin' => true, 'onejoin_field' => $prop->getName());
                        $annotations['tab'] = $one2Oneentity;
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

                                if($settings['type'] == 'tree' && $prop->getName() == 'treeParent'){
                                    $annotations['accept'] = $className;
                                }

                                break;
                        }

                    }

                    /*if($propertyAnnotation instanceof \Doctrine\ORM\Mapping\OneToMany){
                        $annotations['nullable'] = true;
                        $annotations['type']     = 'multijoin';
                        $annotations['accept']   = $propertyAnnotation->targetEntity;
                        $annotations['multiple'] = true;
                        $targetEntity = new $propertyAnnotation->targetEntity();

                    }*/

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

                    if($propertyAnnotation instanceof ManyToMany) {
                        $annotations['type'] = 'multijoin';
                        $annotations['multiple'] = true;

                        if ($customMany2ManyAnnotationsIterator == 1){
                            $targetEntity = new $propertyAnnotation->targetEntity();
                            if($targetEntity instanceof File){
                                $annotations['type'] = 'multifile';
                                $annotations['multiple'] = true;
                                $annotations['accept'] = 'image/*';
                                $annotations['foreign'] = 'produkt_bilder';
                                $annotations['sortable'] = true;
                                $annotations['mappedBy'] = $propertyAnnotation->mappedBy;
                            }else{
                                $annotations['accept'] = $propertyAnnotation->targetEntity;
                                $annotations['mappedBy'] = $propertyAnnotation->mappedBy;
                            }


                        }else {
                            $annotations['accept'.$customMany2ManyAnnotationsIterator] = $propertyAnnotation->targetEntity;
                            $annotations['mappedBy'.$customMany2ManyAnnotationsIterator] = $propertyAnnotation->mappedBy;
                        }

                        $customMany2ManyAnnotationsIterator++;
                    }

                    if($propertyAnnotation instanceof \Doctrine\ORM\Mapping\OneToMany   ){
                        $annotations['acceptFrom'] = $propertyAnnotation->targetEntity;
                        $annotations['mappedFrom'] = $propertyAnnotation->mappedBy;

                        $targetEntity = new $propertyAnnotation->targetEntity();
                        if($targetEntity instanceof  BaseSortable){
                            $annotations['sortable']    = true;
                        }
                    }

                    if($propertyAnnotation instanceof MatrixChooser){
                        $annotations['type'] = 'matrixchooser';
                        $annotations['target1Entity'] = $propertyAnnotation->target1Entity;
                        $annotations['mapped1By'] = $propertyAnnotation->mapped1By;
                        $annotations['target2Entity'] = $propertyAnnotation->target2Entity;
                        $annotations['mapped2By'] = $propertyAnnotation->mapped2By;
                    }


                }

                if(!$annotations['tab']){
                    $annotations['tab'] = 'default';
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

        if(Config\Adapter::getConfig()->APP_ENABLE_SCHEMA_CACHE){
            file_put_contents($cacheFile, serialize($data));
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