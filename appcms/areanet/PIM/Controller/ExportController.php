<?php
namespace Areanet\PIM\Controller;

use Areanet\PIM\Classes\Api;
use Areanet\PIM\Classes\Controller\BaseController;
use Areanet\PIM\Classes\Helper;
use Ellumilel\ExcelWriter;
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
        fputcsv($output, array_keys($data->header));
        foreach($data->rows as $csvRow){
            fputcsv($output, $csvRow);
        }

        return $response;
    }

    public function excelAction(Request $request)
    {
        $data = $this->loadFlatData($request);

        $wExcel = new ExcelWriter();

        $wExcel->writeSheetHeader('Export', $data->header);
        foreach($data->rows as $row){
            $wExcel->writeSheetRow('Export',$row);
        }

        $response = new Response();
        $response->headers->set('Content-Type', 'application/vnd.ms-excel');
        $response->headers->set('Content-Disposition', 'attachment; filename=export.xlsx');
        $output = $wExcel->writeToString();
        $response->setContent($output);
        return $response;

    }

    public function xmlAction(Request $request){

        $entityName         = $request->get('entity', 'Produkt');
        $where              = $request->get('where', null);

        $event = new \Areanet\PIM\Classes\Event();
        $event->setParam('entity',  $entityName);
        $event->setParam('request', $request);
        $event->setParam('where', $where);
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
        $data               = $api->getList($entityName, $where, $order, null, array(), null, $flatten);

        $xml = new \SimpleXMLElement('<items/>');
        $xml->addAttribute('entity', $entityName);

        foreach($data['objects'] as $object){
            $item = $xml->addChild('item');
            $item->addAttribute('id', $object->id);
            foreach($object as $key => $value){
                if(in_array($key, array('views', 'isIntern', 'id'))) continue;


                switch($entitySchema['properties'][$key]['type']){
                    case 'boolean':
                        $item->addChild($key, $value ? 1 : 0);
                        break;
                    case 'join':
                    case 'file':
                        $subitem = $item->addChild($key);
                        if($value) $subitem->addChild('id', isset($value->id) ? $value->id : $value);
                        break;
                    case 'multijoin':
                    case 'multifile':
                    case 'checkbox':
                        $subitem = $item->addChild($key);
                        foreach($value as $subobject){
                            $subitem->addChild('id', isset($subobject->id) ? $subobject->id : $subobject);
                        }
                        break;
                    case 'datetime':
                        if(isset($value['ISO8601'])){
                            $date       = new \DateTime($value['ISO8601']);
                            $format     = $entitySchema['properties'][$key]['format'];
                            $item->addChild($key, $date->format($helper->convertMomentFormatToPhp($format)));
                        }else{
                            $item->addChild($key, '');
                        }
                        break;
                    default:
                        $item->addChild($key, $value);
                        break;
                }
            }

            $event = new \Areanet\PIM\Classes\Event();
            $event->setParam('entity',  $entityName);
            $event->setParam('request', $request);
            $event->setParam('item', $item);
            $event->setParam('object', $object);
            $this->app['dispatcher']->dispatch('pim.export.xml.item', $event);

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

        $event = new \Areanet\PIM\Classes\Event();
        $event->setParam('entity',  $entityName);
        $event->setParam('request', $request);
        $event->setParam('where', $where);
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
        $data               = $api->getList($entityName, $where, $order, null, array(), null, $flatten);
        $csvHeaderInited    = false;
        $csvHeader          = array();
        $csvRows            = array();

        foreach($data['objects'] as $object){
            $csvRow = array();

            foreach($object as $key => $value){
                if(in_array($key, array('views', 'isIntern'))) continue;


                switch($entitySchema['properties'][$key]['type']){
                    case 'boolean':
                    case 'integer':
                        $csvRow[] = $value ? 1 : 0;
                        if(!$csvHeaderInited) $csvHeader[$key] = 'integer';
                        break;
                    case 'double':
                    case 'float':
                        if(!$csvHeaderInited) $csvHeader[$key] = 'float';
                    case 'join':
                    case 'file':
                        $csvRow[] = isset($value->id) ? $value->id : $value;
                        if(!$csvHeaderInited) $csvHeader[$key] = 'string';
                        break;
                    case 'multijoin':
                    case 'multifile':
                    case 'checkbox':
                        $values = array();
                        foreach($value as $subobjects){
                            $values[] = isset($subobjects->id) ? $subobjects->id : $subobjects;
                        }
                        $csvRow[] = join(',', $values);
                        if(!$csvHeaderInited) $csvHeader[$key] = 'string';
                        break;
                    case 'datetime':
                        if(isset($value['ISO8601'])){
                            $date       = new \DateTime($value['ISO8601']);
                            $format     = $entitySchema['properties'][$key]['format'];
                            $csvRow[]   = $date->format($helper->convertMomentFormatToPhp($format));
                            if(!$csvHeaderInited) $csvHeader[$key] = 'text';
                        }else{
                            if(!$csvHeaderInited) $csvHeader[$key] = 'text';
                            $csvRow[] = '';
                        }
                        if(!$csvHeaderInited) $csvHeader[$key] = 'text';
                        break;
                    default:
                        $csvRow[] = $value;
                        if(!$csvHeaderInited) $csvHeader[$key] = 'text';
                        break;
                }

                $event = new \Areanet\PIM\Classes\Event();
                $event->setParam('entity',  $entityName);
                $event->setParam('request', $request);
                $event->setParam('csvRow', $csvRow);
                $event->setParam('object', $object);
                $this->app['dispatcher']->dispatch('pim.export.csv-excel.item', $event);

            }

            $csvRows[]          = $event->getParam('csvRow');
            $csvHeaderInited    = true;
        }

        $returnData = new \stdClass();
        $returnData->header = $csvHeader;
        $returnData->rows   = $csvRows;

        return $returnData;
    }
}