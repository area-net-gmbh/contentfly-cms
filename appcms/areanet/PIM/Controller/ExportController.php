<?php
namespace Areanet\PIM\Controller;

use Areanet\PIM\Classes\Api;
use Areanet\PIM\Classes\Controller\BaseController;
use Areanet\PIM\Classes\Helper;
use Areanet\PIM\Classes\Permission;
use Ellumilel\ExcelWriter;
use PHPMailer\PHPMailer\Exception;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ExportController extends BaseController
{

    public function csvAction(Request $request)
    {
        $data = $this->loadFlatData($request);

        $response = new Response();
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename=export.csv');


        $output = fopen('php://output', 'w');

        stream_filter_register("newlines", "Areanet\PIM\Controller\StreamFilterNewlines");
        stream_filter_append($output, "newlines");

        fputcsv($output, array_keys($data->header), ';', '"');
        foreach($data->rows as $csvRow){
            fputcsv($output, $csvRow, ';', '"');
        }

        return $response;
    }

    public function excelAction(Request $request)
    {
        $data = $this->loadFlatData($request);

        $wExcel = new ExcelWriter();

        $wExcel->writeSheetHeader('Export', $data->header);
        foreach ($data->rows as $row) {
            $wExcel->writeSheetRow('Export', $row);
        }

        $response = new Response();
        $response->headers->set('Content-Type', 'application/vnd.ms-excel');
        $response->headers->set('Content-Disposition', 'attachment; filename=export.xlsx');
        //$response->headers->set
        $output = $wExcel->writeToString();
        $response->setContent($output);
        return $response;

    }

    public function jsonAction(Request $request)
    {
        $entityName         = $request->get('entity', 'Produkt');
        $where              = $request->get('where', null);
        $flatten            = $request->get('flatten', true);
        $lang               = $request->get('lang', null);

        if(!($permission = Permission::canExport($this->app['auth.user'], $entityName))){
            throw new Exception("Export von $entityName verweigert.", 403);
        }

        $event = new \Areanet\PIM\Classes\Event();
        $event->setParam('entity',  $entityName);
        $event->setParam('request', $request);
        $event->setParam('where', $where);
        $event->setParam('lang', $lang);
        $event->setParam('flatten', $flatten);
        $event->setParam('order', null);
        $this->app['dispatcher']->dispatch('pim.export.json.before', $event);

        $flatten    = $event->getParam('flatten');
        $order      = $event->getParam('order');
        $where      = $event->getParam('where');

        $api                = new Api($this->app, $request);
        $schema             = $api->getSchema();
        $data               = $api->getList($entityName, $where, $order, null, array(), null, $flatten, 0, 0, $lang);

        $response = new Response();
        $response->headers->set('Content-Type', 'text/json');
        $response->headers->set('Content-Disposition', 'attachment; filename=export.json');

        if(!$data){
            $response->setContent(json_encode(array()));
            return $response;
        }

        $event = new \Areanet\PIM\Classes\Event();
        $event->setParam('entity',  $entityName);
        $event->setParam('request', $request);
        $event->setParam('where', $where);
        $event->setParam('lang', $lang);
        $event->setParam('flatten', $flatten);
        $event->setParam('objects', $data['objects']);
        $this->app['dispatcher']->dispatch('pim.export.json.after', $event);

        $output = json_encode($event->getParam('objects'));
        $response->setContent($output);
        return $response;

    }

    public function xmlAction(Request $request){

        $entityName         = $request->get('entity', 'Produkt');
        $where              = $request->get('where', null);
        $flatten            = $request->get('flatten', true);
        $lang               = $request->get('lang', null);
        $flatten            = $request->get('flatten', true);

        if(!($permission = Permission::canExport($this->app['auth.user'], $entityName))){
            throw new Exception("Export von $entityName verweigert.", 403);
        }

        $event = new \Areanet\PIM\Classes\Event();
        $event->setParam('entity',  $entityName);
        $event->setParam('request', $request);
        $event->setParam('where', $where);
        $event->setParam('lang', $lang);
        $event->setParam('flatten', false);
        $event->setParam('order', null);
        $this->app['dispatcher']->dispatch('pim.export.xml.before', $event);

        $flatten    = $event->getParam('flatten');
        $order      = $event->getParam('order');
        $where      = $event->getParam('where');

        $helper             = new Helper();
        $api                = new Api($this->app, $request);
        $schema             = $api->getSchema();
        $entitySchema       = $schema[ucfirst($entityName)];
        $data               = $api->getList($entityName, $where, $order, null, array(), null, $flatten, 0, 0, $lang);

        $xml = new \SimpleXMLElement('<items/>');
        $xml->addAttribute('entity', $entityName);

        if($lang){
            $xml->addAttribute('lang', $lang);
        }

        if($data) {
            foreach ($data['objects'] as $object) {
                $item = $xml->addChild('item');
                $item->addAttribute('id', $object->id);

                foreach ($object as $key => $value) {
                    if (in_array($key, array('views', 'isIntern', 'id', 'permissions', 'lang'))) continue;


                    switch ($entitySchema['properties'][$key]['type']) {

                        case 'boolean':
                            $subitem = $item->addChild($key, $value ? 1 : 0);
                            $subitem->addAttribute('type', $entitySchema['properties'][$key]['type']);
                            break;
                        case 'join':
                        case 'onejoin':
                        case 'file':
                            $subitem = $item->addChild($key);
                            $subitem->addAttribute('type', $entitySchema['properties'][$key]['type']);
                            if($entitySchema['properties'][$key]['type'] == 'file'){
                                $subitem->addAttribute('entity', 'PIM\\File');
                            }else{
                                $entityShortName = $helper->getShortEntityName($entitySchema['properties'][$key]['accept']);
                                $subitem->addAttribute('entity', $entityShortName);
                            }

                            if ($value) {
                                $subitem2 =  $subitem->addChild('item');
                                if (is_array($value)) {
                                    $subitem2->addAttribute('id', isset($value['id']) ? $value['id'] : '');
                                } else {
                                    $subitem2->addAttribute('id', isset($value->id) ? $value->id : '');
                                }

                                $event = new \Areanet\PIM\Classes\Event();
                                $event->setParam('entity', $entityName);
                                $event->setParam('request', $request);
                                $event->setParam('subitem2', $subitem2);
                                $event->setParam('value', $value);
                                $event->setParam('lang', $lang);
                                $this->app['dispatcher']->dispatch('pim.export.xml.join.subitem', $event);

                            }
                            break;
                        case 'multijoin':
                        case 'multifile':
                        case 'checkbox':
                            $subitem = $item->addChild($key);
                            $subitem->addAttribute('type', $entitySchema['properties'][$key]['type']);
                            if($entitySchema['properties'][$key]['type'] == 'multifile'){
                                $subitem->addAttribute('entity', 'PIM\\File');
                            }else{
                                $entityShortName = $helper->getShortEntityName($entitySchema['properties'][$key]['accept']);
                                $subitem->addAttribute('entity', $entityShortName);
                            }

                            if ($value) {
                                foreach ($value as $subobject) {
                                    if ($subobject) {
                                        $subitem2 =  $subitem->addChild('item');
                                        if (is_array($subobject)) {
                                            $subitem2->addAttribute('id', isset($subobject['id']) ? $subobject['id'] : '');
                                        } else {
                                            $subitem2->addAttribute('id', isset($subobject->id) ? $subobject->id : '');
                                        }

                                        $event = new \Areanet\PIM\Classes\Event();
                                        $event->setParam('entity', $entityName);
                                        $event->setParam('request', $request);
                                        $event->setParam('subitem2', $subitem2);
                                        $event->setParam('value', $value);
                                        $event->setParam('lang', $lang);
                                        $this->app['dispatcher']->dispatch('pim.export.xml.multijoin.subitem', $event);

                                    }
                                }
                            }
                            break;
                        case 'datetime':
                            if (isset($value['ISO8601'])) {
                                $date = new \DateTime($value['ISO8601']);
                                $format = $entitySchema['properties'][$key]['format'];
                                $subitem = $item->addChild($key, $date->format($helper->convertMomentFormatToPhp($format)));
                                $subitem->addAttribute('type', $entitySchema['properties'][$key]['type']);
                            } else {
                                $subitem = $item->addChild($key, '');
                                $subitem->addAttribute('type', $entitySchema['properties'][$key]['type']);
                            }
                            break;
                        default:
                            $subitem = $item->addChild($key, htmlspecialchars($value));
                            $subitem->addAttribute('type', $entitySchema['properties'][$key]['type']);
                            break;
                    }
                }

                $event = new \Areanet\PIM\Classes\Event();
                $event->setParam('entity', $entityName);
                $event->setParam('request', $request);
                $event->setParam('item', $item);
                $event->setParam('object', $object);
                $event->setParam('lang', $lang);
                $this->app['dispatcher']->dispatch('pim.export.xml.item', $event);

            }
        }

        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        $response->headers->set('Content-Disposition', 'attachment; filename=export.xml');
        $response->setContent($xml->asXML());
        return $response;
    }


    private function loadFlatData(Request $request){
        $entityName         = $request->get('entity', 'Produkt');
        $where              = $request->get('where', null);
        $lang               = $request->get('lang', null);

        if(!($permission = Permission::canExport($this->app['auth.user'], $entityName))){
            throw new Exception("Export von $entityName verweigert.", 403);
        }

        $event = new \Areanet\PIM\Classes\Event();
        $event->setParam('entity',  $entityName);
        $event->setParam('request', $request);
        $event->setParam('where', $where);
        $event->setParam('lang', $lang);
        $event->setParam('flatten', false);
        $event->setParam('order', null);
        $this->app['dispatcher']->dispatch('pim.export.csv-excel.before', $event);

        $flatten    = $event->getParam('flatten');
        $order      = $event->getParam('order');
        $where      = $event->getParam('where');

        $helper             = new Helper();
        $api                = new Api($this->app, $request);
        $schema             = $api->getSchema();
        $entitySchema       = $schema[ucfirst($entityName)];
        $data               = $api->getList($entityName, $where, $order, null, array(), null, $flatten, 0, 0, $lang);
        $csvHeaderInited    = false;
        $csvHeader          = array('id' => APPCMS_ID_TYPE);
        $csvRows            = array();

        if($data) {
            foreach ($data['objects'] as $object) {
                $csvRow = array($object->id);

                foreach ($object as $key => $value) {
                    if (in_array($key, array('views', 'isIntern', 'id', 'permissions'))) continue;

                    switch ($entitySchema['properties'][$key]['type']) {
                        case 'boolean':
                        case 'integer':
                            $csvRow[] = $value ? 1 : 0;
                            if (!$csvHeaderInited) $csvHeader[$key] = 'integer';
                            break;
                        case 'double':
                        case 'float':
                            if (!$csvHeaderInited) $csvHeader[$key] = 'float';
                        case 'join':
                        case 'onejoin':
                        case 'file':

                            if ($value) {
                                $flattenedValue = null;
                                if (is_array($value)) {
                                    $flattenedValue = isset($value['id']) ? $value['id'] : '';
                                } else {
                                    $flattenedValue = isset($value->id) ? $value->id : '';
                                }

                                $event = new \Areanet\PIM\Classes\Event();
                                $event->setParam('entity', $entityName);
                                $event->setParam('request', $request);
                                $event->setParam('flattenedValue', $flattenedValue);
                                $event->setParam('value', $value);
                                $event->setParam('lang', $lang);
                                $this->app['dispatcher']->dispatch('pim.export.csv-excel.join.subitem', $event);

                                $csvRow[] = $event->getParam('flattenedValue');
                            }else{
                                $csvRow[] = null;
                            }

                            if (!$csvHeaderInited) $csvHeader[$key] = APPCMS_ID_TYPE;
                            break;
                        case 'multijoin':
                        case 'multifile':
                        case 'checkbox':
                            $values = array();
                            if ($value) {
                                foreach ($value as $subobject) {
                                    $flattenedValue = null;
                                    if (is_array($subobject)) {
                                        $flattenedValue = isset($subobject['id']) ? $subobject['id'] : '';
                                    } else {
                                        $flattenedValue = isset($subobject->id) ? $subobject->id : '';
                                    }

                                    $event = new \Areanet\PIM\Classes\Event();
                                    $event->setParam('entity', $entityName);
                                    $event->setParam('request', $request);
                                    $event->setParam('flattenedValue', $flattenedValue);
                                    $event->setParam('subobject', $subobject);
                                    $event->setParam('lang', $lang);
                                    $this->app['dispatcher']->dispatch('pim.export.csv-excel.multijoin.subitem', $event);

                                    $values[] = $event->getParam('flattenedValue');
                                }
                            }
                            $csvRow[] = join(',', $values);
                            if (!$csvHeaderInited) $csvHeader[$key] = 'string';
                            break;
                        case 'datetime':
                            if (isset($value['ISO8601'])) {
                                $date = new \DateTime($value['ISO8601']);
                                $format = $entitySchema['properties'][$key]['format'];
                                $csvRow[] = $date->format($helper->convertMomentFormatToPhp($format));
                                if (!$csvHeaderInited) $csvHeader[$key] = 'text';
                            } else {
                                if (!$csvHeaderInited) $csvHeader[$key] = 'text';
                                $csvRow[] = '';
                            }
                            if (!$csvHeaderInited) $csvHeader[$key] = 'text';
                            break;
                        default:
                            $csvRow[] = $value;
                            if (!$csvHeaderInited) $csvHeader[$key] = 'text';
                            break;
                    }

                    $event = new \Areanet\PIM\Classes\Event();
                    $event->setParam('entity', $entityName);
                    $event->setParam('request', $request);
                    $event->setParam('csvRow', $csvRow);
                    $event->setParam('object', $object);
                    $event->setParam('lang', $lang);
                    $this->app['dispatcher']->dispatch('pim.export.csv-excel.item', $event);

                }

                $csvRows[] = $event->getParam('csvRow');
                $csvHeaderInited = true;
            }
        }

        $returnData = new \stdClass();
        $returnData->header = $csvHeader;
        $returnData->rows   = $csvRows;

        return $returnData;
    }
}

class StreamFilterNewlines extends \php_user_filter {
    function filter($in, $out, &$consumed, $closing) {

        while ( $bucket = stream_bucket_make_writeable($in) ) {
            $bucket->data = preg_replace('/([^\r])\n/', "$1\r\n", $bucket->data);
            $consumed += $bucket->datalen;
            stream_bucket_append($out, $bucket);
        }
        return PSFS_PASS_ON;
    }
}