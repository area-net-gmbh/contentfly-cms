<?php
namespace Areanet\PIM\Controller;

use Areanet\PIM\Classes\Annotations\ManyToMany;
use Areanet\PIM\Classes\Annotations\MatrixChooser;
use Areanet\PIM\Classes\Api;
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
use Doctrine\DBAL\ConnectionException;
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
     * @apiParam {String/Integer} id = null ID des Objektes
     * @apiParam {Object} where = null Bedingung, mehrere Felder werden mit AND verknüpft: <code>{'title': 'test', 'desc': 'foo',...}</code>
     * @apiParamExample {json} Request-Beispiel über ID:
     *     {
     *      "entity": "News",
     *      "id": 1
     *     }
     * @apiParamExample {json} Request-Beispiel über WHERE:
     *     {
     *      "entity": "Kunden",
     *      "where": {"kundennummer": 200200}
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
        $where      = $request->get('where');

        $api  = new Api($this->app);
        $data = $api->single($entityName, $id, $where);


        return new JsonResponse(array('message' => "singleAction", 'data' => $data));

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
     * @apiParam {Array} [properties] Gibt nur die angebenenen Eigenschaften/Felder zurück, ansonsten werden alle Eigenschaften geladen (Performance!)<code>['feld1', 'feld2', ...]</code>
     * @apiParamExample {json} Request-Beispiel:
     *     {
     *      "entity": "Category",
     *      "properties": ["title"]
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
        $properties   = $request->get('properties');

        if (substr($entityName, 0, 3) == 'PIM') {
            $entityNameToLoad = 'Areanet\PIM\Entity\\' . substr($entityName, 4);
        }elseif(substr($entityName, 0, 7) == 'Areanet'){
            $splitter = explode('\\', $entityName);
            $entityNameToLoad = $entityName;
            $entityName       = 'PIM\\'.$splitter[count($splitter) - 1];
        }else{
            $entityName = ucfirst($request->get('entity'));
            $entityNameToLoad = 'Custom\Entity\\' . ucfirst($entityName);
        }

        return new JsonResponse(array('message' => "treeAction", 'data' => $this->loadTree($entityName, $entityNameToLoad, null, $properties)));
    }

    protected function loadTree($entityName, $entity, $parent, $properties = array()){

        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder->from($entity, $entityName)
            ->where("$entityName.isIntern = false")
            ->orderBy($entityName.'.sorting', 'ASC');

        if($parent){
            $queryBuilder->andWhere("$entityName.treeParent = :treeParent");
            $queryBuilder->setParameter('treeParent', $parent);
        }else{
            $queryBuilder->andWhere("$entityName.treeParent IS NULL");
        }

        if(count($properties) > 0){
            $partialProperties = implode(',', $properties);
            $queryBuilder->select('partial '.$entityName.'.{id,'.$partialProperties.'}');
        }else{
            $queryBuilder->select($entityName);
        }

        $query   = $queryBuilder->getQuery();
        $objects = $query->getResult();

        $array   = array();

        foreach($objects as $object){
            $data = $object->toValueObject($this->app, $entityName, true, $properties);
            $data->treeChilds = $this->loadTree($entityName, $entity, $object, $properties);
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
        $lastModified           = $request->get('lastModified', null);

        if(!empty($lastModified)) {
            try {
                $lastModified = new \Datetime($lastModified);
            } catch (\Exception $e) {

            }
        }

        if (substr($entityName, 0, 3) == 'PIM') {
            $entityNameToLoad = 'Areanet\PIM\Entity\\' . substr($entityName, 4);
        }elseif(substr($entityName, 0, 7) == 'Areanet'){
            $splitter = explode('\\', $entityName);
            $entityNameToLoad = $entityName;
            $entityName       = 'PIM\\'.$splitter[count($splitter) - 1];
        }else{
            $entityName = ucfirst($request->get('entity'));
            $entityNameToLoad = 'Custom\Entity\\' . ucfirst($entityName);
        }


        if(!($permission = Permission::isReadable($this->app['auth.user'], $entityName))){
            throw new AccessDeniedHttpException("Zugriff auf $entityNameToLoad verweigert.");
        }

        $schema     = $this->app['schema'];

        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
            ->select("count(".$entityName.")")
            ->from($entityNameToLoad, $entityName)
            ->andWhere("$entityName.isIntern = false");


        if($permission == \Areanet\PIM\Entity\Permission::OWN){
            $queryBuilder->andWhere("$entityName.userCreated = :userCreated OR FIND_IN_SET(:userCreated, $entityName.users) = 1");
            $queryBuilder->setParameter('userCreated', $this->app['auth.user']);
        }elseif($permission == \Areanet\PIM\Entity\Permission::GROUP){
            $group = $this->app['auth.user']->getGroup();
            if(!$group){
                $queryBuilder->andWhere("$entityName.userCreated = :userCreated");
                $queryBuilder->setParameter('userCreated', $this->app['auth.user']);
            }else{
                $queryBuilder->andWhere("$entityName.userCreated = :userCreated OR FIND_IN_SET(:userGroup, $entityName.groups) = 1");
                $queryBuilder->setParameter('userGroup', $group);
                $queryBuilder->setParameter('userCreated', $this->app['auth.user']);
            }
        }

        if($lastModified){
            $queryBuilder->andWhere($entityName.'.modified >= :lastModified')->setParameter('lastModified', $lastModified);
        }

        if($where){
            $placeholdCounter   = 0;
            $joinedCounter      = 0;

            foreach($where as $field => $value){

                if(!isset($schema[$entityName]['properties'][$field])){

                    continue;
                }

                if($schema[$entityName]['properties'][$field]['type'] == 'multijoin'){
                    if(isset($schema[$entityName]['properties'][$field]['mappedBy'])){
                        if($value == -1) {
                            $mappedBy           = $schema[$entityName]['properties'][$field]['mappedBy'];
                            $queryBuilder->leftJoin("$entityName.$field", "joined$joinedCounter");
                            $queryBuilder->andWhere("joined$joinedCounter.$mappedBy IS NULL");
                        }else{
                            $searchJoinedEntity = $schema[$entityName]['properties'][$field]['accept'];
                            $searchJoinedObject = $this->em->getRepository($searchJoinedEntity)->find($value);
                            $mappedBy           = $schema[$entityName]['properties'][$field]['mappedBy'];

                            $queryBuilder->leftJoin("$entityName.$field", "joined$joinedCounter");
                            $queryBuilder->andWhere("joined$joinedCounter.$mappedBy = :$field");
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
                            $value = $value;
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

        $properties     = $request->get('properties', array());
        $properties     = is_array($properties) ? $properties : array();

        $query       = $queryBuilder->getQuery();
        $totalObjects = $query->getSingleScalarResult();

        if($currentPage*$itemsPerPage > $totalObjects){
            $currentPage = ceil($totalObjects/$itemsPerPage);
        }

        if($currentPage) {
            $queryBuilder
                ->setFirstResult($itemsPerPage * ($currentPage - 1))
                ->setMaxResults($itemsPerPage)
            ;
        }

        if($order !== null){
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
            $objectData = $object->toValueObject($this->app, $entityName,  $flatten, $properties);

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

        $this->delete($entityName, $id, $app);


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
        $schema = $this->app['schema'];

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

        if($permission == \Areanet\PIM\Entity\Permission::OWN && ($object->getUserCreated() != $this->app['auth.user'] && !$object->hasUserId($this->app['auth.user']->getId())) ){
            throw new AccessDeniedHttpException("Zugriff auf $entityName::$id verweigert.");
        }

        if($permission == \Areanet\PIM\Entity\Permission::GROUP){
            if($object->getUserCreated() != $this->app['auth.user']){
                $group = $this->app['auth.user']->getGroup();
                if(!($group && $object->hasGroupId($group->getId()))){
                    throw new AccessDeniedHttpException("Zugriff auf $entityName::$id verweigert.");
                }
            }
        }

        if($entityPath == 'Areanet\PIM\Entity\User'){

            if($object->getAlias() == 'admin'){
                throw new \Exception("Der Hauptadministrator kann nicht gelöscht werden.", 400);
            }

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
            $backend    = Backend::getInstance();

            $path   = $backend->getPath($object);
            foreach (new \DirectoryIterator($path) as $fileInfo) {
                if ($fileInfo->isDot() || !$fileInfo->isFile()) continue;
                unlink($fileInfo->getPathname());
            }
            @rmdir($path);
        }

        /**
         * Log delete actions
         */
        $schema = $this->app['schema'];

        $log = new Log();

        $log->setModelId($object->getId());
        $log->setModelName(ucfirst($entityName));
        $log->setUser($app['auth.user']);
        $log->setMode(Log::DELETED);

        if($schema[ucfirst($entityName)]['settings']['labelProperty']){
            $labelGetter = 'get'.ucfirst($schema[ucfirst($entityName)]['settings']['labelProperty']);
            $label = $object->$labelGetter();
            $log->setModelLabel($label);
        }

        $this->em->persist($log);
        $this->em->flush();

        $this->em->remove($object);
        $this->em->flush();

        if($schema[ucfirst($entityName)]['settings']['isSortable']){
            $oldPos = $object->getSorting();
            if($schema[ucfirst($entityName)]['settings']['type'] == 'tree') {
                if(!$parent){
                    $query  = $this->em->createQuery("UPDATE $entityPath e SET e.sorting = e.sorting - 1 WHERE e.sorting > $oldPos AND e.sorting > 0 AND e.treeParent IS NULL");
                }else{
                    $query  = $this->em->createQuery("UPDATE $entityPath e SET e.sorting = e.sorting - 1 WHERE e.sorting > $oldPos AND e.sorting > 0 AND e.treeParent = '".$parent->getId()."'");
                }

            }else{
                $query = $this->em->createQuery("UPDATE $entityPath e SET e.sorting = e.sorting - 1 WHERE e.sorting > $oldPos AND e.sorting > 0");
            }
            $query->execute();
        }

        $this->em->flush();

        return $object;
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

        $data = $event->getParam('data');

        try {
            $object = $this->insert($entityName, $data, $app, $app['auth.user']);
        }catch(\Areanet\PIM\Classes\Exceptions\Entity\EntityDuplicateException $e){
            return new JsonResponse(array('message' => $e->getMessage()), 500);
        }catch(\Exception $e){
            return new JsonResponse(array('message' => $e->getMessage()), 500);
        }

        $event = new \Areanet\PIM\Classes\Event();
        $event->setParam('entity',  $entityName);
        $event->setParam('request', $request);
        $event->setParam('user',    $app['auth.user']);
        $event->setParam('object',  $object);
        $event->setParam('app',     $app);
        $this->app['dispatcher']->dispatch('pim.entity.after.insert', $event);

        return new JsonResponse(array('message' => 'Object inserted', 'id' => $object->getId(), "data" => $object->toValueObject($this->app, $entityName)));
    }

    public function insert($entityName, $data, $app, $user)
    {
        $schema              = $this->app['schema'];

        $entityPath = 'Custom\Entity\\'.ucfirst($entityName);
        if(substr($entityName, 0, 3) == "PIM"){
            $entityPath = 'Areanet\PIM\Entity\\'.substr($entityName, 4);
        }

        if(!Permission::isWritable($this->app['auth.user'], $entityName)){
            throw new AccessDeniedHttpException("Zugriff auf $entityName verweigert.");
        }

        $object  = new $entityPath();

        foreach($data as $property => $value){
            if(!isset($schema[ucfirst($entityName)]['properties'][$property])){
                throw new \Exception("Unkown property $property for entity $entityPath", 500);
            }

            $type = $schema[ucfirst($entityName)]['properties'][$property]['type'];
            $typeObject = $this->app['typeManager']->getType($type);
            if(!$typeObject){
                throw new \Exception("Unkown Type $typeObject for $property for entity $entityPath", 500);
            }

            if($schema[ucfirst($entityName)]['properties'][$property]['unique']){
                $objectDuplicated = $this->em->getRepository($entityPath)->findOneBy(array($property => $value));
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
                        $query  = $this->em->createQuery("UPDATE $entityPath e SET e.sorting = e.sorting + 1 WHERE e.treeParent IS NULL");
                    }else {
                        $query = $this->em->createQuery("UPDATE $entityPath e SET e.sorting = e.sorting + 1 WHERE e.treeParent = '".$parent->getId()."'");
                    }
                }elseif($schema[ucfirst($entityName)]['settings']['sortRestrictTo']) {
                    $restrictToProperty = $schema[ucfirst($entityName)]['settings']['sortRestrictTo'];
                    $getter             = 'get'.ucfirst($restrictToProperty);
                    $restrictToObject   = $object->$getter();
                    if(!$restrictToObject){
                        $query  = $this->em->createQuery("UPDATE $entityPath e SET e.sorting = e.sorting + 1 WHERE e.$restrictToProperty IS NULL");
                    }else{
                        $query = $this->em->createQuery("UPDATE $entityPath e SET e.sorting = e.sorting + 1 WHERE e.$restrictToProperty = '".$restrictToObject->getId()."'");
                    }
                }else{
                    $query = $this->em->createQuery("UPDATE $entityPath e SET e.sorting = e.sorting + 1");
                }

                $query->execute();
            }

            $this->em->flush();

            $isPush = $schema[$entityName]['settings']['isPush'];
            if($isPush){
                $pushTitle  = $schema[$entityName]['settings']['pushTitle'];
                $pushText   = $schema[$entityName]['settings']['pushText'];


                $event = new \Areanet\PIM\Classes\Event();
                $event->setParam('entity',          $entityName);
                $event->setParam('data',            $data);
                $event->setParam('user',            $app['auth.user']);
                $event->setParam('additionalData',  array());
                $event->setParam('pushTitle',       $pushTitle);
                $event->setParam('pushText',        $pushText);
                $event->setParam('app',             $app);
                $this->app['dispatcher']->dispatch('pim.push.before.send', $event);

                $additionalData = $event->getParam('additionalData');
                $pushTitle      = $event->getParam('pushTitle');
                $pushText       = $event->getParam('pushText');

                $push = new Push($this->em, $object);
                $push->send($pushTitle, $pushText, $additionalData);
            }

            /**
             * Log insert actions
             */
            $log = new Log();

            $log->setModelId($object->getId());
            $log->setModelName($entityName);
            $log->setUser($user);
            $log->setMode(Log::INSERTED);

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
     * @apiParam {Integer} id Zu aktualisierende Objekt-ID
     * @apiParam {String=null} pass Passwort des eingeloggten Benutzers. Muss übergeben werden, wenn die pass-Property für Entität PIM\User unter data gesetzt wird.
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
        $currentUserPass     = $request->get('pass');
        $disableModifiedTime = $request->get('disableModifiedTime');

        $event = new \Areanet\PIM\Classes\Event();
        $event->setParam('entity',  $entityName);
        $event->setParam('request', $request);
        $event->setParam('id',      $id);
        $event->setParam('user',    $app['auth.user']);
        $event->setParam('data',    $data);
        $event->setParam('app',     $app);
        $this->app['dispatcher']->dispatch('pim.entity.before.udpdate', $event);

        $data = $event->getParam('data');

        try{
            $this->update($entityName, $id, $data, $disableModifiedTime, $app, $app['auth.user'], $currentUserPass);
        }catch(\Areanet\PIM\Classes\Exceptions\Entity\EntityDuplicateException $e){
            return new JsonResponse(array('message' => $e->getMessage()), 500);
        }catch(\Areanet\PIM\Classes\Exceptions\Entity\EntityNotFoundException $e){
            return new JsonResponse(array('message' => "Not found"), 404);
        }catch(\Exception $e){
            return new JsonResponse(array('message' => $e->getMessage()), 500);
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
                $this->update($object['entity'], $object['id'], $object['data'], $disableModifiedTime, $app, $app['auth.user']);
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
    public function update($entityName, $id, $data, $disableModifiedTime, $app, $user = null, $currentUserPass = null)
    {
        $schema              = $this->app['schema'];

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

        if($permission == \Areanet\PIM\Entity\Permission::OWN && ($object->getUserCreated() != $this->app['auth.user'] && !$object->hasUserId($this->app['auth.user']->getId()) && $object != $this->app['auth.user'])){
            throw new AccessDeniedHttpException("Zugriff auf $entityName::$id verweigert.");
        }

        if($permission == \Areanet\PIM\Entity\Permission::GROUP){
            if($object->getUserCreated() != $this->app['auth.user']){
                $group = $this->app['auth.user']->getGroup();
                if(!($group && $object->hasGroupId($group->getId()))){
                    throw new AccessDeniedHttpException("Zugriff auf $entityName::$id verweigert.");
                }
            }
        }

        if($object instanceof User && isset($data['pass']) && !$this->app['auth.user']->getIsAdmin()){
            if(!$this->app['auth.user']->isPass($currentUserPass)){
                throw new \Exception('Passwort des aktuellen Benutzers wurde nicht korrekt übergeben.');
            }
        }


        foreach($data as $property => $value){
            if($property == 'modified' || $property == 'created') continue;


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
                $existingFile = $this->em->getRepository('Areanet\PIM\Entity\File')->findOneBy(array('name' => $object->getName(), 'folder' => $object->getFolder()->getId()));

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
        $log->setMode(Log::UPDATED);

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
        $flatten                = $request->get('flatten', false);

        $lastModified = null;
        if(!empty($timestamp)) {
            try {
                $lastModified = new \Datetime($timestamp);
            } catch (\Exception $e) {

            }
        }

        $entities   = array('Areanet\PIM\Entity\File');

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

            $qb->where("1 = 1");

            if($permission == \Areanet\PIM\Entity\Permission::OWN){
                $qb->andWhere("$entityShortcut.userCreated = :userCreated OR FIND_IN_SET(:userCreated, $entityShortcut.users) = 1");
                $qb->setParameter('userCreated', $this->app['auth.user']);
            }elseif($permission == \Areanet\PIM\Entity\Permission::GROUP){
                $group = $this->app['auth.user']->getGroup();
                if(!$group){
                    $qb->andWhere("$entityShortcut.userCreated = :userCreated");
                    $qb->setParameter('userCreated', $this->app['auth.user']);
                }else{
                    $qb->andWhere("$entityShortcut.userCreated = :userCreated OR FIND_IN_SET(:userGroup, $entityShortcut.groups) = 1");
                    $qb->setParameter('userGroup', $group);
                    $qb->setParameter('userCreated', $this->app['auth.user']);
                }
            }

            if($lastModified) {
                $qb->andWhere($entityShortcut . '.modified >= :lastModified');
                $qb->setParameter('lastModified', $lastModified);
            }

            $query = $qb->getQuery();
            $objects = $query->getResult();


            $array = array();
            foreach($objects as $object){

                $objectData = $object->toValueObject($this->app, $entityShortcut, $flatten);

                if($object instanceof File && $filedata !== null){

                    $backendFS = new FileSystem();
                    foreach($filedata as $size){
                        $sizePrefix = $size == 'org' ? '' : $size.'-';
                        $path       = $backendFS->getPath($object);
                        $filePath   = $path.'/'.$sizePrefix.$object->getName();

                        if(file_exists($filePath)){
                            if(!isset($objectData->filedata)) $objectData->filedata = new \stdClass();

                            $data   = file_get_contents($filePath);
                            $base64 = base64_encode($data);
                            $objectData->filedata->$size = $base64;
                        }
                    }
                }

                $array[] = $objectData;
            }

            //GET DELETED
            $qb = $this->em->createQueryBuilder();

            $qb->select('log')
                ->from('Areanet\PIM\Entity\\Log', 'log')
                ->where('log.modelName = :modelName')
                ->andWhere("log.mode = 'DEL' OR log.mode = 'Gelöscht'")
                ->setParameter('modelName', $entityShortcut);

            if($lastModified) {
                $qb->andWhere('log.created >= :lastModified');
                $qb->setParameter('lastModified', $lastModified);
            }

            $query = $qb->getQuery();
            $objects = $query->getResult();

            foreach($objects as $object){
                $array[] = array(
                    'id' => $object->getModelId(),
                    'isDeleted' => true
                );
            }

            if(!count($array)){
                continue;
            }

            $all[$entityShortcut] = $array;
        }

        $currentDate = new \Datetime();

        return new JsonResponse(array('message' => 'allAction',  'lastModified' => $currentDate->format('Y-m-d H:i:s'),  'data' => $all), count($all) ? 200 : 204);
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

        $uiblocks = $this->app['uiManager']->getBlocks();

        return new JsonResponse(array('message' => 'configAction', 'uiblocks' => $uiblocks, 'frontend' => $frontend, 'devmode' => Config\Adapter::getConfig()->APP_DEBUG, 'version' => APP_VERSION.'/'.CUSTOM_VERSION));
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
            'customNavigation' => array(
                'enabled' => Config\Adapter::getConfig()->FRONTEND_CUSTOM_NAVIGATION
            ),
            'login_redirect' => Config\Adapter::getConfig()->FRONTEND_LOGIN_REDIRECT
        );

        $uiblocks = $this->app['uiManager']->getBlocks();

        $schema         = $this->app['schema'];
        $permissions    = $this->getPermissions();

        if(Config\Adapter::getConfig()->FRONTEND_CUSTOM_NAVIGATION){
            $frontend['customNavigation']['items'] = array();

            $queryBuilder = $this->em->createQueryBuilder();
            $queryBuilder
                ->select("navItem")
                ->from("Areanet\PIM\Entity\NavItem", "navItem")
                ->join("navItem.nav", "nav")
                ->where('navItem.nav IS NOT NULL')
                ->orderBy('nav.sorting')
                ->orderBy('navItem.sorting');

            $items = $queryBuilder->getQuery()->getResult();
            foreach($items as $item){

                $entityUriName = str_replace('Areanet\PIM\Entity', 'PIM/', $item->getEntity());
                $entityUriName = str_replace('Custom\Entity', '', $entityUriName);

                if(empty($frontend['customNavigation']['items'][$item->getNav()->getId()])){
                    $frontend['customNavigation']['items'][$item->getNav()->getId()] = array(
                        'title' => $item->getNav()->getTitle(),
                        'icon' => $item->getNav()->getIcon() ? $item->getNav()->getIcon() : 'glyphicon glyphicon-th-large',
                        'items' => array()
                    );
                }

                $frontend['customNavigation']['items'][$item->getNav()->getId()]['items'][] = array(
                    'entity' => $item->getEntity(),
                    'title'  => $item->getTitle() ? $item->getTitle() : $schema[$item->getEntity()]['settings']['label'],
                    'uri'    => $item->getUri() ? $item->getUri() : '#/list/'.$entityUriName,
                );
            }
        }

        return new JsonResponse(array('message' => 'schemaAction', 'frontend' => $frontend, 'uiblocks' => $uiblocks, 'devmode' => Config\Adapter::getConfig()->APP_DEBUG, 'version' => APP_VERSION.'/'.CUSTOM_VERSION, 'data' => $schema, 'permissions' => $permissions));
    }

    protected function getPermissions()
    {
        $schema = $this->app['schema'];

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