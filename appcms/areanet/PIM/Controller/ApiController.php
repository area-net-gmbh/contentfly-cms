<?php
namespace Areanet\PIM\Controller;

use Areanet\PIM\Classes\Annotations\ManyToMany;
use Areanet\PIM\Classes\Annotations\MatrixChooser;
use Areanet\PIM\Classes\Api;
use \Areanet\PIM\Classes\Config;
use Areanet\PIM\Classes\Controller\BaseController;
use Areanet\PIM\Classes\Exceptions\Entity\EntityDuplicateException;
use Areanet\PIM\Classes\Exceptions\Entity\EntityNotFoundException;
use Areanet\PIM\Classes\Exceptions\File\FileExistsException;
use Areanet\PIM\Classes\File\Backend;
use Areanet\PIM\Classes\File\Backend\FileSystem;
use Areanet\PIM\Classes\File\Processing;
use Areanet\PIM\Classes\File\Processing\Standard;
use Areanet\PIM\Classes\Helper;
use Areanet\PIM\Classes\Permission;
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
     * @apiVersion 1.4.2
     * @api {post} /api/all all
     * @apiName All
     * @apiGroup Objekte
     * @apiDeprecated
     * @apiHeader {String} APPMS-TOKEN Access-Token
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

        $api = new Api($this->app, $request);
        $all = $api->getAll($lastModified, $flatten, $filedata);

        $currentDate = new \Datetime();

        return $this->renderResponse(array('lastModified' => $currentDate->format('Y-m-d H:i:s'),  'data' => $all), count($all) ? 200 : 204);
    }

    /**
     * @apiVersion 1.4.2
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
     *       "version": "1.4.0"
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

        return $this->renderResponse(array('uiblocks' => $uiblocks, 'frontend' => $frontend, 'devmode' => Config\Adapter::getConfig()->APP_DEBUG, 'version' => APP_VERSION.'/'.CUSTOM_VERSION));
    }

    /**
     * @apiVersion 1.4.2
     * @api {post} /api/count count
     * @apiName Count
     * @apiGroup Objekte
     * @apiHeader {String} APPMS-TOKEN Access-Token
     * @apiHeader {String} Content-Type=application/json
     *
     * @apiParam {String/Array} [lastModified="yyyymmdd hh:mm:ii"] Es werden nur die Objekte zurückgegeben, die seit lastModified geändert wurden.
     * @apiParamExample {json} Request-Beispiel mit globalem Zeitstempel:
     *     {
     *      "lastModified": "2016-02-20 15:30:22"
     *      }
     * @apiParamExample {json} Request-Beispiel mit Zeitstempel pro Entität:
     *     {
     *      "lastModified": {
     *          "Entity1" : "2016-02-20 15:30:22",
     *          "Entity2" : "2016-02-20 15:30:22"
     *      }
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "dataCount": 12345,
     *       "filesCount": 234
     *       "filesSize": 1234355
     *     }
     */
    public function countAction(Request $request, Application $app){
        $api            = new Api($this->app, $request);
        $lastModified   = $request->get("lastModified");
        $data           = $api->getCount($lastModified);

        $currentDate    = new \Datetime();

        return $this->renderResponse(array('ts' => $currentDate->format('Y-m-d H:i:s'), 'data' => $data));
    }

    /**
     * @apiVersion 1.4.2
     * @api {post} /api/delete delete
     * @apiName Delete
     * @apiDescription API-Endpoint zum Löschen eines Objektes einer Entität.
     * @apiGroup Objekte
     * @apiHeader {String} APPMS-TOKEN Access-Token
     * @apiHeader {String} Content-Type=application/json
     *
     * @apiParam {String} entity Zu löschende Entity
     * @apiParam {Integer} id Zu löschende Objekt-ID
     * @apiParam {String} lang = null Sprachcode bei sprachabhängigen Entitäten (I18N)
     * @apiParamExample {json} Request-Beispiel:
     *     {
     *      "entity": "News",
     *      "id": 12
     *      }
     */
    public function deleteAction(Request $request, Application $app)
    {

        $helper              = new Helper();
        $entityShortName     = $helper->getShortEntityName($request->get('entity'));
        $id                  = $request->get('id');
        $lang                = $request->get('lang', null);

        $event = new \Areanet\PIM\Classes\Event();
        $event->setParam('entity',  $entityShortName);
        $event->setParam('request', $request);
        $event->setParam('id',      $id);
        $event->setParam('lang',    $lang);
        $event->setParam('user',    $app['auth.user']);
        $event->setParam('app',     $app);

        $this->app['dispatcher']->dispatch('pim.entity.before.delete', $event);

        $api = new Api($this->app, $request);
        $api->doDelete($entityShortName, $id, $lang);

        $event = new \Areanet\PIM\Classes\Event();
        $event->setParam('entity',  $entityShortName);
        $event->setParam('request', $request);
        $event->setParam('id',      $id);
        $event->setParam('lang',    $lang);
        $event->setParam('user',    $app['auth.user']);
        $event->setParam('app',     $app);
        $this->app['dispatcher']->dispatch('pim.entity.after.delete', $event);

        $currentDate = new \Datetime();

        return $this->renderResponse(array('ts' => $currentDate->format('Y-m-d H:i:s'), 'id' => $id));
    }

    /**
     * @apiVersion 1.4.2
     * @api {post} /api/deleted deleted
     * @apiName Deleted
     * @apiGroup Objekte
     * @apiHeader {String} APPMS-TOKEN Access-Token
     * @apiHeader {String} Content-Type=application/json
     *
     * @apiParam {String/Array} [lastModified="yyyymmdd hh:mm:ii"] Es werden nur die Objekte zurückgegeben, die seit lastModified gelöscht wurden.
     * @apiParamExample {json} Request-Beispiel mit globalem Zeitstempel:
     *     {
     *      "lastModified": "2016-02-20 15:30:22"
     *      }
     * @apiParamExample {json} Request-Beispiel mit Zeitstempel pro Entität:
     *     {
     *      "lastModified": {
     *          "Entity1" : "2016-02-20 15:30:22",
     *          "Entity2" : "2016-02-20 15:30:22"
     *      }
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     [
     *       {"entity_name": "Entity1", "id": "...."}
     *     ]
     */
    public function deletedAction(Request $request, Application $app){
        $api            = new Api($this->app, $request);
        $lastModified   = $request->get("lastModified");
        $data           = $api->getDeleted($lastModified);
        $currentDate    = new \Datetime();
        return $this->renderResponse(array('ts' => $currentDate->format('Y-m-d H:i:s'), 'data' => $data));
    }


    /**
     * @apiVersion 1.4.2
     * @api {post} /api/insert insert
     * @apiName Insert
     * @apiDescription API-Endpoint zum Hinzufügen eines neues Objektes einer Entität.
     *
     * Datumsfelder sollten im ISO 8601-Format übertragen werden.
     * @apiGroup Objekte
     * @apiHeader {String} APPMS-TOKEN Access-Token
     * @apiHeader {String} Content-Type=application/json
     *
     * @apiParam {String} entity Einzutragende Entity
     * @apiParam {Object} data Daten des Objekts, abhhängig von der Entity
     * @apiParam {String} lang = null Sprachcode bei sprachabhängigen Entitäten (I18N)
     * @apiParamExample {json} Request-Beispiel:
     *     {
     *      "entity": "News",
     *      "data": {
     *          "title": "Eine neue News",
     *          "subtitle: "Untertitel der neuen News",
     *          "date": "2016-02-18 15:30:00",
     *          // Join 1:n
     *          "category": {
     *              "id": 1
     *          },
     *          // Datum im Format yyyy-mm-dd hh:ii:ss
     *          "active_from": "2016-02-18 15:30:00",
     *          // Multijoin n:m
     *          "cross_selling": [
     *              {
     *                  "id": 2
     *              },
     *              {
     *                  "id": 6
     *              }
     *           ]
     *      }
     * @apiError 500 Ein Objekt mit einem gleichen UNIQUE-INDEX ist bereits vorhanden
     * @apiError 501 Unbekannter Serverfehler
     */
    public function insertAction(Request $request, Application $app)
    {

        $helper              = new Helper();
        $entityShortName     = $helper->getShortEntityName($request->get('entity'));

        $data                = $request->get('data');
        $lang                = $request->get('lang');

        $event = new \Areanet\PIM\Classes\Event();
        $event->setParam('entity',  $entityShortName);
        $event->setParam('request', $request);
        $event->setParam('user',    $app['auth.user']);
        $event->setParam('data',    $data);
        $event->setParam('lang',    $lang);
        $event->setParam('app',     $app);
        $this->app['dispatcher']->dispatch('pim.entity.before.insert', $event);

        $data = $event->getParam('data');

        $api = new Api($this->app, $request);
        $object = $api->doInsert($entityShortName, $data, $lang);


        $event = new \Areanet\PIM\Classes\Event();
        $event->setParam('entity',  $entityShortName);
        $event->setParam('request', $request);
        $event->setParam('user',    $app['auth.user']);
        $event->setParam('object',  $object);
        $event->setParam('lang',    $lang);
        $event->setParam('app',     $app);
        $this->app['dispatcher']->dispatch('pim.entity.after.insert', $event);

        $currentDate    = new \Datetime();

        return $this->renderResponse(array('ts' => $currentDate->format('Y-m-d H:i:s'), 'id' => $object->getId(), "data" => $object->toValueObject($this->app, $entityShortName, true)));
    }

    /**
     * @apiVersion 1.4.2
     * @api {post} /api/list list
     * @apiName List
     * @apiDescription API-Endpoint zum Abruf von Objekten einer Entität.
     *
     * Die Rückgabe der Daten erfolgt im JSON-Format auf Basis des Doctrine ORM. Joins (1:n) und Multijoins (n:m) werden automatisch umgewandelt und als Unterobjekte zurückgegeben. Das kann bei vielen Objekten mit Joins/Multijoins zu Performance-Problemen führen. Abhilfe bietet der Parameter flatten.
     * @apiGroup Objekte
     * @apiHeader {String} APPMS-TOKEN Access-Token
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
     * @apiParam {String} lang = null Sprachcode bei sprachabhängigen Entitäten (I18N)
     * @apiParam {String} untranslatedLang = null Sprachcode bei dem noch nicht übersetzte Datensätze für lang gesetzt sind. Parameter lang muss gesetzt sein. (I18N)
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
     * @apiParamExample {json} Request-Beispiel Zeige alle englischen Datensätze, die noch nicht ins Deutsche übersetzt sind
     *     {
     *      "entity": "News",
     *      "lang": "de",
     *      "untranslatedLang": "en"
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

        $entityName             = $request->get('entity');
        $groupBy                = $request->get('groupBy', false);
        $doCount                = $request->get('count', false);
        $order                  = $request->get('order', null);
        $where                  = $request->get('where', null);
        $currentPage            = $request->get('currentPage');
        $itemsPerPage           = $request->get('itemsPerPage', Config\Adapter::getConfig()->FRONTEND_ITEMS_PER_PAGE);
        $flatten                = $request->get('flatten', false);
        $lastModified           = $request->get('lastModified', null);
        $lang                   = $request->get('lang', null);
        $untranslatedLang       = $request->get('untranslatedLang', null);

        $properties             = $request->get('properties', array());
        $properties             = is_array($properties) ? $properties : array();

        $api        = new Api($this->app, $request);

        if(!($data = $api->getList($entityName, $where, $order, $groupBy, $properties, $lastModified, $flatten, $currentPage, $itemsPerPage, $lang, $untranslatedLang))){
            return new JsonResponse(array('message' => "Not found"), 404);
        }

        if($doCount){
            return $this->renderResponse(array('data' => count($data['objects'])));
        }


        if($currentPage) {
            $data = array('data' => $data['objects'], 'itemsPerPage' => $itemsPerPage, 'totalItems' => $data['totalObjects']);

            if($lastModified){
                $currentDate = new \Datetime();
                $data['lastModified'] = $currentDate->format('Y-m-d H:i:s');
            }
            return $this->renderResponse($data);
        } else {
            $data = array('data' => $data['objects'], 'totalItems' => $data['totalObjects']);

            if($lastModified){
                $currentDate = new \Datetime();
                $data['lastModified'] = $currentDate->format('Y-m-d H:i:s');
            }
            return $this->renderResponse($data);
        }
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
        return $this->renderResponse(array('data' => $data));
    }

    public function multiupdateAction(Request $request, Application $app)
    {
        $objects             = $request->get('objects');
        $disableModifiedTime = $request->get('disableModifiedTime');
        $lang                = $request->get('lang');

        foreach($objects as $object){
            $api = new Api($this->app, $request);
            $updateUniversalLangProps = $api->doUpdate($object['entity'], $object['id'], $object['data'], $disableModifiedTime, null, $lang);

            if($updateUniversalLangProps){
                foreach ($updateUniversalLangProps['i18nObjects'] as $i18nObject) {

                    $api->doUpdate($object['entity'], $i18nObject['id'], $updateUniversalLangProps['i18nProperties'], $disableModifiedTime, null, $i18nObject['lang'], true);
                }
            }
        }

        return $this->renderResponse(array());
    }

    /**
     * @apiVersion 1.4.2
     * @api {post} /api/update update
     * @apiName Update
     * @apiGroup Objekte
     * @apiHeader {String} APPMS-TOKEN Access-Token
     * @apiHeader {String} Content-Type=application/json
     * @apiDescription API-Endpoint zum Hinzufügen eines neues Objektes einer Entität.
     *
     * Datumsfelder sollten im ISO 8601-Format übertragen werden.
     *
     * @apiParam {String} entity zu aktualisierende Entity
     * @apiParam {Integer} id Zu aktualisierende Objekt-ID
     * @apiParam {String} lang = null Sprachcode bei sprachabhängigen Entitäten (I18N)
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
        $lang                = $request->get('lang');
        $data                = $request->get('data');
        $currentUserPass     = $request->get('pass');
        $disableModifiedTime = $request->get('disableModifiedTime');

        $event = new \Areanet\PIM\Classes\Event();
        $event->setParam('entity',  $entityName);
        $event->setParam('request', $request);
        $event->setParam('id',      $id);
        $event->setParam('lang',    $lang);
        $event->setParam('user',    $app['auth.user']);
        $event->setParam('data',    $data);
        $event->setParam('app',     $app);
        $this->app['dispatcher']->dispatch('pim.entity.before.udpdate', $event);
        $this->app['dispatcher']->dispatch('pim.entity.before.update', $event);

        $data = $event->getParam('data');

        $api = new Api($this->app, $request);
        $updateUniversalLangProps = $api->doUpdate($entityName, $id, $data, $disableModifiedTime, $currentUserPass, $lang);

        if($updateUniversalLangProps){
            foreach ($updateUniversalLangProps['i18nObjects'] as $i18nObject) {
                $api->doUpdate($entityName, $i18nObject['id'], $updateUniversalLangProps['i18nProperties'], $disableModifiedTime, $currentUserPass, $i18nObject['lang'], true);
            }
        }

        $event = new \Areanet\PIM\Classes\Event();
        $event->setParam('entity',  $entityName);
        $event->setParam('request', $request);
        $event->setParam('id',      $id);
        $event->setParam('lang',    $lang);
        $event->setParam('user',    $app['auth.user']);
        $event->setParam('data',    $data);
        $event->setParam('app',     $app);
        $this->app['dispatcher']->dispatch('pim.entity.after.udpdate', $event);
        $this->app['dispatcher']->dispatch('pim.entity.after.update', $event);

        $currentDate = new \Datetime();

        return $this->renderResponse(array('ts' => $currentDate->format('Y-m-d H:i:s'), 'id' => $id));

    }

    protected function renderResponse(Array $data, $status = 200){
        $data['version']    = APP_VERSION;
        $data['hash']       = $this->app['schema']['_hash'];
        return new JsonResponse($data, $status);
    }

    /**
     * @apiVersion 1.4.2
     * @api {post} /api/replace replace
     * @apiName Replace
     * @apiGroup Objekte
     * @apiHeader {String} APPMS-TOKEN Access-Token
     * @apiHeader {String} Content-Type=application/json
     *
     * @apiDescription API-Endpoint zum Abruf von Objekten einer Entität. Ist das Objekt vorhanden, wird ein Insert, ansonsten ein Update durchgeführt.
     *
     * Datumsfelder sollten im ISO 8601-Format übertragen werden.
     *
     * @apiParam {String} entity zu aktualisierende oder einzufügende Entity
     * @apiParam {Integer} id Objekt-ID (wenn vorhanden, wird das Objekt aktualisiert, ansonten neu angelegt)
     * @apiParam {String} lang = null Sprachcode bei sprachabhängigen Entitäten (I18N)
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
        $lang                = $request->get('lang');
        $data                = $request->get('data');


        $helper             = new Helper();
        $entityFullName     = $helper->getFullEntityName($entityName);

        $schema = $this->app['schema'];
        $object = null;

        if($schema[$entityName]['settings']['i18n']) {
            $object = $this->em->getRepository($entityFullName)->find(array('id' => $id, 'lang' => $lang));
        }else{
            $object = $this->em->getRepository($entityFullName)->find($id);
        }

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
     * @apiVersion 1.4.2
     * @api {get} /api/schema schema
     * @apiName Schema
     * @apiGroup Settings
     * @apiHeader {String} APPMS-TOKEN Access-Token
     * @apiHeader {String} Content-Type=application/json
     *
     * @apiDescription Gibt das Schema aller Entitäten zurück.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "devmode": false,
     *       "version": "1.4.0"
     *       "data:" {
     *         ...
     *       }
     *     }
     */
    public function schemaAction()
    {
        $api = new Api($this->app);
        $extendedSchema = $api->getExtendedSchema();

        return $this->renderResponse($extendedSchema);

    }

    /**
     * @apiVersion 1.4.2
     * @api {post} /api/single single
     * @apiName Single
     * @apiDescription API-Endpoint zum Abruf eines einzelnen Objektes einer Entität.
     *
     * Die Rückgabe der Daten erfolgt im JSON-Format auf Basis des Doctrine ORM. Joins (1:n) und Multijoins (n:m) werden automatisch umgewandelt und als Unterobjekte zurückgegeben.
     * @apiGroup Objekte
     * @apiHeader {String} APPMS-TOKEN Access-Token
     * @apiHeader {String} Content-Type=application/json
     *
     * @apiParam {String} entity Auszulesende Entity
     * @apiParam {String/Integer} id = null ID des Objektes
     * @apiParam {String} lang = null Sprachcode bei sprachabhängigen Entitäten (I18N)
     * @apiParam {String} compareToLang = null Lädt für eine Übersetzung vernküpfte Objekte aus 'compareToLang' und gibt eine Fehlermeldung aus, wenn Übersetzungen fehlen (I18N)
     * @apiParam {String} compareToMainLang = null Lädt für eine Übersetzung vernküpfte Objekte aus der Hauptsprache und gibt eine Fehlermeldung aus, wenn Übersetzungen fehlen (I18N)
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

        $data               = array();

        $entityName         = $request->get('entity');
        $id                 = $request->get('id');
        $lang               = $request->get('lang');
        $compareToLang      = $request->get('compareToLang');
        $loadJoinedLang     = $request->get('loadJoinedLang');
        $where              = $request->get('where');

        $api  = new Api($this->app);
        $data = $api->getSingle($entityName, $id, $where, $lang, false, $compareToLang, $loadJoinedLang);
        $currentDate    = new \Datetime();

        return $this->renderResponse(array('ts' => $currentDate->format('Y-m-d H:i:s'), 'data' => $data));

    }

    /**
     * @apiVersion 1.4.2
     * @api {post} /api/tree tree
     * @apiName Baumansicht
     * @apiDescription API-Endpoint, zum Abruf einer Baumstruktur.
     *
     * Die Entität muss vom Typ Areanet\PIM\Entity\BaseTree
     * @apiGroup Objekte
     * @apiHeader {String} APPMS-TOKEN Access-Token
     * @apiHeader {String} Content-Type=application/json
     *
     * @apiParam {String} entity Auszulesende Entity
     * @apiParam {Array} [properties] Gibt nur die angebenenen Eigenschaften/Felder zurück, ansonsten werden alle Eigenschaften geladen (Performance!)<code>['feld1', 'feld2', ...]</code>
     * @apiParam {String} lang Sprachvariante
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
        $lang         = $request->get('lang');
        $properties   = $request->get('properties');

        $api            = new Api($this->app);
        $tree           = $api->getTree($entityName, null, $properties, $lang);
        $currentDate    = new \Datetime();

        return $this->renderResponse(array('ts' => $currentDate->format('Y-m-d H:i:s'),'data' => $tree));
    }

    /**
     * @apiVersion 1.5.2
     * @api {post} /api/tree2 tree2
     * @apiName Baumansicht optimiert
     * @apiDescription API-Endpoint, zum Abruf einer optimierten/performanten Baumstruktur.
     *
     * Die Entität muss vom Typ Areanet\PIM\Entity\BaseTree
     * @apiGroup Objekte
     * @apiHeader {String} APPMS-TOKEN Access-Token
     * @apiHeader {String} Content-Type=application/json
     *
     * @apiParam {String} entity Auszulesende Entity
     * @apiParam {String} lang Sprachvariante
     * @apiParamExample {json} Request-Beispiel:
     *     {
     *      "entity": "Category"
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
    public function tree2Action(Request $request)
    {
        $entityName   = $request->get('entity');
        $lang         = $request->get('lang');

        $api            = new Api($this->app);
        $tree           = $api->getTree2($entityName,  $lang);
        $currentDate    = new \Datetime();

        return $this->renderResponse(array('ts' => $currentDate->format('Y-m-d H:i:s'),'data' => $tree));
    }

    /**
     * @apiVersion 1.5.0
     * @api {post} /api/translations translations
     * @apiName Übersetzungen
     * @apiDescription Liefert die Anzahl der offenen Übersetzungen für eine Entität.
     *
     * @apiGroup Objekte
     * @apiHeader {String} APPMS-TOKEN Access-Token
     * @apiHeader {String} Content-Type=application/json
     *
     * @apiParam {String} entity Auszulesende Entity
     * @apiParam {String} lang Sprachcode (z.B. de, en,..), für den noch nicht übersetzte Datensätze analysiert werden sollen.
     * @apiParamExample {json} Request-Beispiel:
     *     {
     *      "entity": "Category",
     *      "lang": "en"
     *     }
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "message": "translationsAction",
     *       "data": [
     *         {
     *           "lang": "de",
     *           "records": "4"
     *         }, ...
     *       ],
     *   }
     */
    public function translationsAction(Request $request)
    {
        $entityName = $request->get('entity');
        $lang       = $request->get('lang');

        $api  = new Api($this->app);
        $lang = $api->getTranslations($entityName, $lang);

        return $this->renderResponse(array('data' => $lang));
    }

    /**
     * @apiVersion 1.4.2
     * @api {post} /api/query query
     * @apiDescription Erweiterter API-Endpoint, über den nahezu beliebige Abfragen auf die Datenbank/Entitäten gestellt werden können. Die Abfragesyntax basiert dabei auf dem DBAL-QueryBuilder (http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/query-builder.html) von Doctrine. Der JSON-Request (siehe Beispiele unten) wird im Contentfly CMS in eine analoge DBAL-Abfrage über den QueryBuilder umgewandelt.
     *
     * Die Rückgabe der Daten erfolgt im JSON-Format. Durch die DBAL-Abfrage erfolgt die Rückgabe direkt auf Datenbankebene und nicht auf Doctrine Entitäten.
     * @apiName Query
     * @apiGroup Objekte
     * @apiHeader {String} APPMS-TOKEN Access-Token
     * @apiHeader {String} Content-Type=application/json
     *
     * @apiParamExample {json} Einfache Abfrage:
     *     {
     *      "select": "*",
     *      "from": "Product"
     *     }
     * @apiParamExample {json} Einfache Abfrage mit Where
     *     {
     *      "select": "*",
     *      "from": "Product",
     *      "where": {"active": true},
     *     }
     * @apiParamExample {json} Abfrage mit Group, Count(), Limit und Offset
     *     {
     *      "select": ['title', 'field2', 'COUNT(id) AS users'],
     *      "from": "Product",
     *      "where": {"active": true},
     *      "groupBy": "category",
     *      "having": {"field": "value"},
     *      "orderBy": {"field": "ASC"},
     *      "addOrderBy": {"field": "DESC"},
     *      "setFirstResult": 10, //Offfset,
     *      "setMaxResults": 20, //Limit
     *     }
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *      [
     *          {
     *              "name" : "Produkt1",
     *              "active": true
     *          },
     *          {
     *              ..
     *          }
     *      ]
     */
    public function queryAction(Request $request){
        $params = $request->request->all();

        $api            = new Api($this->app, $request);
        $data           = $api->getQuery($params);
        $currentDate    = new \Datetime();

        return $this->renderResponse(array('ts' => $currentDate->format('Y-m-d H:i:s'), 'params' => $params, 'data' => $data));
    }



}