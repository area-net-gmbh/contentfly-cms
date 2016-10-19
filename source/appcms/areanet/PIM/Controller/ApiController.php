<?php
namespace Areanet\PIM\Controller;

use Areanet\PIM\Classes\Annotations\ManyToMany;
use Areanet\PIM\Classes\Annotations\MatrixChooser;
use \Areanet\PIM\Classes\Config;
use Areanet\PIM\Classes\Controller\BaseController;
use Areanet\PIM\Classes\Exceptions\Config\EntityDuplicateException;
use Areanet\PIM\Classes\Exceptions\Config\EntityNotFoundException;
use Areanet\PIM\Classes\Exceptions\File\FileExistsException;
use Areanet\PIM\Classes\File\Backend;
use Areanet\PIM\Classes\File\Backend\FileSystem;
use Areanet\PIM\Classes\File\Processing;
use Areanet\PIM\Classes\File\Processing\Standard;
use Areanet\PIM\Classes\Permission;
use Areanet\PIM\Classes\Push;
use Areanet\PIM\Classes\TypeManager;
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
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\Id\AssignedGenerator;
use Doctrine\ORM\Query;
use Silex\Application;

use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;


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

        $data       = array();

        $entityName = $request->get('entity');
        $id         = $request->get('id');
        $schema     = $this->getSchema();

        if (substr($entityName, 0, 3) == 'PIM') {
            $entityNameToLoad = 'Areanet\PIM\Entity\\' . substr($entityName, 4);
        } else {
            $entityName = ucfirst($request->get('entity'));
            $entityNameToLoad = 'Custom\Entity\\' . ucfirst($entityName);
        }

        if(!($permission = Permission::isReadable($this->app['auth.user'], $entityName))){
            throw new AccessDeniedHttpException("Zugriff auf $entityNameToLoad verweigert.");
        }

        $object = $this->em->getRepository($entityNameToLoad)->find($id);

        if (!$object) {
            return new JsonResponse(array('message' => "Object not found"), 404);
        }

        if($permission == \Areanet\PIM\Entity\Permission::OWN && $object->getUserCreated() != $this->app['auth.user']){
            throw new AccessDeniedHttpException("Zugriff auf $entityNameToLoad::$id verweigert.");
        }

        return new JsonResponse(array('message' => "singleAction", 'data' => $object->toValueObject($this->app['auth.user'], $schema, $entityName, false)));

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

        return new JsonResponse(array('message' => "treeAction", 'data' => $this->loadTree($entityName, $entityNameToLoad, null)));
    }

    protected function loadTree($entityName, $entity, $parent){
        $objects = $this->em->getRepository($entity)->findBy(array('treeParent' => $parent, 'isDeleted' => false, 'isIntern' => false), array('sorting' => 'ASC'));
        $array   = array();
        $schema  = $this->getSchema();

        foreach($objects as $object){
            $data = $object->toValueObject($this->app['auth.user'], $schema, $entityName, true);
            $data->treeChilds = $this->loadTree($entityName, $entity, $object);
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
     * @apiParam {Array} [properties] Gibt nur die angebenenen Eigenschaften/Felder zurück, ansonsten werden alle Eigenschaften geladen (Performance!)<code>['feld1', 'feld2', ...]</code>
     * @apiParam {Object} [order="{'id': 'DESC'}"] Sortierung: <code>{'date': 'ASC/DESC',...}</code>
     * @apiParam {String} [groupBy] Gruppierung der Rückgabe nach Eigenschaft
     * @apiParam {Object} [where] Bedingung, mehrere Felder werden mit AND verknüpft: <code>{'title': 'test', 'desc': 'foo',...}</code>
     * @apiParam {Boolean} [count] Nur Rückgabe der Anzahl der Objekte
     * @apiParam {Integer} [currentPage] Aktuelle Seite für Pagination
     * @apiParam {Integer} [itemsPerPage="Config::FRONTEND_ITEMS_PER_PAGE"] Anzahl Objekte pro Seite bei Pagination
     * @apiParam {Boolean} [flatten="false"] Gibt bei Joins lediglich die IDs und nicht die kompletten Objekte zurück
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
        $groupBy                = $request->get('groupBy', false);
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


        if(!($permission = Permission::isReadable($this->app['auth.user'], $entityName))){
            throw new AccessDeniedHttpException("Zugriff auf $entityNameToLoad verweigert.");
        }

        $schema     = $this->getSchema();
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
            ->select("count(".$entityName.")")
            ->from($entityNameToLoad, $entityName)
            ->where("$entityName.isDeleted = false")
            ->andWhere("$entityName.isIntern = false");

        if($permission == \Areanet\PIM\Entity\Permission::OWN){
            $queryBuilder->andWhere("$entityName.userCreated = :userCreated");
            $queryBuilder->setParameter('userCreated', $this->app['auth.user']);
        }

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
                    $value = intval($value);

                    if(isset($schema[$entityName]['properties'][$field]['mappedBy'])){
                        if($value == -1) {
                            $mappedBy           = $schema[$entityName]['properties'][$field]['mappedBy'];
                            $queryBuilder->leftJoin("$entityName.$field", 'joined');
                            $queryBuilder->andWhere("joined.$mappedBy IS NULL");
                        }else{
                            $searchJoinedEntity = $schema[$entityName]['properties'][$field]['accept'];
                            $searchJoinedObject = $this->em->getRepository($searchJoinedEntity)->find($value);
                            $mappedBy           = $schema[$entityName]['properties'][$field]['mappedBy'];

                            $queryBuilder->leftJoin("$entityName.$field", 'joined');
                            $queryBuilder->andWhere("joined.$mappedBy = :$field");
                            $queryBuilder->setParameter($field, $searchJoinedObject);
                            $placeholdCounter++;
                        }

                    }else{

                        $queryBuilder->leftJoin("$entityName.$field", 'k');
                        if($value == -1){
                            $queryBuilder->andWhere("k.id IS NULL");
                        }else{
                            $queryBuilder->andWhere("k.id = :$field");
                            $queryBuilder->setParameter($field, $value);
                            $placeholdCounter++;
                        }

                    }

                }else{
                    switch($schema[$entityName]['properties'][$field]['type']){
                        case 'join':
                            $value = intval($value);
                            if($value == -1){
                                $queryBuilder->andWhere("$entityName.$field IS NULL");
                            }else{
                                $queryBuilder->andWhere("$entityName.$field = :$field");
                                $queryBuilder->setParameter($field, $value);
                                $placeholdCounter++;
                            }
                        break;
                        case 'boolean':
                            if(strtolower($value) == 'false'){
                                $value = 0;
                            }elseif(strtolower($value) == 'true'){
                                $value = 1;
                            }else{
                                $value = boolval($value);
                            }

                            $queryBuilder->andWhere("$entityName.$field = :$field");
                            $queryBuilder->setParameter($field, $value);
                            $placeholdCounter++;

                            break;
                        case 'integer':
                            $value = intval($value);

                            $queryBuilder->andWhere("$entityName.$field = :$field");
                            $queryBuilder->setParameter($field, $value);
                            $placeholdCounter++;

                            break;
                        default:

                            $queryBuilder->andWhere("$entityName.$field = :$field");
                            $queryBuilder->setParameter($field, $value);
                            $placeholdCounter++;

                            break;
                    }


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

                }


            }
        }

        $event = new \Areanet\PIM\Classes\Event();
        $event->setParam('request',        $request);
        $event->setParam('entity',         $entityName);
        $event->setParam('queryBuilder',   $queryBuilder);
        $event->setParam('app',            $this->app);
        $event->setParam('user',           $this->app['auth.user']);

        $this->app['dispatcher']->dispatch('pim.entity.before.list', $event);

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

        if($groupBy){
            $queryBuilder->groupBy($entityName.".".$groupBy);
        }

        if(count($properties) > 0){
            $partialProperties = implode(',', $properties);
            $queryBuilder->select('partial '.$entityName.'.{id,'.$partialProperties.'}');
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
            $objectData = $object->toValueObject($this->app['auth.user'], $schema, $entityName,  $flatten, $properties);

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

        $event = new \Areanet\PIM\Classes\Event();
        $event->setParam('entity',  $entityName);
        $event->setParam('request', $request);
        $event->setParam('id',      $id);
        $event->setParam('user',    $app['auth.user']);
        $event->setParam('app',     $app);

        $this->app['dispatcher']->dispatch('pim.entity.before.delete', $event);

        $object = $this->delete($entityName, $id, $app);

        $schema = $this->getSchema();


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

        $event = new \Areanet\PIM\Classes\Event();
        $event->setParam('entity',  $entityName);
        $event->setParam('request', $request);
        $event->setParam('id',      $id);
        $event->setParam('user',    $app['auth.user']);
        $event->setParam('app',     $app);
        $this->app['dispatcher']->dispatch('pim.entity.after.delete', $event);

        return new JsonResponse(array('message' => 'deleteAction: '.$id));
    }

    public function delete($entityName, $id, Application $app){
        $schema = $this->getSchema();

        $entityPath = 'Custom\Entity\\'.$entityName;
        if(substr($entityName, 0, 3) == "PIM"){
            $entityPath = 'Areanet\PIM\Entity\\'.substr($entityName, 4);
        }

        if(!($permission = Permission::isDeletable($this->app['auth.user'], $entityName))){
            throw new AccessDeniedHttpException("Zugriff auf $entityName verweigert.");
        }

        $object = $this->em->getRepository($entityPath)->find($id);
        if(!$object){
            throw new \Exception("Das Objekt wurde nicht gefunden.", 404);
        }

        if($permission == \Areanet\PIM\Entity\Permission::OWN && $object->getUserCreated() != $this->app['auth.user']){
            throw new AccessDeniedHttpException("Zugriff auf $entityName::$id verweigert.");
        }


        if($entityPath == 'Areanet\PIM\Entity\User'){

            if($object->getAlias() == 'admin'){
                throw new \Exception("Der Hauptadministrator kann nicht gelöscht werden.", 400);
            }

            $alias = $object->getAlias();
            $object->setAlias("$alias-".md5($app['auth.user']->getId().time()));
        }

        $parent = null;
        if($schema[ucfirst($entityName)]['settings']['type'] == 'tree') {
            $subObjects = $this->em->getRepository($entityPath)->findBy(array('treeParent' => $object->getId()));
            if($subObjects){
                foreach($subObjects as $subObject){
                    $this->delete($entityName, $subObject->getId(), $app);
                }
            }
            $parent = $object->getTreeParent();
        }

        if($entityName == 'PIM\\File') {
            $name = $object->getName().'-'.md5(time().$app['auth.user']->getId());
            $object->setName($name);

            //todo: Auslagern in /file/delete-API
            $backend    = Backend::getInstance();

            $path   = $backend->getPath($object);
            foreach (new \DirectoryIterator($path) as $fileInfo) {
                if ($fileInfo->isDot() || !$fileInfo->isFile()) continue;
                unlink($fileInfo->getPathname());
            }
            @rmdir($path);
        }

        foreach($schema[ucfirst($entityName)]['properties'] as $key => $config){
            if($config['unique']){
                $getter = 'get'.ucfirst($key);
                $setter = 'set'.ucfirst($key);

                $oldVal = $object->$getter();
                $object->$setter($oldVal.'-'.md5($app['auth.user']->getId().time()));
            }
        }

        $this->em->remove($object);
        $this->em->flush();


        $deletedObject = new $entityPath();

        foreach($schema[ucfirst($entityName)]['properties'] as $property => $config){
            $setter = 'set'.ucfirst($property);
            $getter = 'get'.ucfirst($property);
            if(isset($config['nullable']) && !$config['nullable']){
                $deletedObject->$setter($object->$getter());
            }
        }

        $deletedObject->setId($id);
        $deletedObject->setIsDeleted(true);
        $deletedObject->setCreated($object->getCreated());
        $deletedObject->setModified($object->getModified());
        $deletedObject->setUser($object->getUser());
        $deletedObject->setUserCreated($object->getUserCreated());
        
        $object = null;

        $metadata = $this->em->getClassMetaData(get_class($deletedObject));
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);
        if(Config\Adapter::getConfig()->DB_GUID_STRATEGY) $metadata->setIdGenerator(new AssignedGenerator());

        if($schema[ucfirst($entityName)]['settings']['type'] == 'tree') {
            $metadata = $this->em->getClassMetaData('Areanet\PIM\Entity\BaseTree');
            $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);
            if(Config\Adapter::getConfig()->DB_GUID_STRATEGY) $metadata->setIdGenerator(new AssignedGenerator());
        }

        $this->em->persist($deletedObject);
        $this->em->flush();

        if($schema[ucfirst($entityName)]['settings']['isSortable']){
            $oldPos = $deletedObject->getSorting();
            if($schema[ucfirst($entityName)]['settings']['type'] == 'tree') {
                if(!$parent){
                    $query  = $this->em->createQuery("UPDATE $entityPath e SET e.sorting = e.sorting - 1 WHERE e.isDeleted = false AND e.sorting > $oldPos AND e.sorting > 0 AND e.treeParent IS NULL");
                }else{
                    $query  = $this->em->createQuery("UPDATE $entityPath e SET e.sorting = e.sorting - 1 WHERE e.isDeleted = false AND e.sorting > $oldPos AND e.sorting > 0 AND e.treeParent = '".$parent->getId()."'");
                }

            }else{
                $query = $this->em->createQuery("UPDATE $entityPath e SET e.sorting = e.sorting - 1 WHERE e.isDeleted = false AND e.sorting > $oldPos AND e.sorting > 0");
            }
            $query->execute();
        }

        $this->em->flush();

        return $deletedObject;
    }

    /**
     * @apiVersion 1.3.0
     * @api {post} /api/replace replace
     * @apiName Replace
     * @apiGroup Objekte
     * @apiHeader {String} X-Token Acces-Token
     * @apiHeader {String} Content-Type=application/json
     *
     * @apiDescription Datumsfelder sollten im ISO 8601-Format übertragen werden.
     *
     * @apiParam {String} entity zu aktualisierende oder einzufügende Entity
     * @apiParam {Integer} id Objekt-ID (wenn vorhanden, wird das Objekt aktualisiert, ansonten neu angelegt)
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
     * @apiError 500 Ein Objekt mit einem gleichen UNIQUE-INDEX ist bereits vorhanden
     */
    public function replaceAction(Request $request, Application $app)
    {
        $entityName          = $request->get('entity');
        $id                  = $request->get('id');
        $data                = $request->get('data');

        $schema              = $this->getSchema();

        $entityPath = 'Custom\Entity\\'.ucfirst($entityName);
        if(substr($entityName, 0, 3) == "PIM"){
            $entityPath = 'Areanet\PIM\Entity\\'.substr($entityName, 4);
        }

        $object = $this->em->getRepository($entityPath)->find($id);
        if(!$object){
            $subRequest = Request::create('/api/insert', 'POST', $request->attributes->all(), $request->cookies->all(), $request->files->all(), $request->server->all(), $request->getContent());

            $allData = $request->request->all();
            $allData["data"]["id"] = $id;
            $request->request->replace($allData);
            $request->attributes->set("data", $data);

            $subRequest->request = $request->request;
            $subRequest->query = $request->query;
            return $this->app->handle($subRequest, HttpKernelInterface::SUB_REQUEST);

        }

        $subRequest = Request::create('/api/update', 'POST', $request->attributes->all(), $request->cookies->all(), $request->files->all(), $request->server->all(), $request->getContent());

        $allData = $request->request->all();
        $allData["data"]["id"] = $id;
        $request->request->replace($allData);
        $request->attributes->set("data", $data);

        $subRequest->request = $request->request;
        $subRequest->query = $request->query;
        return $this->app->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
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

        $event = new \Areanet\PIM\Classes\Event();
        $event->setParam('entity',  $entityName);
        $event->setParam('request', $request);
        $event->setParam('user',    $app['auth.user']);
        $event->setParam('data',    $data);
        $event->setParam('app',     $app);
        $this->app['dispatcher']->dispatch('pim.entity.before.insert', $event);

        try {
            $object = $this->insert($entityName, $data, $app['auth.user']);
        }catch(EntityDuplicateException $e){
            return new JsonResponse(array('message' => $e->getMessage()), 500);
        }catch(Exception $e){
            return new JsonResponse(array('message' => $e->getMessage()), $e->getCode());
        }

        $event = new \Areanet\PIM\Classes\Event();
        $event->setParam('entity',  $entityName);
        $event->setParam('request', $request);
        $event->setParam('user',    $app['auth.user']);
        $event->setParam('object',  $object);
        $event->setParam('app',     $app);
        $this->app['dispatcher']->dispatch('pim.entity.after.insert', $event);

        $schema = $this->getSchema();

        return new JsonResponse(array('message' => 'Object inserted', 'id' => $object->getId(), "data" => $object->toValueObject($this->app['auth.user'], $schema, $entityName)));
    }

    public function insert($entityName, $data, $user)
    {
        $schema              = $this->getSchema();

        $entityPath = 'Custom\Entity\\'.ucfirst($entityName);
        if(substr($entityName, 0, 3) == "PIM"){
            $entityPath = 'Areanet\PIM\Entity\\'.substr($entityName, 4);
        }

        if(!Permission::isWritable($this->app['auth.user'], $entityName)){
            throw new AccessDeniedHttpException("Zugriff auf $entityName verweigert.");
        }

        $object  = new $entityPath();
        
        foreach($data as $property => $value){
            $setter = 'set'.ucfirst($property);
            $getter = 'get'.ucfirst($property);

            if(!isset($schema[ucfirst($entityName)]['properties'][$property])){
                throw new \Exception("Unkown property $property for entity $entityPath", 500);
            }

            $type = $schema[ucfirst($entityName)]['properties'][$property]['type'];
            $typeObject = $this->app['typeManager']->getType($type);
            if(!$typeObject){
                throw new \Exception("Unkown Type $typeObject for $property for entity $entityPath", 500);
            }

            if($schema[ucfirst($entityName)]['properties'][$property]['unique']){
                $objectDuplicated = $this->em->getRepository($entityPath)->findOneBy(array($property => $value, 'isDeleted' => false));
                if($objectDuplicated) throw new \Areanet\PIM\Classes\Exceptions\Entity\EntityDuplicateException("Dieser Eintrag ist bereits vorhanden.");
            }

            if($property == 'id'){
                $metadata = $this->em->getClassMetaData(get_class($object));
                $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);
                if(Config\Adapter::getConfig()->DB_GUID_STRATEGY) $metadata->setIdGenerator(new AssignedGenerator());
            }

            $typeObject->toDatabase($this, $object, $property, $value, $entityName, $schema, $user);

        }

        if($object instanceof Base){
            $object->setUserCreated($user);
            $object->setUser($user);
        }

        try {

            $this->em->persist($object);


            if($schema[ucfirst($entityName)]['settings']['isSortable']){
                if($schema[ucfirst($entityName)]['settings']['type'] == 'tree') {
                    $parent = $object->getTreeParent();
                    if(!$parent){
                        $query  = $this->em->createQuery("UPDATE $entityPath e SET e.sorting = e.sorting + 1 WHERE e.isDeleted = false AND e.treeParent IS NULL");
                    }else {
                        $query = $this->em->createQuery("UPDATE $entityPath e SET e.sorting = e.sorting + 1 WHERE e.isDeleted = false AND e.treeParent = '".$parent->getId()."'");
                    }
                }elseif($schema[ucfirst($entityName)]['settings']['sortRestrictTo']) {
                    $restrictToProperty = $schema[ucfirst($entityName)]['settings']['sortRestrictTo'];
                    $getter             = 'get'.ucfirst($restrictToProperty);
                    $restrictToObject   = $object->$getter();
                    if(!$restrictToObject){
                        $query  = $this->em->createQuery("UPDATE $entityPath e SET e.sorting = e.sorting + 1 WHERE e.isDeleted = false AND e.$restrictToProperty IS NULL");
                    }else{
                        $query = $this->em->createQuery("UPDATE $entityPath e SET e.sorting = e.sorting + 1 WHERE e.isDeleted = false AND e.$restrictToProperty = '".$restrictToObject->getId()."'");
                    }
                }else{
                    $query = $this->em->createQuery("UPDATE $entityPath e SET e.sorting = e.sorting + 1 WHERE e.isDeleted = false");
                }

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
                throw new \Areanet\PIM\Classes\Exceptions\Entity\EntityDuplicateException("Ein Benutzer mit diesem Benutzername ist bereits vorhanden.");
            }
            $uniqueObjectLoaded = false;

            foreach($schema[$entityName]['properties'] as $property => $propertySettings){

                if($propertySettings['unique']){
                    $object = $this->em->getRepository($entityPath)->findOneBy(array($property => $data[$property]));
                    if(!$object){
                        throw new \Exception("Unbekannter Fehler", 501);
                    }
                    $uniqueObjectLoaded = true;
                    break;
                }
            }

            if(!$uniqueObjectLoaded) throw new \Exception("Unbekannter Fehler", 500);
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

        $event = new \Areanet\PIM\Classes\Event();
        $event->setParam('entity',  $entityName);
        $event->setParam('request', $request);
        $event->setParam('id',      $id);
        $event->setParam('user',    $app['auth.user']);
        $event->setParam('data',    $data);
        $event->setParam('app',     $app);
        $this->app['dispatcher']->dispatch('pim.entity.before.udpdate', $event);

        try{
            $this->update($entityName, $id, $data, $disableModifiedTime, $app['auth.user']);
        }catch(EntityDuplicateException $e){
            return new JsonResponse(array('message' => $e->getMessage()), 500);
        }catch(EntityNotFoundException $e){
            return new JsonResponse(array('message' => "Not found"), 404);
        }

        $event = new \Areanet\PIM\Classes\Event();
        $event->setParam('entity',  $entityName);
        $event->setParam('request', $request);
        $event->setParam('id',      $id);
        $event->setParam('user',    $app['auth.user']);
        $event->setParam('data',    $data);
        $event->setParam('app',     $app);
        $this->app['dispatcher']->dispatch('pim.entity.after.udpdate', $event);

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
    public function update($entityName, $id, $data, $disableModifiedTime, $user = null)
    {
        $schema              = $this->getSchema();

        $entityPath = 'Custom\Entity\\'.ucfirst($entityName);
        if(substr($entityName, 0, 3) == "PIM"){
            $entityPath = 'Areanet\PIM\Entity\\'.substr($entityName, 4);
        }

        if(!($permission = Permission::isWritable($this->app['auth.user'], $entityName))){
            throw new AccessDeniedHttpException("Zugriff auf $entityName verweigert.");
        }

        $object = $this->em->getRepository($entityPath)->find($id);
        if(!$object){
            throw new \Areanet\PIM\Classes\Exceptions\Entity\EntityNotFoundException();

        }

        if($permission == \Areanet\PIM\Entity\Permission::OWN && $object->getUserCreated() != $this->app['auth.user']){
            throw new AccessDeniedHttpException("Zugriff auf $entityName::$id verweigert.");
        }


        foreach($data as $property => $value){
            if($property == 'modified' || $property == 'created') continue;
            
            $setter = 'set'.ucfirst($property);
            $getter = 'get'.ucfirst($property);

            if(!isset($schema[ucfirst($entityName)]['properties'][$property])){
                throw new \Exception("Unkown property $property for entity $entityPath", 500);
            }

            $type = $schema[ucfirst($entityName)]['properties'][$property]['type'];
            $typeObject =  $this->app['typeManager']->getType($type);
            if(!$typeObject){
                throw new \Exception("Unkown Type $typeObject for $property for entity $entityPath", 500);
            }


            $typeObject->toDatabase($this, $object, $property, $value, $entityName, $schema, $user);

        }
        $object->setModified(new \DateTime());
        $object->setUser($user);

        try{
            if($disableModifiedTime){
                $object->doDisableModifiedTime(true);
            }

            $this->em->persist($object);
            $this->em->flush();

        }catch(UniqueConstraintViolationException $e){
            if($entityPath == 'Areanet\PIM\Entity\User'){
                throw new \Areanet\PIM\Classes\Exceptions\Entity\EntityDuplicateException("Ein Benutzer mit diesem Benutzername ist bereits vorhanden.");
            }elseif($entityPath == 'Areanet\PIM\Entity\File') {
                $existingFile = $this->em->getRepository('Areanet\PIM\Entity\File')->findOneBy(array('name' => $object->getName(), 'folder' => $object->getFolder()->getId(), 'isDeleted' => false));

                throw new FileExistsException("Die Datei ist in diesem Ordner bereits vorhanden. Wollen Sie die bestehende Datei überschreiben?", $existingFile->getId());
            }else{
                throw new \Areanet\PIM\Classes\Exceptions\Entity\EntityDuplicateException("Ein gleicher Eintrag ist bereits vorhanden.");
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

        $entityFolder = __DIR__.'/../../../../custom/Entity/';
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

            if(!($permission = Permission::isReadable($this->app['auth.user'], $entityShortcut))){
                continue;
            }

            $qb = $this->em->createQueryBuilder();

            $qb->select($entityShortcut)
               ->from($entityName, $entityShortcut);

            if($permission == \Areanet\PIM\Entity\Permission::OWN){
                $qb->where("$entityShortcut.userCreated = :userCreated");
                $qb->setParameter('userCreated', $this->app['auth.user']);
            }

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

                $objectData = $object->toValueObject($this->app['auth.user'], $schema, $entityShortcut, $flatten);

                if(isset($schema[$entityShortcut])) {

                    foreach ($schema[$entityShortcut]['properties'] as $key => $config) {
                        if($flatten){
                            if (isset($config['type']) && $config['type'] == 'multifile') {
                                $getterName = 'get' . ucfirst($key);
                                $multiFiles = $object->$getterName();
                                $multiData = array();
                                foreach ($multiFiles as $multiFile) {
                                    $multiData[] =  array("id" => $multiFile->getid());
                                }
                                $objectData->$key = $multiData;
                            }

                            if (isset($config['type']) && $config['type'] == 'multijoin') {
                                $getterName = 'get' . ucfirst($key);
                                $multiFiles = $object->$getterName();
                                $multiData = array();
                                foreach ($multiFiles as $multiFile) {
                                    $multiData[] =  array("id" => $multiFile->getid());
                                }
                                $objectData->$key = $multiData;
                            }
                        }else {
                            if (isset($config['type']) && $config['type'] == 'multifile') {
                                $getterName = 'get' . ucfirst($key);
                                $multiFiles = $object->$getterName();
                                $multiData = array();
                                foreach ($multiFiles as $multiFile) {
                                    if (!$multiFile->getIsDeleted()) $multiData[] = $multiFile->toValueObject($this->app['auth.user'], $schema, $entityShortcut);
                                }
                                $objectData->$key = $multiData;
                            }

                            if (isset($config['type']) && $config['type'] == 'multijoin') {

                                $getterName = 'get' . ucfirst($key);
                                $multiFiles = $object->$getterName();
                                $multiData = array();

                                foreach ($multiFiles as $multiFile) {
                                    $multiData[] = $multiFile->toValueObject($this->app['auth.user'], $schema, $entityShortcut);
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
                            $objectData->filedata->$size = $base64;
                        }
                    }
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

        return new JsonResponse(array('message' => 'configAction', 'frontend' => $frontend, 'devmode' => Config\Adapter::getConfig()->APP_DEBUG, 'version' => APP_VERSION.'/'.CUSTOM_VERSION));
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
            'customLogo' => Config\Adapter::getConfig()->FRONTEND_CUSTOM_LOGO,
            'formImageSquarePreview' => Config\Adapter::getConfig()->FRONTEND_FORM_IMAGE_SQUARE_PREVIEW,
            'title'  => Config\Adapter::getConfig()->FRONTEND_TITLE,
            'welcome'  => Config\Adapter::getConfig()->FRONTEND_WELCOME,
        );

        $uiblocks = $this->app['uiManager']->getBlocks();

        $schema         = $this->getSchema();
        $permissions    = $this->getPermissions();

        return new JsonResponse(array('message' => 'schemaAction', 'frontend' => $frontend, 'uiblocks' => $uiblocks, 'devmode' => Config\Adapter::getConfig()->APP_DEBUG, 'version' => APP_VERSION.'/'.CUSTOM_VERSION, 'data' => $schema, 'permissions' => $permissions));
    }

    protected function getPermissions()
    {
        $schema = $this->getSchema();

        $permissions = array();
        foreach($schema as $entityName => $config){

            $permissions[$entityName] = array(
                'readable'  => Permission::isReadable($this->app['auth.user'], $entityName),
                'writable'  => Permission::isWritable($this->app['auth.user'], $entityName),
                'deletable' => Permission::isDeletable($this->app['auth.user'], $entityName),
                'extended'  => Permission::getExtended($this->app['auth.user'], $entityName)
            );
        }

        return $permissions;
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